<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/admin/models/games.php';
$u = usuario();
require_once __DIR__ . '/admin/config/biblioteca.php';
$comprados = ($u && $u['tipo'] !== 'admin') ? idsComprados($pdo, (int)$u['id']) : [];

function capa($g) { return !empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : ''; }
function card($g) {
    global $vend, $u, $comprados;
    $ehAdmin = $u && $u['tipo'] === 'admin';
    $img = capa($g); $esg = (int)$g['estoque'] <= 0;
    $tag = 'a';  // todo card abre a página do jogo (inclusive esgotados, que mostram "Indisponível")
    $href = ' href="' . BASE . '/cliente/jogo.php?id=' . (int)$g['id'] . '"';
    echo "<$tag class='card" . ($esg ? ' esgotado' : '') . "'$href>";
    echo '<div class="capa"><span class="ini">' . h(mb_substr($g['titulo'], 0, 1)) . '</span>';
    if ($img) echo '<img src="' . h($img) . '" alt="" loading="lazy" onerror="this.remove()">';
    if ($ehAdmin) echo '<span class="selos"><b>Estoque ' . (int)$g['estoque'] . '</b><b>Vendidos ' . ($vend[(int)$g['id']] ?? 0) . '</b></span>';
    if ($esg) echo '<em>Esgotado</em>';
    echo relogioPreVenda($g);
    echo seloComprado($g['id'], $comprados);
    echo '</div>';
    if (!empty($g['categoria'])) echo '<p class="rot">' . h($g['categoria']) . '</p>';
    echo '<h3>' . h($g['titulo']) . '</h3><p class="preco">' . moeda($g['preco']) . '</p>' . estreiaHtml($g) . '</' . $tag . '>';
}

$vend = [];
if ($u && $u['tipo'] === 'admin') try {
    $vend = Game::vendidosPorJogo();
} catch (Throwable $e) { /* tabela pedidos ainda não existe */ }

