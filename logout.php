<?php
require_once 'admin/config/auth.php';

/* Logout protegido: só encerra a sessão com o token CSRF (link do sistema ou formulário POST).
   Quem chegar sem token (ex.: link de outro site) vê uma tela de confirmação. */
function encerrarSessao(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();
    header('Location: ' . BASE . '/login.php'); exit;
}

if (!usuario()) { header('Location: ' . BASE . '/login.php'); exit; }

$token = (string)($_POST['csrf'] ?? $_GET['t'] ?? '');
if ($token !== '' && hash_equals(csrfToken(), $token)) encerrarSessao();

topo('Sair');
?>
<form method="POST" style="max-width:360px;margin:40px auto;text-align:center">
  <?= csrfCampo() ?>
  <p>Deseja encerrar a sessão?</p>
  <button>Sim, sair</button> <a href="<?= h(BASE) ?>/index.php">Cancelar</a>
</form>
<?php rodape();
