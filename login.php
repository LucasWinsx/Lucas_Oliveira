<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/admin/models/games.php';
$erro = '';
const MAX_TENTATIVAS = 5;     // erros seguidos antes do bloqueio temporário
const BLOQUEIO_SEG   = 60;    // duração do bloqueio em segundos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfOk()) {
    $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
  } elseif (($_SESSION['login_bloqueado_ate'] ?? 0) > time()) {
    $erro = 'Muitas tentativas. Aguarde ' . ($_SESSION['login_bloqueado_ate'] - time()) . ' segundos e tente de novo.';
  } else {
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE email=?');
    $st->execute([trim($_POST['email'] ?? '')]);
    $u = $st->fetch();
    $senha = $_POST['senha'] ?? '';
    $ok = false;
    if ($u) {
        if (password_verify($senha, (string)$u['senha'])) $ok = true;
        elseif (hash_equals((string)$u['senha'], $senha)) { // senha antiga em texto puro: converte para hash
            $ok = true;
            $pdo->prepare('UPDATE usuarios SET senha=? WHERE id=?')->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
        }
    }
    if ($ok) {
        unset($_SESSION['login_falhas'], $_SESSION['login_bloqueado_ate']);
        session_regenerate_id(true);
        $_SESSION['u'] = ['id' => $u['id'], 'nome' => $u['nome'] ?: $u['email'], 'tipo' => $u['tipo'] ?? 'cliente'];
        header('Location: ' . BASE . '/index.php'); exit; // todos os usuários (clientes e admin) vão para a tela inicial
    }
    $_SESSION['login_falhas'] = ($_SESSION['login_falhas'] ?? 0) + 1;
    if ($_SESSION['login_falhas'] >= MAX_TENTATIVAS) {
        $_SESSION['login_bloqueado_ate'] = time() + BLOQUEIO_SEG;
        $_SESSION['login_falhas'] = 0;
    }
    $erro = 'E-mail ou senha incorretos.';
  }
}

/* Imagem do último jogo cadastrado (fallback do fundo) */
$ultimo = Game::ultimoComImagem();
$url = function ($c) {
    $c = (string)$c;
    return preg_match('~^(https?:)?//|^/~', $c) ? $c : BASE . '/admin/public/imagens/' . rawurlencode(ltrim($c, '/'));
};
$capa = $ultimo ? $url($ultimo['imagem']) : '';

/* Imagens da pasta C:\xampp\htdocs\Lucas_Oliveira\admin\public\imagens (alternam ao fundo) */
$pasta = __DIR__ . '/admin/public/imagens';
$imgs = [];
if (is_dir($pasta)) {
    foreach (scandir($pasta) as $f) {
        if (preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $f)) $imgs[] = BASE . '/admin/public/imagens/' . rawurlencode($f);
    }
}
shuffle($imgs);
if (!$imgs && $capa) $imgs = [$capa]; // se a pasta estiver vazia, usa a capa do último jogo

