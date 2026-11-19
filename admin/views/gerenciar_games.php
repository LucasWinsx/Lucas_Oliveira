<?php
require_once '../config/auth.php';
require_once '../models/games.php';
exigirAdmin();
$u = usuario();

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
    global $vend;
    $id  = (int)$g['id'];
    $img = capa($g); $esg = (int)$g['estoque'] <= 0;
    echo "<article class='card" . ($esg ? ' esgotado' : '') . "'>";
    echo '<div class="capa"><span class="ini">' . h(mb_substr($g['titulo'], 0, 1)) . '</span>';
    if ($img) echo '<img src="' . h($img) . '" alt="" loading="lazy" onerror="this.remove()">';
    echo '<span class="selos"><b>Estoque ' . (int)$g['estoque'] . '</b><b>Vendidos ' . ($vend[$id] ?? 0) . '</b></span>';
    if ($esg) echo '<em>Esgotado</em>';
    echo '</div>';
    if (!empty($g['categoria'])) echo '<p class="rot">' . h($g['categoria']) . '</p>';
    echo '<h3>' . realce($g['titulo']) . '</h3><p class="preco">' . moeda($g['preco']) . '</p>';
    echo '<div class="botoes"><a class="editar" href="editar_game.php?id=' . $id . '">Editar</a>'
       . '<a class="excluir" href="#" onclick="return excluir(\'' . h(addslashes($g['titulo'])) . '\', ' . $id . ')">Excluir</a></div>';
    echo '</article>';
}

// quantidade vendida por jogo (pedidos não cancelados)
$vend = [];
try {
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
  <title>Gerenciar jogos | Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--navy:#0d1b4c;--bd:#dfe3ee;--px:clamp(16px,5vw,90px)}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:80px}
body{margin:0;color:var(--tx);font:16px/1.5 "Segoe UI",system-ui,sans-serif;background:#fff;min-height:100vh}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid var(--ac);outline-offset:2px}
.topo{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:22px;padding:14px var(--px);background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid var(--bd)}
.topo::before{content:""}
.menu{display:flex;gap:26px;color:var(--mt);flex-wrap:wrap;justify-content:center}.menu a:hover{color:var(--ac)}
.conta{justify-self:end;display:flex;gap:14px;align-items:center;color:var(--mt)}.conta a:hover{color:var(--ac)}
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
@media(prefers-reduced-motion:reduce){.grade .card,.campo::after{animation:none}.capa{transition:none}html{scroll-behavior:auto}}
.busca select{flex:0 1 150px;cursor:pointer}
.busca input:focus,.busca select:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.acoes{display:flex;justify-content:center;margin:14px 0 0}
.btn-add{background:var(--ac);color:#fff;padding:8px 22px;border-radius:99px;font-size:14px;font-weight:600;transition:background .15s}
.btn-add:hover{background:var(--ac2)}
#conteudo{transition:opacity .15s}#conteudo.carregando{opacity:.45;transition:opacity .2s}
main{padding:0 var(--px) 48px}

.bloco{margin-top:40px}
.bloco h2{font-size:26px;font-weight:600;margin:0 0 18px}
.vazio{color:var(--mt)}
.grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:30px 22px}
.card{display:flex;flex-direction:column;min-width:0}
.capa{position:relative;aspect-ratio:1/1;border-radius:10px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45)}
.capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.capa .ini{position:absolute;inset:0;display:grid;place-items:center;font-size:64px;font-weight:700;color:rgba(255,255,255,.3)}
.capa em{position:absolute;left:8px;top:8px;background:#000b;color:#fff;padding:2px 10px;border-radius:99px;font-size:13px;font-style:normal;z-index:1}
.rot{margin:8px 0 0;font-size:12px;font-weight:700;color:var(--mt);text-transform:uppercase}
.card h3{font-size:17px;font-weight:400;line-height:1.5;margin:2px 0 6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.preco{margin:0 0 12px;font-size:17px;color:#4a5063}
.selos{position:absolute;left:10px;bottom:10px;display:flex;gap:6px;flex-wrap:wrap;z-index:1}
.selos b{background:#fff;color:#111;border:1px solid #111;border-radius:3px;padding:0 6px;font-size:12px;line-height:18px;font-weight:700}
.esgotado .capa{opacity:.55}
/* sombra azul em volta da capa ao passar o mouse (ou focar com o teclado) */
.capa{transition:box-shadow .25s}
.card:hover .capa,.card:focus-within .capa{box-shadow:0 0 0 4px rgba(0,112,209,.55),0 12px 28px -6px rgba(0,112,209,.45)}
.botoes{display:flex;gap:8px;margin-top:auto}
.botoes a{flex:1;text-align:center;padding:7px 12px;border-radius:99px;font-size:14px;font-weight:600;transition:background .15s}
.botoes .editar{background:#eef3fb;color:var(--ac)}.botoes .editar:hover{background:#dce8fa}
.botoes .excluir{background:#fdeceb;color:#d93025}.botoes .excluir:hover{background:#f9d6d3}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape a:hover{color:var(--ac)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(max-width:700px){.grade{grid-template-columns:repeat(2,1fr);gap:22px 14px}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <a href="<?= BASE ?>/admin/views/gerenciar_games.php" style="color:var(--ac);font-weight:600">Gerenciar jogos</a>
    <a href="<?= BASE ?>/admin/views/ver_usuarios.php">Gerenciar usuários</a>
    <a href="<?= BASE ?>/admin/views/ver_clientes.php">Clientes</a>
    <a href="<?= BASE ?>/admin/views/ver_categorias.php">Categorias</a>
    <a href="<?= BASE ?>/admin/views/dashboard.php">Dashboard</a>
    <a href="<?= BASE ?>/admin/views/pedidos.php">Pedidos</a>
  </nav>
  <div class="conta"><span><?= h($u['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a></div>
</header>
<main>
<?php if ($m = flash()): ?><p style="margin:16px 0 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1"><?= h($m) ?></p><?php endif; ?>
<form class="busca" id="filtros" method="get" action="gerenciar_games.php" role="search">
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
<div class="acoes"><a class="btn-add" href="cadastrar_game.php">+ Adicionar jogo</a></div>
<div id="conteudo">
  <section class="bloco" id="catalogo">
    <h2><?= $filtrando ? 'Resultados' . ($cat !== '' ? ' em ' . h($cat) : '') . ($busca !== '' ? ' para “' . h($busca) . '”' : '') : 'Todos os jogos' ?></h2>
    <?php if (!$resultados): ?><p class="vazio"><?= $filtrando ? 'Nenhum jogo encontrado. Tente outro nome ou filtro.' : 'Ainda não há jogos cadastrados.' ?></p><?php endif; ?>
    <div class="grade"><?php foreach ($resultados as $g) card($g); ?></div>
  </section>
</div>
</main>

<footer class="rodape">
  <a href="<?= BASE ?>/index.php#inicio"><strong>Voltar ao início</strong></a>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>

<form id="fdel" method="POST" action="../controllers/deletar_game.php" hidden><?= csrfCampo() ?><input type="hidden" name="id" id="fdel-id"></form>
<script>
function excluir(titulo, id) {
  if (confirm('Tem certeza que deseja excluir o jogo "' + titulo + '"?')) {
    document.getElementById('fdel-id').value = id;
    document.getElementById('fdel').submit();
  }
  return false;
}
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
</body>
</html>
