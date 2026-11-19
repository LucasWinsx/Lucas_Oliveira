<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/estilo_auth.php';
auth_tabela_recuperacao($pdo);

$msg = ''; $erro = ''; $link = '';
$local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $st = $pdo->prepare('SELECT id, nome, email FROM usuarios WHERE email=?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u) {
            $pdo->prepare('DELETE FROM recuperacao_senha WHERE usuario_id=?')->execute([$u['id']]);
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO recuperacao_senha (usuario_id, token_hash, expira_em) VALUES (?,?,?)')
                ->execute([$u['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $url = $esquema . '://' . $_SERVER['HTTP_HOST'] . BASE . '/redefinir.php?token=' . $token;
            $corpo = "Olá, " . ($u['nome'] ?: $u['email']) . "!\n\nPara criar uma nova senha, acesse o link abaixo (vale por 1 hora):\n$url\n\nSe você não pediu isso, ignore este e-mail.";
            @mail($u['email'], 'Recuperação de senha', $corpo, "Content-Type: text/plain; charset=UTF-8");
            if ($local) $link = $url; // no XAMPP o e-mail normalmente não sai: mostra o link só em localhost
        }
        // mesma resposta exista ou não o e-mail (não revela quem tem conta)
        $msg = 'Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha.';
    }
}

auth_topo('Recuperar senha');
?>
  <form class="cartao-form" method="POST">
    <h2>Recuperar senha</h2>
    <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <?php if ($msg): ?><p class="ok"><?= h($msg) ?></p><?php endif; ?>
    <?php if ($link): ?><p class="dica">Modo local (localhost): o e-mail pode não ser enviado, use este link:<br><a href="<?= h($link) ?>"><?= h($link) ?></a></p><?php endif; ?>
    <label>E-mail da sua conta <input type="email" name="email" autocomplete="email" required value="<?= h($_POST['email'] ?? '') ?>"></label>
    <button>Enviar link</button>
    <p><a href="login.php">Voltar para entrar</a></p>
  </form>
<?php auth_rodape();
