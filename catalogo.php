<?php
require_once 'admin/config/auth.php';
require_once __DIR__ . '/admin/models/games.php';
$u = usuario();
require_once __DIR__ . '/admin/config/biblioteca.php';
$comprados = ($u && $u['tipo'] !== 'admin') ? idsComprados($pdo, (int)$u['id']) : [];   // jogos que o cliente já comprou
$busca  = trim($_GET['buscar'] ?? '');
$filtro = trim($_GET['filtro'] ?? '');   // um único filtro: ord:<chave> | pr:<chave> | cat:<gênero>
$ordens = [
    'recentes' => ['Mais recentes', 'g.id DESC'],
    'antigos'  => ['Mais antigos',  'g.id ASC'],
    'menor'    => ['Menor preço',   'preco ASC, titulo'],
    'maior'    => ['Maior preço',   'preco DESC, titulo'],
    'nome'     => ['Nome (A-Z)',    'titulo'],
];
$faixas = [   // [rótulo, mínimo, máximo]
    'ate50'   => ['Até R$ 50',        null, 50],
    '50a100'  => ['R$ 50 a R$ 100',   50,   100],
    '100a200' => ['R$ 100 a R$ 200',  100,  200],
    '200mais' => ['Acima de R$ 200',  200,  null],
];
$cat = $ordem = $faixa = '';
if (strncmp($filtro, 'cat:', 4) === 0) $cat = substr($filtro, 4);
elseif (strncmp($filtro, 'ord:', 4) === 0 && isset($ordens[substr($filtro, 4)])) $ordem = substr($filtro, 4);
elseif (strncmp($filtro, 'pr:', 3) === 0 && isset($faixas[substr($filtro, 3)])) $faixa = substr($filtro, 3);
$filtrando = $busca !== '' || $cat !== '' || $ordem !== '' || $faixa !== '';

function capa($g) { return !empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : ''; }
function realce($t) {
    global $busca;
    $t = h($t);
    return $busca === '' ? $t : preg_replace('/(' . preg_quote(h($busca), '/') . ')/iu', '<mark>$1</mark>', $t) ?? $t;
}
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
    echo '<h3>' . realce($g['titulo']) . '</h3><p class="preco">' . moeda($g['preco']) . '</p>' . estreiaHtml($g) . '</' . $tag . '>';
}

$vend = [];
if ($u && $u['tipo'] === 'admin') try {
    $vend = Game::vendidosPorJogo();
} catch (Throwable $e) { /* tabela pedidos ainda não existe */ }

$categorias = Game::categoriasComJogos();

