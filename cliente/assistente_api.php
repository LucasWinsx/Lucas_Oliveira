<?php
require_once __DIR__ . '/../admin/config/auth.php';
require_once __DIR__ . '/../admin/models/assistente.php';

/* Endpoint do assistente (chamado pelo botão de chat via fetch). Sempre responde JSON.
   Rotas (campo "acao" do POST):
   - (vazio)   : pergunta ao assistente (usa a IA e o limite de perguntas)
   - carrinho  : resumo do carrinho (sem IA)
   - add       : adiciona 1 unidade de game_id ao carrinho (botão dos cards, sem IA)
   - limpar    : começa uma nova conversa */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ob_start();   // segura avisos/deprecations do PHP para não corromperem o JSON

function responder(int $codigo, array $dados): void {
    global $ehAdmin;
    $lixo = '';
    while (ob_get_level() > 0) $lixo .= (string)ob_get_clean();
    if (trim($lixo) !== '') {
        $txt = mb_substr(trim(strip_tags($lixo)), 0, 400);
        error_log('Assistente: saída inesperada do PHP antes do JSON: ' . $txt);
        if (!empty($ehAdmin) && empty($dados['detalhe'])) $dados['detalhe'] = 'PHP emitiu: ' . $txt;
    }
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function detalheErro(Throwable $e): string {
    return get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
}

function carrinhoJson(array $c): array {
    return ['quantidade' => (int)$c['quantidade_total'], 'total' => moeda($c['total'])];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['erro' => 'Método não permitido.']);
$u = usuario();
$ehAdmin = ($u['tipo'] ?? '') === 'admin';
if (!$u) responder(401, ['erro' => 'Entre na sua conta para falar com o assistente.']);
if (!csrfOk()) responder(403, ['erro' => 'Sessão expirada. Recarregue a página.']);

$acao = (string)($_POST['acao'] ?? '');

if ($acao === 'limpar') { unset($_SESSION['ass_hist']); responder(200, ['ok' => true]); }

// ações de carrinho sem IA (a sessão continua aberta, então o model não mexe nela)
if ($acao === 'carrinho' || $acao === 'add') {
    try {
        $ass = new Assistente();
        if ($acao === 'carrinho') responder(200, ['ok' => true, 'carrinho' => carrinhoJson($ass->carrinho())]);
        $r = $ass->adicionarCarrinho((int)($_POST['game_id'] ?? 0), 1);
        if (empty($r['ok'])) responder(200, ['ok' => false, 'mensagem' => $r['erro'] ?? 'Não foi possível adicionar.', 'carrinho' => carrinhoJson($ass->carrinho())]);
        responder(200, [
            'ok' => true,
            'mensagem' => '“' . $r['jogo'] . '” está no carrinho (' . $r['quantidade_no_carrinho'] . ').' . (!empty($r['ajustado_ao_limite']) ? ' Esse é o máximo disponível.' : ''),
            'carrinho' => carrinhoJson($r['carrinho']),
        ]);
    } catch (Throwable $e) {
        error_log('Assistente carrinho: ' . $e->getMessage());
        responder(500, ['erro' => 'Não foi possível atualizar o carrinho agora.'] + ($ehAdmin ? ['detalhe' => detalheErro($e)] : []));
    }
}

$pergunta = trim((string)($_POST['pergunta'] ?? ''));
if ($pergunta === '') responder(422, ['erro' => 'Digite sua pergunta.']);
$pergunta = mb_substr($pergunta, 0, 300);

// limite por usuário (protege a cota gratuita da API)
$maximo = 15;
$arqIa = __DIR__ . '/../admin/config/ia.php';
if (is_file($arqIa)) require_once $arqIa;
if (defined('ASSISTENTE_MAX_PERGUNTAS')) $maximo = max(1, (int)ASSISTENTE_MAX_PERGUNTAS);
$agora = time();
$log = array_values(array_filter($_SESSION['ass_log'] ?? [], fn($t) => $t > $agora - 600));
if (count($log) >= $maximo) responder(429, ['erro' => 'Você fez muitas perguntas seguidas. Aguarde alguns minutos.']);
if ($log && end($log) > $agora - 2) responder(429, ['erro' => 'Aguarde um instante antes da próxima pergunta.']);
$log[] = $agora;
$_SESSION['ass_log'] = $log;

$historico = $_SESSION['ass_hist'] ?? [];

// A sessão é liberada enquanto a IA pensa (pode demorar alguns segundos). Quando o assistente
// precisa mexer no carrinho, ele reabre a sessão rapidamente e fecha de novo.
$abrir = function () {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
};
$fechar = function () { if (session_status() === PHP_SESSION_ACTIVE) session_write_close(); };
$fechar();

try {
    $r = (new Assistente($abrir, $fechar))->conversar($pergunta, $historico);
} catch (AssistenteException $e) {
    $d = ['erro' => $e->getMessage()];
    if ($ehAdmin && $e->detalhe !== '') $d['detalhe'] = $e->detalhe;
    responder(503, $d);
} catch (Throwable $e) {
    error_log('Assistente: ' . $e->getMessage());
    responder(500, ['erro' => 'Não foi possível responder agora. Tente novamente.'] + ($ehAdmin ? ['detalhe' => detalheErro($e)] : []));
}

// guarda a conversa na sessão (no servidor). Anota os ids dos jogos mostrados para "adiciona o segundo" funcionar na próxima pergunta.
$nota = '';
if ($r['jogos']) $nota = "\n[jogos mostrados: " . implode('; ', array_map(fn($g) => (int)$g['id'] . '=' . $g['titulo'], $r['jogos'])) . ']';
$abrir();
$historico[] = ['r' => 'user', 't' => $pergunta];
$historico[] = ['r' => 'model', 't' => $r['resposta'] . $nota];
$_SESSION['ass_hist'] = array_slice($historico, -8);
$fechar();

$img = function ($arq) {
    $arq = (string)$arq;
    if ($arq === '') return '';
    return preg_match('~^(https?:)?//~', $arq) ? $arq : BASE . '/admin/public/imagens/' . rawurlencode(ltrim($arq, '/'));
};
responder(200, [
    'resposta' => $r['resposta'],
    'jogos' => array_map(fn($g) => [
        'id'      => (int)$g['id'],
        'titulo'  => $g['titulo'],
        'preco'   => moeda($g['preco']),
        'estoque' => (int)$g['estoque'],
        'imagem'  => $img($g['imagem']),
        'link'    => BASE . '/cliente/jogo.php?id=' . (int)$g['id'],
    ], $r['jogos']),
    'acoes'    => $r['acoes'],
    'carrinho' => $r['carrinho'] ? carrinhoJson($r['carrinho']) : null,
]);