topo('Entrar');
?>
<style>
  body { background: linear-gradient(135deg, #a3a7b0, #666a74) fixed !important; color: #1a1a1a; font-family: "Segoe UI", system-ui, -apple-system, sans-serif; overflow-x: hidden; }
  /* esconde o cabeçalho antigo; a barra preta abaixo o substitui */
  header, .topo, .cabecalho, .navbar, nav { display: none !important; }

  /* botão "Tela de início" translúcido (vidro) */
  .atv-inicio { position: fixed; top: 20px; left: 24px; z-index: 5; padding: 10px 20px; border-radius: 999px; color: #fff; text-decoration: none; font-weight: 600; font-size: .95rem;
    background: rgba(255,255,255,.18); backdrop-filter: blur(20px) saturate(1.4); -webkit-backdrop-filter: blur(20px) saturate(1.4);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.35), 0 8px 24px -8px rgba(0,0,0,.4); text-shadow: 0 1px 3px rgba(0,0,0,.45); transition: transform .2s, background .2s; }
  .atv-inicio:hover, .atv-inicio:focus-visible { transform: scale(1.06); background: rgba(255,255,255,.3); outline: none; }

  /* imagens ao fundo translúcidas, estilo Apple TV: suaves, esfumaçadas e em movimento lento */
  /* imagem cobrindo a tela toda */
  .atv-fundo { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; background: radial-gradient(ellipse at 30% 20%, #2b3150, #0b0d14 75%); }
  .atv-fundo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 25%; filter: blur(5px) saturate(1.35) brightness(.9); opacity: 0; transition: opacity 2.2s ease-in-out; will-change: transform, opacity; }
  .atv-fundo img.on { opacity: .58; animation: atv-zoom 18s ease-out forwards; }
  .atv-fundo::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(255,255,255,.07), rgba(0,0,0,.28)), radial-gradient(ellipse at center, rgba(0,0,0,.12), rgba(0,0,0,.45) 95%); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); }
  @keyframes atv-zoom { from { transform: scale(1.04) translate3d(0,0,0); } to { transform: scale(1.14) translate3d(-1.5%, -1%, 0); } }

  /* sem caixa: só os campos, discretos, direto sobre a imagem */
  .atv-cena { position: relative; z-index: 2; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 24px; box-sizing: border-box; }
  .atv-cena .cartao-form { width: 100%; max-width: 300px; margin: 0; padding: 0; border: 0; background: none; box-shadow: none; color: #fff;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", system-ui, sans-serif; text-shadow: 0 1px 8px rgba(0,0,0,.55); }
  .atv-cena .cartao-form h2 { margin: 0 0 22px; text-align: center; font-size: 1.15rem; font-weight: 500; letter-spacing: .04em; opacity: .9; }
  .atv-cena .cartao-form label { display: block; margin-bottom: 14px; font-size: .75rem; font-weight: 500; color: rgba(255,255,255,.75); }
  .atv-cena .cartao-form input { display: block; width: 100%; box-sizing: border-box; margin-top: 2px; padding: 9px 2px; border: 0; border-bottom: 1px solid rgba(255,255,255,.45); border-radius: 0; font-size: .95rem; color: #fff; background: transparent; outline: none; text-shadow: none;
    transition: border-color .25s, box-shadow .25s; }
  .atv-cena .cartao-form input:focus { border-bottom-color: #fff; box-shadow: 0 1px 0 0 #fff; }
  .atv-cena .cartao-form input:-webkit-autofill { -webkit-text-fill-color: #fff; transition: background-color 9999s; }
  .atv-cena .cartao-form button { display: block; margin: 20px auto 0; padding: 9px 30px; border: 1px solid rgba(255,255,255,.5); border-radius: 999px; font-size: .85rem; font-weight: 500; color: #fff; background: rgba(255,255,255,.08); cursor: pointer; text-shadow: none;
    backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); transition: background .25s, color .25s, border-color .25s; }
  .atv-cena .cartao-form button:hover, .atv-cena .cartao-form button:focus-visible { background: #fff; color: #000; border-color: #fff; outline: none; }
  .atv-cena .cartao-form p { text-align: center; margin: 16px 0 0; font-size: .75rem; color: rgba(255,255,255,.7); }
  .atv-cena .cartao-form a { color: #fff; font-weight: 600; text-decoration: none; }
  .atv-cena .cartao-form a:hover { text-decoration: underline; }
  .atv-cena .cartao-form .erro { margin: 0 0 14px; padding: 8px 10px; border-radius: 8px; text-align: center; font-size: .8rem; color: #ffd9d6; background: rgba(255,69,58,.28); }

  @media (prefers-reduced-motion: reduce) { .atv-fundo img { animation: none; } .atv-cena input, .atv-cena button { transition: none; } }
</style>

<a class="atv-inicio" href="<?= h(BASE) ?>/index.php">Tela de início</a>

<div class="atv-fundo" aria-hidden="true">
<?php foreach ($imgs as $i => $src): ?><img src="<?= h($src) ?>" alt="" class="<?= $i === 0 ? 'on' : '' ?>" onerror="this.remove()"><?php endforeach; ?>
</div>

<main class="atv-cena">
  <form class="cartao-form" method="POST" data-validar>
    <?= csrfCampo() ?>
    <h2>Entrar</h2>
    <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <label>E-mail <input type="email" name="email" autocomplete="email" required></label>
    <label>Senha <input type="password" name="senha" autocomplete="current-password" required></label>
    <button>Entrar</button>
    <p><a href="recuperar.php">Esqueceu a senha?</a></p>
    <p>Ainda não tem conta? <a href="registro.php">Cadastre-se</a></p>
  </form>
</main>
<script>
(function () {
  var box = document.querySelector('.atv-fundo');
  if (!box) return;
  var i = 0;
  setInterval(function () {
    var imgs = box.querySelectorAll('img');
    if (imgs.length < 2) return;
    imgs[i % imgs.length].classList.remove('on');
    i = (i + 1) % imgs.length;
    imgs[i].classList.add('on');
  }, 8000);
})();
</script>
<script src="<?= h(BASE) ?>/admin/public/js/validacao.js" defer></script>
<?php rodape();