$sql = Game::SQL_SELECT . ' WHERE g.titulo LIKE ?'; $args = ['%' . $busca . '%'];
if ($cat !== '') { $sql .= ' AND c.nome = ?'; $args[] = $cat; }
if ($faixa !== '') {
    [, $fmin, $fmax] = $faixas[$faixa];
    if ($fmin !== null) { $sql .= ' AND preco >= ?'; $args[] = $fmin; }
    if ($fmax !== null) { $sql .= ' AND preco <= ?'; $args[] = $fmax; }
}
$st = $pdo->prepare($sql . ' ORDER BY ' . ($ordens[$ordem][1] ?? 'titulo')); $st->execute($args);
$resultados = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Catálogo | Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--navy:#0d1b4c;--bd:#dfe3ee;--px:clamp(16px,5vw,90px);--hb:clamp(220px,30vw,500px)}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:80px}
body{margin:0;color:var(--tx);font:16px/1.5 "Segoe UI",system-ui,sans-serif;background:#fff;min-height:100vh}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid var(--ac);outline-offset:2px}
.topo{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:22px;padding:14px var(--px);background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid var(--bd)}
.topo::before{content:""}
.menu{display:flex;gap:26px;color:var(--mt);flex-wrap:wrap;justify-content:center}.menu a:hover{color:var(--ac)}
.conta{justify-self:end;display:flex;gap:14px;align-items:center;color:var(--mt)}.conta a:hover{color:var(--ac)}
.conta .destaque{background:var(--ac);color:#fff;padding:7px 16px;border-radius:99px;font-weight:600}
.conta .destaque:hover{background:var(--ac2);color:#fff}
.busca{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;align-items:center;margin:22px auto 0;max-width:480px}
.busca input,.busca select{min-width:0;padding:7px 14px;border:1px solid var(--bd);border-radius:99px;font:inherit;font-size:14px;color:var(--tx);background:#fff}
.campo{position:relative;flex:1 1 220px;display:flex}
.campo input{width:100%;text-align:center;padding-right:34px;padding-left:34px}
.campo::after{content:"";position:absolute;right:13px;top:50%;width:14px;height:14px;margin-top:-7px;border:2px solid var(--bd);border-top-color:var(--ac);border-radius:50%;opacity:0;transition:opacity .15s;animation:gira .7s linear infinite}
.buscando .campo::after{opacity:1}
@keyframes gira{to{transform:rotate(360deg)}}
.grade .card{animation:surge .45s cubic-bezier(.2,.7,.2,1) calc(var(--i,0) * 45ms) backwards}
@keyframes surge{from{opacity:0;transform:translateY(18px) scale(.96)}}
mark{background:#cfe6ff;color:inherit;border-radius:3px;padding:0 2px}
@media(prefers-reduced-motion:reduce){.grade .card,.campo::after{animation:none}a.card:hover .capa{transform:none}}
.busca select{flex:0 1 150px;cursor:pointer}
.busca input:focus,.busca select:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
#conteudo{transition:opacity .15s}#conteudo.carregando{opacity:.45;transition:opacity .2s}
main{padding:0 var(--px) 48px}

/* jogos em grade, no estilo da loja */
.bloco{margin-top:40px}
.bloco h2{font-size:26px;font-weight:600;margin:0 0 18px}
.vazio{color:var(--mt)}
.grade,.fileira{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:30px 22px}
.fileira{grid-template-columns:repeat(5,1fr)}
@media(max-width:1100px){.fileira{grid-template-columns:repeat(3,1fr)}}
.card{display:block}
.capa{position:relative;aspect-ratio:1/1;border-radius:10px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45);transition:transform .25s cubic-bezier(.2,.8,.3,1),box-shadow .25s}
a.card:hover .capa{box-shadow:0 0 0 3px var(--ac),0 22px 36px -10px rgba(13,27,76,.55);transform:scale(1.08);z-index:2}
.capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.capa .ini{font-size:64px}
.capa em{position:absolute;left:8px;top:8px;background:#000b;color:#fff;padding:2px 10px;border-radius:99px;font-size:13px;font-style:normal}
.rot{margin:8px 0 0;font-size:12px;font-weight:700;color:var(--mt);text-transform:uppercase}
.card h3{font-size:17px;font-weight:400;line-height:1.5;margin:2px 0 6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.preco{margin:0;font-size:17px;color:#4a5063}
<?= cssPreVenda() ?>
.capa .comprado{position:absolute;right:10px;top:10px;z-index:3;background:rgba(18,120,60,.92);color:#fff;padding:3px 11px;border-radius:99px;font-size:12px;font-weight:600;font-style:normal;box-shadow:0 2px 10px rgba(0,0,0,.35)}
.selos{position:absolute;left:10px;bottom:10px;display:flex;gap:6px;flex-wrap:wrap;z-index:1}
.selos b{background:#fff;color:#111;border:1px solid #111;border-radius:3px;padding:0 6px;font-size:12px;line-height:18px;font-weight:700}
.esgotado{opacity:.55}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape strong{color:var(--tx)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(max-width:700px){.faixa{flex-wrap:wrap;gap:10px;padding-top:60px}.faixa h1{font-size:22px}.grade,.fileira{grid-template-columns:repeat(2,1fr);gap:22px 14px}}
@media(prefers-reduced-motion:reduce){.slide{transition:none}.hero::after{display:none}.slide.ativo .faixa>*{animation:none}html{scroll-behavior:auto}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <?php $admin = $u && $u['tipo'] === 'admin'; $cliente = $u && !$admin; $nCarrinho = array_sum($_SESSION['carrinho'] ?? []); ?>
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php" style="color:var(--ac);font-weight:600">Catálogo</a>
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
      <a href="<?= BASE ?>/cliente/meus_pedidos.php">Meus pedidos</a>
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
<?php if ($m = flash()): ?><p style="margin:16px 0 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1"><?= h($m) ?></p><?php endif; ?>
<form class="busca" id="filtros" method="get" action="<?= BASE ?>/catalogo.php" role="search">
  <div class="campo"><input type="search" name="buscar" value="<?= h($busca) ?>" placeholder="Buscar jogo por nome" aria-label="Buscar jogo por nome" autocomplete="off"></div>
  <select name="filtro" aria-label="Filtrar">
    <option value="">Filtrar por…</option>
    <optgroup label="Ordenar">
      <?php foreach ($ordens as $k => $o): ?><option value="ord:<?= $k ?>"<?= $ordem === $k ? ' selected' : '' ?>><?= h($o[0]) ?></option><?php endforeach; ?>
    </optgroup>
    <optgroup label="Preço">
      <?php foreach ($faixas as $k => $f): ?><option value="pr:<?= $k ?>"<?= $faixa === $k ? ' selected' : '' ?>><?= h($f[0]) ?></option><?php endforeach; ?>
    </optgroup>
    <optgroup label="Gênero">
      <?php foreach ($categorias as $c): ?><option value="cat:<?= h($c) ?>"<?= $cat === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
    </optgroup>
  </select>
</form>
<div id="conteudo">
  <section class="bloco" id="catalogo">
    <h2><?= $filtrando ? 'Resultados' . ($cat !== '' ? ' em ' . h($cat) : '') . ($busca !== '' ? ' para “' . h($busca) . '”' : '') : 'Todos os jogos' ?></h2>
    <?php if (!$resultados): ?><p class="vazio"><?= $filtrando ? 'Nenhum jogo encontrado. Tente outro nome ou filtro.' : 'Ainda não há jogos cadastrados.' ?></p><?php endif; ?>
    <div class="grade"><?php foreach ($resultados as $g) card($g); ?></div>
  </section>
</div>
</main>

<footer class="rodape">
  <strong>Lucas_Oliveira</strong>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>

<script>
// busca e filtro ao vivo: atualiza a lista enquanto digita ou ao trocar o filtro, sem recarregar a página
(function () {
  const form = document.getElementById('filtros'), alvo = document.getElementById('conteudo');
  let espera, ctrl;
  const animar = () => alvo.querySelectorAll('.grade .card').forEach((c, i) => c.style.setProperty('--i', Math.min(i, 14)));
  animar();
  const atualizar = async () => {
    const qs = new URLSearchParams();
    for (const [k, v] of new FormData(form)) if (v.trim() !== '') qs.set(k, v.trim());
    const url = form.getAttribute('action') + (qs.toString() ? '?' + qs : '');
    history.replaceState(null, '', url);
    if (ctrl) ctrl.abort();
    const meu = ctrl = new AbortController();
    alvo.classList.add('carregando'); form.classList.add('buscando');
    try {
      const r = await fetch(url, { signal: meu.signal });
      const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
      const novo = doc.getElementById('conteudo');
      if (novo) { alvo.innerHTML = novo.innerHTML; animar(); }
    } catch (e) { if (e.name !== 'AbortError') console.error(e); }
    if (ctrl === meu) { alvo.classList.remove('carregando'); form.classList.remove('buscando'); }
  };
  form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(espera); atualizar(); });
  form.elements.buscar.addEventListener('input', () => { clearTimeout(espera); espera = setTimeout(atualizar, 250); });
  form.elements.filtro.addEventListener('change', () => { clearTimeout(espera); atualizar(); });
})();
</script>
<?php require __DIR__ . '/cliente/_assistente_widget.php'; ?>
</body>
</html>
