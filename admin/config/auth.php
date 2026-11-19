<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);   // cookie de sessão inacessível ao JavaScript
    session_start();
}
require_once __DIR__ . '/db.php';
const BASE = '/lucas_oliveira';   // pasta do projeto dentro do htdocs

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function moeda($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function usuario() { return $_SESSION['u'] ?? null; }
function exigirLogin() { if (!usuario()) { header('Location: ' . BASE . '/login.php'); exit; } }
function exigirAdmin() {
    exigirLogin();
    if (usuario()['tipo'] !== 'admin') { header('Location: ' . BASE . '/cliente/loja.php'); exit; }
}
function csrfToken() { $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrfCampo() { return '<input type="hidden" name="csrf" value="' . csrfToken() . '">'; }
function logoutUrl() { return BASE . '/logout.php?t=' . csrfToken(); }   // o logout exige o token (evita deslogar por link de terceiros)
function csrfOk() { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }
function flash($m = null) {
    if ($m !== null) { $_SESSION['msg'] = $m; return ''; }
    $x = $_SESSION['msg'] ?? ''; unset($_SESSION['msg']); return $x;
}
function topo($titulo) {
    $u = usuario(); $b = BASE;
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . h($titulo) . ' - Lucas_Oliveira</title><link rel="stylesheet" href="' . $b . '/admin/public/css/loja.css"></head><body>'
       . '<header><a class="logo" href="' . $b . '/index.php">Lucas_Oliveira</a><nav>';
    if ($u) {
        if ($u['tipo'] === 'admin')
            echo "<a href='$b/admin/views/dashboard.php'>Dashboard</a><a href='$b/admin/views/pedidos.php'>Pedidos</a><a href='$b/admin/views/gerenciar_games.php'>Jogos</a><a href='$b/admin/views/ver_categorias.php'>Categorias</a><a href='$b/admin/views/ver_clientes.php'>Clientes</a><a href='$b/admin/views/ver_usuarios.php'>Usuários</a>";
        else
            echo "<a href='$b/cliente/loja.php'>Loja</a><a href='$b/cliente/meus_pedidos.php'>Meus pedidos</a>";
        echo '<span>' . h($u['nome']) . '</span><a href="' . logoutUrl() . '">Sair</a>';
    }
    echo '</nav></header><main>';
    if ($m = flash()) echo '<p class="aviso">' . h($m) . '</p>';
}
function rodape() { echo '</main></body></html>'; }
