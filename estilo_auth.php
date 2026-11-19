<?php
/* Visual compartilhado das telas de senha (mesmo estilo do login/registro).
   Uso: auth_topo('Título'); ...<form class="cartao-form">...</form>...; auth_rodape(); */

function auth_topo(string $titulo): void {
    $pasta = __DIR__ . '/admin/public/imagens';
    $imgs = [];
    if (is_dir($pasta)) {
        foreach (scandir($pasta) as $f) {
            if (preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $f)) $imgs[] = BASE . '/admin/public/imagens/' . rawurlencode($f);
        }
    }
    shuffle($imgs);
    topo($titulo);
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

  .atv-cena .cartao-form .ok { margin: 0 0 14px; padding: 8px 10px; border-radius: 8px; text-align: center; font-size: .8rem; color: #d9ffe3; background: rgba(48,209,88,.28); }
  .atv-cena .cartao-form .dica { margin: 10px 0 0; font-size: .72rem; line-height: 1.5; word-break: break-all; color: rgba(255,255,255,.8); }
  .atv-cena .cartao-form .dica a { text-decoration: underline; }

</style>

<a class="atv-inicio" href="<?= h(BASE) ?>/index.php">Tela de início</a>

<div class="atv-fundo" aria-hidden="true">
<?php foreach ($imgs as $i => $src): ?><img src="<?= h($src) ?>" alt="" class="<?= $i === 0 ? 'on' : '' ?>" onerror="this.remove()"><?php endforeach; ?>
</div>

<main class="atv-cena">
<?php
}

function auth_rodape(): void {
    ?>
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
<?php
    rodape();
}

/* CSRF simples para formulários de quem já está logado */
function auth_csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function auth_csrf_ok(): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));
}

/* A tabela recuperacao_senha é criada pelo sql/script.sql (nenhum DDL é executado pelo PHP). */
function auth_tabela_recuperacao(PDO $pdo): void {
    // mantida só por compatibilidade com recuperar.php / redefinir.php
}
