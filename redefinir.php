<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/estilo_auth.php';
auth_tabela_recuperacao($pdo);

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$erro = '';
$reg = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = $pdo->prepare('SELECT * FROM recuperacao_senha WHERE token_hash=? AND expira_em > ?');
    $st->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $reg = $st->fetch();
}

if ($reg && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova = $_POST['senha'] ?? '';
    $conf = $_POST['confirmar'] ?? '';
    if (strlen($nova) < 6) $erro = 'A senha precisa ter ao menos 6 caracteres.';
    elseif ($nova !== $conf) $erro = 'As senhas não são iguais.';
    else {
        $pdo->prepare('UPDATE usuarios SET senha=? WHERE id=?')->execute([password_hash($nova, PASSWORD_DEFAULT), $reg['usuario_id']]);
        $pdo->prepare('DELETE FROM recuperacao_senha WHERE usuario_id=?')->execute([$reg['usuario_id']]);
        flash('Senha alterada! Faça login com a nova senha.');
        header('Location: login.php'); exit;
    }
}

auth_topo('Nova senha');
?>
<?php if (!$reg): ?>
  <form class="cartao-form" onsubmit="return false">
    <h2>Link inválido</h2>
    <p class="erro">Este link expirou ou já foi usado.</p>
    <p><a href="recuperar.php">Pedir um novo link</a></p>
  </form>
<?php else: ?>
  <form class="cartao-form" method="POST">
    <h2>Nova senha</h2>
    <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">
    <label>Nova senha (mín. 6 caracteres) <input type="password" name="senha" autocomplete="new-password" required></label>
    <label>Confirmar nova senha <input type="password" name="confirmar" autocomplete="new-password" required></label>
    <button>Salvar senha</button>
    <p><a href="login.php">Voltar para entrar</a></p>
  </form>
<?php endif; ?>
<?php auth_rodape();
