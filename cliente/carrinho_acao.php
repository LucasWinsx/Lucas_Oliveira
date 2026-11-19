<?php
/* Ações do carrinho (guardado em $_SESSION['carrinho'][game_id] = quantidade).
   REGRA: no máximo 1 unidade de cada jogo no carrinho.
   Funciona por fetch (devolve JSON) e também por envio normal do formulário (redireciona). */
require_once __DIR__ . '/../admin/config/auth.php';

const LIMITE_POR_JOGO = 1;

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

/* A mensagem flash só vale para o envio normal; no fetch quem mostra o aviso é o JavaScript. */
function aviso(bool $ajax, string $m): void { if (!$ajax) flash($m); }

function responder(bool $ajax, array $dados, int $codigo, string $volta): void {
    if ($ajax) {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados);
    } else {
        header('Location: ' . $volta);
    }
    exit;
}

$u   = usuario();
$gid = (int)($_POST['game_id'] ?? 0);
$acao = $_POST['acao'] ?? 'add';
$volta = ($_POST['volta'] ?? '') === 'carrinho' ? BASE . '/cliente/carrinho.php' : BASE . '/cliente/jogo.php?id=' . $gid;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder($ajax, ['erro' => 'metodo'], 405, BASE . '/catalogo.php');
if (!csrfOk()) { aviso($ajax, 'Sessão expirada. Tente novamente.'); responder($ajax, ['erro' => 'Sessão expirada. Recarregue a página.'], 400, $volta); }
if ($u && ($u['tipo'] ?? '') === 'admin') { aviso($ajax, 'Administradores não compram jogos.'); responder($ajax, ['erro' => 'Administradores não compram jogos.'], 403, $volta); }

$_SESSION['carrinho'] ??= [];
$total = fn() => array_sum($_SESSION['carrinho']);

if ($acao === 'remove') {
    unset($_SESSION['carrinho'][$gid]);
    responder($ajax, ['ok' => true, 'no_carrinho' => false, 'total' => $total()], 200, $volta);
}

if ($acao !== 'add' && $acao !== 'set') responder($ajax, ['erro' => 'Ação inválida.'], 400, $volta);

$st = $pdo->prepare('SELECT id, titulo, estoque FROM games WHERE id=?');
$st->execute([$gid]);
$g = $st->fetch();
if (!$g) { aviso($ajax, 'Jogo não encontrado.'); responder($ajax, ['erro' => 'Jogo não encontrado.'], 404, BASE . '/catalogo.php'); }

if ((int)$g['estoque'] <= 0) {
    unset($_SESSION['carrinho'][$gid]);
    aviso($ajax, '“' . $g['titulo'] . '” está esgotado.');
    responder($ajax, ['erro' => '“' . $g['titulo'] . '” está esgotado.'], 409, $volta);
}

// já está no carrinho: não soma, mantém 1 unidade e avisa
if (!empty($_SESSION['carrinho'][$gid])) {
    $_SESSION['carrinho'][$gid] = LIMITE_POR_JOGO;
    aviso($ajax, '“' . $g['titulo'] . '” já está no carrinho (limite de 1 unidade por jogo).');
    responder($ajax, ['ok' => true, 'ja_estava' => true, 'no_carrinho' => true, 'total' => $total(),
                      'mensagem' => 'Esse jogo já está no carrinho (limite de 1 unidade por jogo).'], 200, $volta);
}

$_SESSION['carrinho'][$gid] = LIMITE_POR_JOGO;
aviso($ajax, '“' . $g['titulo'] . '” foi adicionado ao carrinho.');
responder($ajax, ['ok' => true, 'ja_estava' => false, 'no_carrinho' => true, 'total' => $total(),
                  'mensagem' => 'Adicionado ao carrinho.'], 200, $volta);
