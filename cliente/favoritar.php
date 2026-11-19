<?php
/* Liga/desliga um jogo na lista de desejos do usuário logado.
   Funciona por fetch (devolve JSON) e também por envio normal do formulário (redireciona). */
require_once __DIR__ . '/../admin/config/auth.php';

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

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

$u = usuario();
$gid = (int)($_POST['game_id'] ?? 0);
$voltaJogo = BASE . '/cliente/jogo.php?id=' . $gid;
$volta = ($_POST['volta'] ?? '') === 'lista' ? BASE . '/cliente/lista_desejos.php' : $voltaJogo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder($ajax, ['erro' => 'metodo'], 405, BASE . '/index.php');
if (!$u) responder($ajax, ['login' => true], 401, BASE . '/login.php');
if (!csrfOk()) { flash('Sessão expirada. Tente novamente.'); responder($ajax, ['erro' => 'csrf'], 400, $volta); }

$st = $pdo->prepare('SELECT id FROM games WHERE id=?');
$st->execute([$gid]);
if (!$st->fetchColumn()) { flash('Jogo não encontrado.'); responder($ajax, ['erro' => 'jogo'], 404, BASE . '/index.php'); }

try {
    $st = $pdo->prepare('SELECT 1 FROM favoritos WHERE usuario_id=? AND game_id=?');
    $st->execute([(int)$u['id'], $gid]);
    $jaTem = (bool)$st->fetchColumn();
    if ($jaTem) {
        $pdo->prepare('DELETE FROM favoritos WHERE usuario_id=? AND game_id=?')->execute([(int)$u['id'], $gid]);
    } else {
        $pdo->prepare('INSERT INTO favoritos (usuario_id, game_id) VALUES (?,?)')->execute([(int)$u['id'], $gid]);
    }
} catch (Throwable $e) {
    flash('Não foi possível atualizar a lista de desejos. Rode o favoritos.sql no banco.');
    responder($ajax, ['erro' => 'banco'], 500, $volta);
}

responder($ajax, ['fav' => !$jaTem], 200, $volta);
