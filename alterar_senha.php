<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/estilo_auth.php';

if (empty($_SESSION['u']['id'])) { header('Location: login.php'); exit; } // precisa estar logado

$erro = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual = $_POST['atual'] ?? '';
    $nova  = $_POST['nova'] ?? '';
    $conf  = $_POST['confirmar'] ?? '';
    $st = $pdo->prepare('SELECT id, senha FROM usuarios WHERE id=?');
    $st->execute([$_SESSION['u']['id']]);
    $u = $st->fetch();
    $hash = (string)($u['senha'] ?? '');
    $atualOk = $u && password_verify($atual, $hash);
    if (!auth_csrf_ok()) $erro = 'Sessão expirada. Recarregue a página e tente de novo.';
    elseif (!$atualOk) $erro = 'A senha atual está incorreta.';
    elseif (strlen($nova) < 6) $erro = 'A nova senha precisa ter ao menos 6 caracteres.';
    elseif ($nova !== $conf) $erro = 'A confirmação não é igual à nova senha.';
    elseif ($nova === $atual) $erro = 'A nova senha deve ser diferente da atual.';
    else {
        $pdo->prepare('UPDATE usuarios SET senha=? WHERE id=?')->execute([password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
        try { $pdo->prepare('DELETE FROM recuperacao_senha WHERE usuario_id=?')->execute([$u['id']]); } catch (Throwable $e) {}
        session_regenerate_id(true);
        $ok = 'Senha alterada com sucesso!';
    }
}

auth_topo('Alterar senha');
?>
  <form class="cartao-form" method="POST">
    <h2>Alterar senha</h2>
    <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <?php if ($ok): ?><p class="ok"><?= h($ok) ?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= h(auth_csrf()) ?>">
    <label>Senha atual <input type="password" name="atual" autocomplete="current-password" required></label>
    <label>Nova senha (mín. 6 caracteres) <input type="password" name="nova" autocomplete="new-password" required></label>
    <label>Confirmar nova senha <input type="password" name="confirmar" autocomplete="new-password" required></label>
    <button>Alterar senha</button>
    <p><a href="<?= h(BASE) ?>/index.php">Voltar para o início</a></p>
  </form>
<?php auth_rodape();