$recentes  = $pdo->query(Game::SQL_SELECT . ' ORDER BY g.id DESC LIMIT 12')->fetchAll();
$destaques = array_slice(array_values(array_filter($recentes, fn($g) => $g['estoque'] > 0)), 0, 4);
$jogos     = array_slice($recentes, 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Loja de jogos</title>
  <style>
:root{--tx:#1d1d1f;--mt:rgba(29,29,31,.6);--px:clamp(16px,5vw,90px);--hb:clamp(420px,37vw,580px);--vidro:rgba(0,0,0,.06);color-scheme:light}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:80px}
::selection{background:rgba(0,0,0,.14)}
body{margin:0;color:var(--tx);font:16px/1.5 -apple-system,BlinkMacSystemFont,"SF Pro Display","Segoe UI",system-ui,sans-serif;background:#fff;min-height:100vh;overflow-x:clip;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid #1d1d1f;outline-offset:3px}

/* barra de navegação: vidro escuro */
.topo{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:22px;padding:14px var(--px);background:rgba(255,255,255,.72);backdrop-filter:blur(30px) saturate(1.8);-webkit-backdrop-filter:blur(30px) saturate(1.8);border-bottom:1px solid rgba(0,0,0,.08)}
.topo::before{content:""}
.menu{display:flex;gap:26px;flex-wrap:wrap;justify-content:center;font-size:14.5px;font-weight:500;color:var(--mt)}
.menu a{transition:color .2s}.menu a:hover{color:#000}
.conta{justify-self:end;display:flex;gap:16px;align-items:center;font-size:14.5px;font-weight:500;color:var(--mt)}.conta a{transition:color .2s}.conta a:hover{color:#000}
.conta .destaque{background:#1d1d1f;color:#fff;padding:7px 18px;border-radius:99px;font-weight:600;transition:transform .2s,box-shadow .2s}
.conta .destaque:hover{color:#fff;background:#000;transform:scale(1.05);box-shadow:0 10px 24px -8px rgba(0,0,0,.4)}
main{padding:0 var(--px) 56px}
.aviso{margin:18px 0 0;padding:12px 18px;border-radius:16px;background:rgba(0,0,0,.05);box-shadow:inset 0 0 0 1px rgba(0,0,0,.08)}

/* ===== CARROSSEL ESTILO APPLE TV: cartões grandes com cantos arredondados, vizinhos espiando nas laterais ===== */
.rasgo{position:relative;margin:10px calc(var(--px) * -1) 0}
.hero{--w:84%;--l:8%;--gap:18px;position:relative;height:var(--hb);overflow-x:clip;touch-action:pan-y}
.slide{--o:0;--sc:1;position:absolute;top:0;bottom:0;left:var(--l);width:var(--w);border-radius:22px;overflow:hidden;background:#111;
  box-shadow:0 30px 60px -32px rgba(0,0,0,.55);
  transform:translateX(calc(var(--o) * (100% + var(--gap)))) scale(var(--sc));
  transition:transform .8s cubic-bezier(.32,.72,0,1),opacity .6s,visibility .6s}
.slide:not(.ativo){--sc:.94;cursor:pointer}
.slide.longe{opacity:0;visibility:hidden;pointer-events:none}
.slide.salto{transition:opacity .6s,visibility .6s}
.hero:not(.pronto) .slide:not(.ativo){visibility:hidden}
/* vizinhos ficam escurecidos */
.slide::after{content:"";position:absolute;inset:0;z-index:3;pointer-events:none;background:#000;opacity:0;transition:opacity .6s}
.slide:not(.ativo)::after{opacity:.42}
.banner{position:absolute;inset:0;display:block;overflow:hidden;background:linear-gradient(135deg,#2b3150,#0b0d14)}
.ini{position:absolute;inset:0;display:grid;place-items:center;font:700 140px -apple-system,BlinkMacSystemFont,"SF Pro Display","Segoe UI",system-ui,sans-serif;color:rgba(255,255,255,.08)}
.arte{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 30%;transition:transform 1.2s cubic-bezier(.2,.7,.2,1)}
.slide.ativo:hover .arte{transform:scale(1.035)}
.faixa{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;gap:10px;padding:clamp(22px,3.4vw,52px) clamp(22px,3.4vw,52px) 68px;
  background:linear-gradient(to top,rgba(0,0,0,.8) 0%,rgba(0,0,0,.38) 38%,rgba(0,0,0,0) 64%);color:#fff;pointer-events:none;transition:opacity .4s}
.slide:not(.ativo) .faixa{opacity:0}
.slide:not(.ativo) .faixa .btn{pointer-events:none}
.slide.ativo .faixa>*{animation:entra .9s .2s cubic-bezier(.2,.7,.2,1) both}
@keyframes entra{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
.faixa>div:first-child{min-width:0;max-width:640px}
.faixa .rot{margin:0 0 8px;font-size:13px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.faixa h1{margin:0;font-size:clamp(30px,4.2vw,58px);font-weight:700;letter-spacing:-.025em;line-height:1.05;color:#fff;text-shadow:0 2px 24px rgba(0,0,0,.35)}
.faixa .desc{margin:0;max-width:520px;font-size:clamp(14px,1.2vw,17px);line-height:1.45;color:rgba(255,255,255,.84);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.faixa .acao{display:flex;align-items:center;gap:18px;margin-top:8px}
.preco-faixa{font-size:16px;font-weight:600;color:rgba(255,255,255,.92)}
.faixa .btn{pointer-events:auto;margin:0;padding:11px 28px;font-size:15px}
.btn{display:inline-block;background:#fff;color:#000;padding:12px 30px;border-radius:99px;font-weight:600;white-space:nowrap;transition:transform .25s cubic-bezier(.2,.8,.2,1),box-shadow .25s,background .25s}
.btn:hover,.btn:focus-visible{transform:scale(1.06);box-shadow:0 16px 34px -10px rgba(255,255,255,.45)}
.btn.vidro{background:var(--vidro);color:#1d1d1f;box-shadow:inset 0 0 0 1px rgba(0,0,0,.1)}
.btn.vidro:hover,.btn.vidro:focus-visible{background:#1d1d1f;color:#fff;box-shadow:0 16px 34px -10px rgba(0,0,0,.4)}
/* indicadores: cápsula de vidro com bolinhas */
.pontos{position:absolute;left:50%;bottom:20px;transform:translateX(-50%);display:flex;align-items:center;gap:8px;z-index:4;padding:8px 12px;border-radius:99px;background:rgba(40,40,44,.45);backdrop-filter:blur(18px) saturate(1.6);-webkit-backdrop-filter:blur(18px) saturate(1.6)}
.pontos button{width:7px;height:7px;border:0;border-radius:99px;background:rgba(255,255,255,.5);cursor:pointer;padding:0;transition:width .35s cubic-bezier(.32,.72,0,1),background .3s}
.pontos button.on{width:22px;background:#fff}

/* prateleira de jogos */
.bloco{margin-top:48px}
.bloco h2{font-size:28px;font-weight:700;letter-spacing:-.02em;margin:0 0 20px}
.vazio{color:var(--mt)}
.grade,.fileira{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:34px 22px}
.fileira{grid-template-columns:repeat(5,1fr)}
@media(max-width:1100px){.fileira{grid-template-columns:repeat(3,1fr)}}
.card{display:block}
.capa{--rx:0deg;--ry:0deg;--s:1;--gx:50%;--gy:50%;position:relative;aspect-ratio:4/5;border-radius:20px;overflow:hidden;background:linear-gradient(135deg,#2b3150,#0b0d14);
  box-shadow:0 12px 26px -14px rgba(0,0,0,.4),inset 0 0 0 1px rgba(0,0,0,.06);transform:perspective(900px) rotateX(var(--rx)) rotateY(var(--ry)) scale(var(--s));transition:transform .4s cubic-bezier(.2,.8,.2,1),box-shadow .4s;will-change:transform}
a.card:hover .capa,a.card:focus-visible .capa{--s:1.06;z-index:2;box-shadow:0 30px 50px -18px rgba(0,0,0,.5),0 0 0 1px rgba(0,0,0,.1)}
.capa::after{content:"";position:absolute;inset:0;z-index:2;pointer-events:none;background:radial-gradient(circle at var(--gx) var(--gy),rgba(255,255,255,.38),transparent 55%);mix-blend-mode:soft-light;opacity:0;transition:opacity .3s}
a.card:hover .capa::after{opacity:1}
.capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.capa .ini{font-size:64px}
.capa em{position:absolute;left:10px;top:10px;z-index:3;background:rgba(0,0,0,.5);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);color:#fff;padding:3px 11px;border-radius:99px;font-size:12px;font-weight:600;font-style:normal}
.selos{position:absolute;left:10px;bottom:10px;display:flex;gap:6px;flex-wrap:wrap;z-index:3}
.selos b{background:rgba(0,0,0,.55);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);color:#fff;border-radius:99px;padding:2px 10px;font-size:11px;line-height:18px;font-weight:600}
.rot{margin:12px 0 0;font-size:11px;font-weight:600;letter-spacing:.08em;color:rgba(29,29,31,.5);text-transform:uppercase}
.card h3{font-size:15.5px;font-weight:600;line-height:1.35;margin:3px 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.preco{margin:0;font-size:14px;color:var(--mt)}
.esgotado{opacity:.5}
<?= cssPreVenda() ?>
.capa .comprado{position:absolute;right:10px;top:10px;z-index:3;background:rgba(18,120,60,.92);color:#fff;padding:3px 11px;border-radius:99px;font-size:12px;font-weight:600;box-shadow:0 2px 10px rgba(0,0,0,.35)}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:26px var(--px);border-top:1px solid rgba(0,0,0,.1);color:var(--mt);font-size:14px}
.rodape strong{color:var(--tx);font-weight:600}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(max-width:700px){.hero{--w:90%;--l:5%;--gap:12px}.slide{border-radius:18px}.faixa{padding:18px 18px 62px}.grade,.fileira{grid-template-columns:repeat(2,1fr);gap:24px 14px}.capa{border-radius:16px}}
@media(prefers-reduced-motion:reduce){a.card:hover .capa{--s:1}.capa,.slide,.btn{transition:none}.slide{transition:none!important}.arte{transition:none}.slide.ativo .faixa>*{animation:none}html{scroll-behavior:auto}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <?php $admin = $u && $u['tipo'] === 'admin'; $cliente = $u && !$admin; $nCarrinho = array_sum($_SESSION['carrinho'] ?? []); ?>
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/index.php#novidades">Novidades</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <?php if ($admin): ?>
      <a href="<?= BASE ?>/admin/views/gerenciar_games.php">Gerenciar jogos</a>
      <a href="<?= BASE ?>/admin/views/ver_usuarios.php">Gerenciar usuários</a>
      <a href="<?= BASE ?>/admin/views/ver_clientes.php">Clientes</a>
      <a href="<?= BASE ?>/admin/views/ver_categorias.php">Categorias</a>
      <a href="<?= BASE ?>/admin/views/dashboard.php">Dashboard</a>
      <a href="<?= BASE ?>/admin/views/pedidos.php">Pedidos</a>
    <?php elseif ($cliente): ?>
      <a href="<?= BASE ?>/cliente/carrinho.php">Carrinho<?= $nCarrinho ? ' (' . $nCarrinho . ')' : '' ?></a>
      <a href="<?= BASE ?>/cliente/lista_desejos.php">Lista de desejos</a>
      <a href="<?= BASE ?>/cliente/meus_jogos.php">Meus jogos</a>
      <a href="<?= BASE ?>/cliente/meus_pedidos.php">Meus pedidos</a>
      <a href="<?= BASE ?>/cliente/conta.php">Conta</a>
    <?php endif; ?>
  </nav>
  <div class="conta">
    <?php if (!$u): ?>
      <a href="<?= BASE ?>/cliente/carrinho.php">Carrinho<?= $nCarrinho ? ' (' . $nCarrinho . ')' : '' ?></a>
      <a href="<?= BASE ?>/login.php">Entrar</a><a class="destaque" href="<?= BASE ?>/registro.php">Criar conta</a>
    <?php else: ?>
      <span><?= h($u['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a>
    <?php endif; ?>
  </div>
</header>

<main>
<?php if ($m = flash()): ?><p class="aviso"><?= h($m) ?></p><?php endif; ?>
  <?php if ($destaques): ?>
  <div class="rasgo">
  <section class="hero" aria-label="Destaques">
    <?php foreach ($destaques as $i => $g): $img = capa($g); $lnk = BASE . '/cliente/jogo.php?id=' . (int)$g['id']; ?>
      <article class="slide<?= $i === 0 ? ' ativo' : '' ?>">
        <a class="banner" href="<?= $lnk ?>" aria-label="<?= h($g['titulo']) ?>">
          <span class="ini"><?= h(mb_substr($g['titulo'], 0, 1)) ?></span>
          <?php if ($img): ?>
            <img class="arte" src="<?= h($img) ?>" alt="" onerror="this.remove()">
          <?php endif; ?>
        </a>
        <div class="faixa">
          <div>
            <?php if (!empty($g['categoria'])): ?><p class="rot"><?= h($g['categoria']) ?></p><?php endif; ?>
            <h1><?= h($g['titulo']) ?></h1>
          </div>
          <?php $desc = trim(strip_tags($g['descricao'] ?? '')); if ($desc !== ''): ?>
            <p class="desc"><?= h(mb_strimwidth($desc, 0, 220, '...')) ?></p>
          <?php endif; ?>
          <div class="acao">
            <a class="btn" href="<?= $lnk ?>">Ver jogo</a>
            <span class="preco-faixa"><?= moeda($g['preco']) ?></span>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (count($destaques) > 1): ?>
    <div class="pontos"><?php foreach ($destaques as $i => $g): ?><button type="button" aria-label="Destaque <?= $i + 1 ?>" class="<?= $i === 0 ? 'on' : '' ?>"></button><?php endforeach; ?></div>
    <?php endif; ?>
  </section>
  </div>
  <?php endif; ?>
  <section class="bloco" id="novidades">
    <h2>Novidades</h2>
    <?php if (!$jogos): ?><p class="vazio">Ainda não há jogos cadastrados.</p><?php endif; ?>
    <div class="fileira"><?php foreach ($jogos as $g) card($g); ?></div>
    <p style="text-align:center;margin:28px 0 0"><a class="btn vidro" href="<?= BASE ?>/catalogo.php">Ver catálogo completo</a></p>
  </section>
</main>

<footer class="rodape">
  <strong>Lucas_Oliveira</strong>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>

<script>
let pararCarrossel = () => {};
function carrossel() {
  pararCarrossel();
  const hero = document.querySelector('.hero');
  if (!hero) return;
  const slides = [...hero.querySelectorAll('.slide')], pts = [...hero.querySelectorAll('.pontos button')], n = slides.length;
  const reduz = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const meio = Math.floor(n / 2);
  let atual = 0, timer, arrastou = false, x0 = 0;
  const prev = slides.map(() => null);

  // posiciona cada cartão pelo deslocamento em relação ao atual (-1 = esquerda, 0 = centro, 1 = direita)
  const posicionar = () => {
    slides.forEach((s, i) => {
      const o = n === 2 ? 0 : ((i - atual + n + meio) % n) - meio;
      const salto = prev[i] === null || Math.abs(o - prev[i]) > 1;   // volta pelo outro lado: sem deslizar atravessado
      if (salto) s.classList.add('salto');
      s.style.setProperty('--o', o);
      s.classList.toggle('ativo', i === atual);
      s.classList.toggle('longe', Math.abs(o) > 1);
      if (salto) setTimeout(() => s.classList.remove('salto'), 80);
      prev[i] = o;
    });
    pts.forEach((p, i) => p.classList.toggle('on', i === atual));
    hero.classList.add('pronto');
  };

  const ir = k => { atual = (k + n) % n; posicionar(); };
  const iniciar = () => { clearInterval(timer); if (!reduz && n > 1) timer = setInterval(() => ir(atual + 1), 6500); };

  posicionar();
  if (n < 2) return;

  pts.forEach((p, i) => p.addEventListener('click', () => { ir(i); iniciar(); }));
  // clicar num cartão vizinho o traz para o centro (o cartão central continua abrindo o jogo)
  slides.forEach((s, i) => s.addEventListener('click', e => {
    if (arrastou) { e.preventDefault(); arrastou = false; return; }
    if (i !== atual) { e.preventDefault(); ir(i); iniciar(); }
  }, true));
  hero.addEventListener('mouseenter', () => clearInterval(timer));
  hero.addEventListener('mouseleave', iniciar);
  // deslizar com o dedo
  hero.addEventListener('pointerdown', e => { if (e.pointerType === 'touch') x0 = e.clientX; });
  hero.addEventListener('pointerup', e => {
    if (e.pointerType !== 'touch') return;
    const dx = e.clientX - x0;
    if (Math.abs(dx) > 50) { arrastou = true; ir(atual + (dx < 0 ? 1 : -1)); iniciar(); setTimeout(() => arrastou = false, 400); }
  });
  hero.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') { ir(atual + 1); iniciar(); }
    if (e.key === 'ArrowLeft') { ir(atual - 1); iniciar(); }
  });
  iniciar();
  pararCarrossel = () => clearInterval(timer);
}
carrossel();

// efeito Apple TV nos cards: inclina e brilha seguindo o mouse
(function () {
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  document.querySelectorAll('a.card .capa').forEach(function (c) {
    c.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse') return;
      var r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      c.style.transition = 'transform .08s linear, box-shadow .4s';
      c.style.setProperty('--ry', ((x - .5) * 12) + 'deg');
      c.style.setProperty('--rx', ((.5 - y) * 12) + 'deg');
      c.style.setProperty('--gx', (x * 100) + '%');
      c.style.setProperty('--gy', (y * 100) + '%');
    });
    c.addEventListener('pointerleave', function () {
      c.style.transition = '';
      c.style.setProperty('--rx', '0deg'); c.style.setProperty('--ry', '0deg');
    });
  });
})();
</script>
<?php require __DIR__ . '/cliente/_assistente_widget.php'; ?>
</body>
</html>
