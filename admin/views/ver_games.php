<?php
require_once '../config/auth.php';
require_once '../models/games.php';
exigirAdmin();

$game   = new Game();
$nome   = trim($_GET['buscar'] ?? '');
$filtro = trim($_GET['categoria'] ?? '');
$games  = $nome !== '' ? $game->buscarPorNome($nome) : $game->listarGames();

// categorias para o seletor "Filtrar por..." (sempre a partir da lista completa)
$categorias = array_values(array_unique(array_filter(array_column($game->listarGames(), 'categoria'))));
sort($categorias, SORT_NATURAL | SORT_FLAG_CASE);
if ($filtro !== '') {
    $games = array_values(array_filter($games, fn($g) => $g['categoria'] === $filtro));
}

// unidades vendidas por jogo (mesma regra da página inicial: pedidos não cancelados)
$vend = [];
if (isset($pdo)) try {
    $vend = Game::vendidosPorJogo();
} catch (Throwable $e) { /* tabela pedidos ainda não existe */ }

// ===== capas dos jogos =====
// Procura a imagem automaticamente em várias pastas comuns do projeto e monta a URL correta.
// Se o campo "imagem" já tiver URL completa ou começar com "/", é usado como está.
function urlDoArquivo($real) {
    $real = str_replace('\\', '/', $real);
    $raiz = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
    if ($raiz !== '' && strpos($real, $raiz . '/') === 0) {
        return implode('/', array_map('rawurlencode', explode('/', substr($real, strlen($raiz)))));
    }
    return null;
}
function srcImagem($img) {
    static $indice = null;
    $img = trim((string)$img);
    if ($img === '') return '';
    if (preg_match('#^(https?:)?//|^/|^data:#', $img)) return $img;

    $base = __DIR__;
    $pastas = ['../uploads/', '../uploads/games/', '../uploads/jogos/', '../uploads/capas/',
               '../assets/img/', '../assets/images/', '../assets/uploads/', '../img/', '../images/',
               '../imagens/', '../public/uploads/', '../public/img/', 'uploads/', 'img/', '../'];
    foreach ($pastas as $p) {
        $f = realpath($base . '/' . $p . $img);
        if ($f && is_file($f) && ($u = urlDoArquivo($f))) return $u;
    }
    // último recurso: varre o projeto (uma vez) procurando pelo nome do arquivo
    if ($indice === null) {
        $indice = [];
        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($base . '/..'), FilesystemIterator::SKIP_DOTS));
            $n = 0;
            foreach ($it as $arq) {
                if (++$n > 20000) break;
                if ($arq->isFile() && preg_match('/\.(png|jpe?g|webp|gif|avif)$/i', $arq->getFilename())) {
                    $indice[strtolower($arq->getFilename())] ??= $arq->getPathname();
                }
            }
        } catch (Throwable $e) {}
    }
    $f = $indice[strtolower(basename($img))] ?? null;
    if ($f && ($u = urlDoArquivo($f))) return $u;
    return '../uploads/' . $img; // palpite final
}
topo('Lista de jogos');
?>
<style>
/* fundo branco */
html,body{background:#fff !important}
/* cabeçalho branco */
body > header,header,.topo,.navbar,.topbar,.menu,.site-header,body > nav,nav{background:#fff !important;color:#6b7280 !important;box-shadow:none !important;border-bottom:1px solid #eceef3 !important}
body > header a,header a,.topo a,.navbar a,.topbar a,.menu a,.site-header a,nav a{color:#6b7280 !important;text-shadow:none !important}
header a:hover,.topo a:hover,.navbar a:hover,.topbar a:hover,.menu a:hover,nav a:hover{color:#0a74da !important}
header a.ativo,header a.active,header a[aria-current],.topo a.ativo,.topo a.active,.navbar a.active,.navbar a.ativo,nav a.ativo,nav a.active,nav a[aria-current]{color:#0a74da !important}

/* ===== catálogo de jogos: busca centralizada + botão + grade de 5 cartões ===== */
.cat-busca{display:flex;justify-content:center;gap:10px;margin:8px 0 18px}
.cat-busca input[type=text],.cat-busca select{height:36px;border:1px solid #e3e6ec;border-radius:99px;background:#fff;font:inherit;font-size:14px;color:#4b5563;outline:none;transition:border-color .2s,box-shadow .2s}
.cat-busca input[type=text]{width:min(260px,50vw);padding:0 16px;text-align:center}
.cat-busca select{width:150px;padding:0 12px;cursor:pointer}
.cat-busca input:focus,.cat-busca select:focus{border-color:#0a74da;box-shadow:0 0 0 3px rgba(10,116,218,.15)}
.cat-busca button{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

.cat-novo-wrap{display:flex;justify-content:center;margin:0 0 40px}
.cat-novo{display:inline-block;padding:8px 20px;border-radius:99px;background:#0a74da;color:#fff;font-weight:600;font-size:14px;text-decoration:none;transition:background .2s,transform .2s}
.cat-novo:hover{background:#085fb3;transform:translateY(-1px)}

.cat-titulo{margin:0 0 22px;font-size:32px;font-weight:500;letter-spacing:-.01em;color:#1f2430}

.cat-grade{display:grid;grid-template-columns:repeat(5,1fr);gap:34px 28px}
@media(max-width:1200px){.cat-grade{grid-template-columns:repeat(4,1fr)}}
@media(max-width:960px){.cat-grade{grid-template-columns:repeat(3,1fr)}}
@media(max-width:680px){.cat-grade{grid-template-columns:repeat(2,1fr);gap:26px 16px}.cat-busca{flex-wrap:wrap}}
.cat-vazio{grid-column:1/-1;color:#6b7280}

.cat-card{display:flex;flex-direction:column}
.cat-capa{position:relative;aspect-ratio:1/1;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#2b3150,#0b0d14);transition:box-shadow .25s,transform .25s}
.cat-capa .cat-ini{position:absolute;inset:0;display:grid;place-items:center;font:700 64px Georgia,serif;color:rgba(255,255,255,.12)}
.cat-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.cat-card:hover .cat-capa{box-shadow:0 14px 28px -12px rgba(0,0,0,.45);transform:translateY(-2px)}

.cat-selos{position:absolute;left:12px;bottom:12px;z-index:2;display:flex;gap:8px;flex-wrap:wrap}
.cat-selos b{background:#fff;color:#000;border:1px solid #000;border-radius:4px;padding:1px 8px;font-size:13px;line-height:20px;font-weight:700}
.cat-selos b.zerado{color:#c0262d}
.cat-selos b.baixo{color:#b45309}

.cat-cat{margin:12px 0 0;font-size:13px;font-weight:700;letter-spacing:.02em;color:#64748b;text-transform:uppercase}
.cat-card h3{margin:2px 0 6px;font-size:20px;font-weight:400;line-height:1.3;color:#1f2430;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.cat-preco{margin:0 0 16px;font-size:18px;color:#5b6472}

/* botões Editar / Excluir sempre visíveis, abaixo do cartão */
.cat-acoes{display:flex;gap:10px;margin-top:auto}
.cat-acoes a,.cat-acoes button{flex:1;height:44px;display:flex;align-items:center;justify-content:center;border:0;border-radius:99px;font-family:inherit;font-weight:600;font-size:15px;cursor:pointer;text-decoration:none;transition:background .2s}
.cat-acoes .editar{background:#e9eefb;color:#0a5fc2}
.cat-acoes .editar:hover{background:#d8e2f7}
.cat-acoes .perigo{background:#fde8e8;color:#c0262d}
.cat-acoes .perigo:hover{background:#fbd3d3}

@media(prefers-reduced-motion:reduce){.cat-capa,.cat-novo,.cat-acoes a,.cat-acoes button{transition:none}.cat-card:hover .cat-capa{transform:none}}
</style>

<form class="cat-busca" method="GET">
  <input type="text" name="buscar" placeholder="Buscar jogo por nome" value="<?= h($nome) ?>" aria-label="Buscar jogo por nome">
  <select name="categoria" aria-label="Filtrar por categoria" onchange="this.form.submit()">
    <option value="">Filtrar por...</option>
    <?php foreach ($categorias as $c): ?>
      <option value="<?= h($c) ?>" <?= $c === $filtro ? 'selected' : '' ?>><?= h($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Buscar</button>
</form>

<div class="cat-novo-wrap">
  <a class="cat-novo" href="cadastrar_game.php">+ Adicionar jogo</a>
</div>

<h2 class="cat-titulo"><?= ($nome !== '' || $filtro !== '') ? 'Resultados' : 'Todos os jogos' ?></h2>

<div class="cat-grade">
  <?php if (count($games) === 0): ?>
    <p class="cat-vazio">Nenhum jogo encontrado.</p>
  <?php endif; ?>
  <?php foreach ($games as $g): $est = (int)$g['estoque']; ?>
  <article class="cat-card">
    <div class="cat-capa">
      <span class="cat-ini"><?= h(mb_strtoupper(mb_substr($g['titulo'], 0, 1))) ?></span>
      <?php if (!empty($g['imagem'])): ?>
        <img src="<?= h(srcImagem($g['imagem'])) ?>" alt="" loading="lazy" onerror="this.remove()">
      <?php endif; ?>
      <span class="cat-selos">
        <b class="<?= $est <= 0 ? 'zerado' : ($est <= 5 ? 'baixo' : '') ?>"><?= $est <= 0 ? 'Esgotado' : 'Estoque ' . $est ?></b>
        <b>Vendidos <?= $vend[(int)$g['id']] ?? 0 ?></b>
      </span>
    </div>
    <p class="cat-cat"><?= h($g['categoria']) ?></p>
    <h3 title="<?= h($g['descricao']) ?>"><?= h($g['titulo']) ?></h3>
    <p class="cat-preco"><?= moeda($g['preco']) ?></p>
    <div class="cat-acoes">
      <a class="editar" href="editar_game.php?id=<?= (int)$g['id'] ?>">Editar</a>
      <button type="button" class="perigo" onclick="return excluir('<?= h(addslashes($g['titulo'])) ?>', <?= (int)$g['id'] ?>)">Excluir</button>
    </div>
  </article>
  <?php endforeach; ?>
</div>

<form id="fdel" method="POST" action="../controllers/deletar_game.php" hidden><?= csrfCampo() ?><input type="hidden" name="id" id="fdel-id"></form>
<script>
function excluir(titulo, id) {
  if (confirm('Tem certeza que deseja excluir o jogo "' + titulo + '"?')) {
    document.getElementById('fdel-id').value = id;
    document.getElementById('fdel').submit();
  }
  return false;
}
</script>
<script>
// Troca o texto "Lucas_Oliveira" do cabeçalho por "Voltar ao início", apontando para a página inicial
(function () {
  var HOME = '/lucas_oliveira/index.php';
  var re = /^\s*lucas[_ ]?oliveira\s*$/i;
  document.querySelectorAll('body *').forEach(function (el) {
    if (el.children.length || !re.test(el.textContent) || el.closest('.cat-grade,.cat-busca')) return;
    el.textContent = 'Voltar ao início';
    var ref = Array.prototype.find.call(document.querySelectorAll('a'), function (x) { return /^\s*pedidos\s*$/i.test(x.textContent); });
    if (ref) { el.className = ref.className; el.style.cssText = ''; }
    if (el.tagName === 'A') {
      el.href = HOME;
    } else {
      var a = document.createElement('a');
      a.href = HOME;
      a.className = el.className;
      a.style.cssText = ref ? '' : el.style.cssText + ';color:inherit;text-decoration:none';
      a.textContent = el.textContent;
      el.replaceWith(a);
    }
  });
})();
</script>
<?php rodape();
