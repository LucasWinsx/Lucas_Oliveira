<?php
require_once __DIR__ . '/../admin/config/auth.php';
require_once __DIR__ . '/../admin/models/games.php';
require_once __DIR__ . '/_ps_layout.php';
require_once __DIR__ . '/../admin/config/biblioteca.php';
$u  = usuario();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(Game::SQL_SELECT . ' WHERE g.id=?');
$st->execute([$id]);
$g = $st->fetch();
if (!$g) { flash('Jogo não encontrado.'); header('Location: ' . BASE . '/catalogo.php'); exit; }

/* Capa e campos opcionais: se a coluna não existir ou estiver vazia, o bloco não aparece. */
$capaArq   = trim((string)($g['capa'] ?? ''));           // imagem larga de fundo (opcional): URL ou arquivo em admin/public/imagens
$capa      = $capaArq !== ''
    ? (preg_match('#^https?://#i', $capaArq) ? $capaArq : BASE . '/admin/public/imagens/' . rawurlencode($capaArq))
    : (!empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : '');
$dev       = trim($g['desenvolvedora'] ?? '');
/* Plataformas: reconhece PS4, PS5, Xbox Series S, Xbox Series X e PC (separadas por vírgula, / ou |) e padroniza o nome. */
function plataformaNome($p) {
    $t = mb_strtolower(trim($p));
    if (preg_match('/ps\s*5|playstation\s*5/u', $t)) return 'PS5';
    if (preg_match('/ps\s*4|playstation\s*4/u', $t)) return 'PS4';
    if (preg_match('/series\s*s\b/u', $t)) return 'Xbox Series S';
    if (preg_match('/series\s*x\b/u', $t)) return 'Xbox Series X';
    if (preg_match('/^(pc|windows|steam|computador)/u', $t)) return 'PC';
    return trim($p);
}
$plataf = [];
foreach (preg_split('/[,\/|;]+/', (string)($g['plataforma'] ?? '')) as $pl) {
    $pl = trim($pl);
    if ($pl !== '') { $n = plataformaNome($pl); $plataf[$n] = $n; }
}
$plataf  = array_values($plataf);
$platTxt = implode(' / ', $plataf);
$platIco = $plataf ? $plataf[0] : 'PS5';
$edicao    = trim($g['edicao'] ?? '');
$nota      = max(0, min(5, (float)($g['nota'] ?? 0)));
$avals     = (int)($g['avaliacoes'] ?? 0);
$jogadores = trim($g['jogadores'] ?? '');
$classif   = trim((string)($g['classificacao'] ?? ''));
$conteudo  = trim($g['conteudo_classificacao'] ?? '');
$versao    = trim($g['versao'] ?? '');
$comprasJg = (int)($g['compras_jogo'] ?? 0);
$descricao = trim((string)($g['descricao'] ?? ''));
$lanc      = trim((string)($g['lancamento'] ?? ''));        // data de lançamento (opcional)
$aviso     = trim((string)($g['aviso_compra'] ?? ''));      // texto abaixo do preço (opcional)
$tsLanc    = $lanc !== '' ? strtotime($lanc) : false;
$preVenda  = estreiaFutura($g) !== null;   // mesma regra da vitrine: lançamento ainda no futuro

/* Perfil de administrador: em vez de "Comprar", mostra "Editar jogo" + estoque e cópias vendidas */
$ehAdmin   = $u && ($u['tipo'] ?? '') === 'admin';
$jaComprou = $u && !$ehAdmin && in_array($id, idsComprados($pdo, (int)$u['id']), true);
$noCarrinho = !empty($_SESSION['carrinho'][$id]);   // limite: 1 unidade por jogo, então basta saber se já está lá
$urlEditar = BASE . '/admin/views/editar_game.php?id=' . (int)$g['id'];   // ajuste aqui se a sua tela de edição tiver outro nome
$vendidas  = 0;
if ($ehAdmin) {
    try {
        $q = $pdo->prepare("SELECT COALESCE(SUM(quantidade),0) FROM pedidos WHERE game_id=? AND status <> 'cancelado'");
        $q->execute([$id]);
        $vendidas = (int)$q->fetchColumn();
    } catch (Throwable $e) { /* tabela pedidos ainda não existe */ }
}

/* Trailer de fundo (opcional): link do YouTube ou arquivo de vídeo (.mp4/.webm), URL completa ou nome do arquivo em admin/public/imagens */
$trailer = trim((string)($g['trailer'] ?? ''));
$ytId = ''; $trailerArq = '';
if ($trailer !== '') {
    if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~i', $trailer, $mm)) $ytId = $mm[1];
    elseif (preg_match('~\.(mp4|webm|ogv)(\?.*)?$~i', $trailer))
        $trailerArq = preg_match('~^https?://~i', $trailer) ? $trailer : BASE . '/admin/public/imagens/' . rawurlencode($trailer);
}
$ytSrc = $ytId ? 'https://www.youtube-nocookie.com/embed/' . $ytId . '?autoplay=1&mute=1&controls=0&loop=1&playlist=' . $ytId
       . '&playsinline=1&modestbranding=1&rel=0&iv_load_policy=3&disablekb=1&enablejsapi=1' : '';
$temTrailer = $ytId !== '' || $trailerArq !== '';

$itens = array_values(array_filter(array_map('trim', preg_split('/\R/', $g['recursos'] ?? ''))));
if ($jogadores !== '') array_splice($itens, min(1, count($itens)), 0, [$jogadores]);
if ($comprasJg) array_unshift($itens, 'Compras no jogo opcionais');
$LIMITE = 7;                                   // itens visíveis antes do "Visualizar tudo"
$metade = (int)ceil(min(count($itens), $LIMITE) / 2);

/* O jogo já está na lista de desejos deste usuário? (se a tabela ainda não existe, segue sem erro) */
$ehFav = false;
if ($u) {
    try {
        $q = $pdo->prepare('SELECT 1 FROM favoritos WHERE usuario_id=? AND game_id=?');
        $q->execute([(int)$u['id'], $id]);
        $ehFav = (bool)$q->fetchColumn();
    } catch (Throwable $e) { /* rode o favoritos.sql */ }
}

function milhar($n) {
    return $n >= 1000 ? number_format($n / 1000, $n >= 10000 ? 0 : 1, ',', '.') . ' mil' : (string)$n;
}

/* Ícone de cada recurso, escolhido pela palavra-chave do texto. */
function icone($txt) {
    $t = mb_strtolower($txt);
    $p = [
        'globo'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'pessoa'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.5 3.5-7 8-7s8 2.5 8 7z"/>',
        'controle' => '<path d="M7 8h10a4 4 0 0 1 4 4.5l-.7 4a2.3 2.3 0 0 1-4 1L14.5 16h-5l-1.8 1.5a2.3 2.3 0 0 1-4-1L3 12.5A4 4 0 0 1 7 8z"/><path d="M8 10.5v3M6.5 12h3"/>',
        'acess'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="7.5" r="1"/><path d="M7.5 10.5l4.5.8 4.5-.8M12 11.5V15M12 15l-2.5 3.5M12 15l2.5 3.5"/>',
        'energia'  => '<path d="M6 5h9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zM17 10h2v4h-2"/><path d="M11 8l-2 4h3l-2 4"/>',
        'ajuda'    => '<path d="M9 18h5M10 21h3M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/>',
        'ps5'      => '<rect x="8" y="3" width="8" height="18" rx="2"/><path d="M12 7v10"/>',
        'compras'  => '<path d="M7 9h9a4 4 0 0 1 4 4.5l-.6 3.5a2.2 2.2 0 0 1-3.8.9L14 16H9l-1.6 1.9a2.2 2.2 0 0 1-3.8-.9L3 13.5A4 4 0 0 1 7 9z"/><path d="M17 3.5h2.3l-.9 3.2h-2.6z"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'pc'       => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/>',
        'xbox'     => '<circle cx="12" cy="12" r="9"/><path d="M7.5 7.5c2 1.5 3 3 4.5 4.5 1.5-1.5 2.5-3 4.5-4.5M7.5 16.5c2-1.5 3-3 4.5-4.5 1.5 1.5 2.5 3 4.5 4.5"/>',
    ];
    if (preg_match('/compras/u', $t)) $k = 'compras';
    elseif (preg_match('/offline|online/u', $t)) $k = 'globo';
    elseif (preg_match('/jogador/u', $t)) $k = 'pessoa';
    elseif (preg_match('/remoto/u', $t)) $k = 'controle';
    elseif (preg_match('/acessibilidade/u', $t)) $k = 'acess';
    elseif (preg_match('/vibra|dualsense|gatilho|controle/u', $t)) $k = 'controle';
    elseif (preg_match('/energia|bateria/u', $t)) $k = 'energia';
    elseif (preg_match('/ajuda/u', $t)) $k = 'ajuda';
    elseif ($t === 'ps5' || $t === 'ps4') $k = 'ps5';
    elseif ($t === 'pc') $k = 'pc';
    elseif (strpos($t, 'xbox') === 0) $k = 'xbox';
    else $k = 'check';
    return '<svg class="jg-ico" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$k] . '</svg>';
}

ob_start(); ?>
<style>
:root{--hh:clamp(660px,94vh,1020px)}
/* estilo PS Store: conteúdo escuro com o cabeçalho claro */
body{background:#0d0f12;color:#fff}
:focus-visible{outline-color:#fff}
.rodape{background:#fff;border-top-color:#e3e5e8;color:#555;box-shadow:0 0 0 100vmax #fff;clip-path:inset(0 -100vmax)}.rodape strong{color:#111}.rodape a,.rodape a:visited{color:#111}.rodape a:hover{color:#0070d1}
.jg-stage{position:relative;height:var(--hh);display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;background:#1b1d22}
.jg-stage::after{content:"";position:absolute;inset:0;z-index:1;pointer-events:none;background:linear-gradient(to top,rgba(13,15,18,.94) 0,rgba(13,15,18,.7) 35%,rgba(13,15,18,.15) 70%,transparent 100%)}
.jg-stage{touch-action:manipulation}
.jg-stage::after{transition:opacity .45s}
.jg-corpo{transition:opacity .45s,transform .45s}
.jg-stage.solo::after{opacity:0}
.jg-stage.solo .jg-corpo{opacity:0;transform:translateY(24px);pointer-events:none;visibility:hidden;transition:opacity .45s,transform .45s,visibility 0s .45s}
.jg-dica{position:absolute;left:50%;top:24px;transform:translateX(-50%);z-index:5;background:rgba(0,0,0,.6);color:#fff;font-size:14px;padding:8px 16px;border-radius:999px;opacity:0;pointer-events:none;transition:opacity .3s;white-space:nowrap}
.jg-dica.on{opacity:1}
.jg-hero{position:absolute;inset:0;overflow:hidden;height:var(--hh);background-color:#1b1d22;background-size:cover;background-position:center top}
.jg-video{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:max(100vw,calc(var(--hh) * 16 / 9));height:max(var(--hh),56.25vw);border:0;object-fit:cover;pointer-events:none}
.jg-volbox{position:absolute;right:28px;bottom:28px;z-index:4;display:flex;align-items:center;flex-direction:row-reverse;gap:0;height:40px;background:none;border:0;padding:0;filter:drop-shadow(0 1px 3px rgba(0,0,0,.75))}
.jg-som{flex:0 0 36px;width:36px;height:36px;border:0;background:none;color:#fff;display:grid;place-items:center;cursor:pointer;padding:0}
.jg-volbox{transition:opacity .4s}
.jg-stage.idle .jg-volbox:not(:focus-within){opacity:0;pointer-events:none}
.jg-stage.idle.fs{cursor:none}
.jg-som svg{width:26px;height:26px}
.jg-vol{width:0;opacity:0;margin:0;accent-color:#fff;cursor:pointer;height:4px;transition:width .2s,opacity .2s,margin .2s}
.jg-volbox:hover .jg-vol,.jg-volbox:focus-within .jg-vol{width:110px;opacity:1;margin:0 4px 0 14px}
@media(hover:none){.jg-vol{width:90px;opacity:1;margin:0 4px 0 14px}}
.jg-som .on{display:none}.jg-som[aria-pressed=true] .on{display:block}.jg-som[aria-pressed=true] .off{display:none}
@media(prefers-reduced-motion:reduce){.jg-video,.jg-volbox{display:none}}
.jg-corpo{position:relative;z-index:3;display:flex;gap:clamp(40px,6.2vw,118px);padding:0 48px 40px 40px;align-items:flex-end}
.jg-painel{flex:0 0 auto;width:fit-content;min-width:320px;max-width:min(520px,100%);position:relative;background:none;padding:0;text-shadow:0 1px 8px rgba(0,0,0,.75)}
.jg-painel h1{margin:0 0 14px;font-size:32px;font-weight:400;line-height:1.2}
.jg-dev{font-size:13px;font-weight:600;margin:0 0 8px}
.jg-data{font-size:13px;font-weight:600;margin:0 0 10px}
.jg-tags{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px;padding:0;list-style:none}
.jg-tags li{border:1px solid #fff;font-size:11px;font-weight:700;padding:2px 6px;text-transform:uppercase}
.jg-tags li.pl{background:rgba(255,255,255,.14)}
.jg-nota{display:flex;align-items:center;gap:10px;font-size:16px;margin:0 0 14px}
.jg-estrelas{position:relative;display:inline-block;font-size:20px;letter-spacing:3px;line-height:1;color:#55585e}
.jg-estrelas span{position:absolute;inset:0 auto 0 0;overflow:hidden;white-space:nowrap;color:#fff}
.jg-cat{width:0;min-width:100%;margin:0 0 14px;color:#d0d3d8;font-size:14px}
.jg-preco{font-size:26px;font-weight:400;margin:0 0 6px}
.jg-aviso{width:0;min-width:100%;font-size:12px;font-weight:600;line-height:1.35;margin:0 0 16px}
.jg-acoes{display:flex;align-items:center;gap:12px}
.jg-btn{flex:0 1 210px;background:#fff;color:#111;font-weight:600;font-size:15px;padding:10px 0;text-align:center;border-radius:999px}
.jg-btn:hover{background:#e6e6e6}
.jg-btn[aria-disabled=true]{background:rgba(68,71,77,.8);color:#fff;pointer-events:none}
.jg-btn.sec{background:rgba(0,0,0,.3);color:#fff;border:2px solid rgba(255,255,255,.7);padding:8px 0;font-family:inherit;cursor:pointer}
.jg-btn.sec:hover{background:rgba(255,255,255,.18)}
.jg-btn.sec.no{border-color:rgba(80,200,120,.8);background:rgba(18,120,60,.45)}
.jg-btn.sec:disabled{opacity:.6;cursor:wait}
.jg-cmsg{margin:10px 0 0;font-size:13px;font-weight:600;min-height:18px}
.jg-cmsg a{text-decoration:underline}
.jg-comprado{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 14px;padding:8px 14px;border:1px solid rgba(80,200,120,.55);border-radius:10px;background:rgba(18,120,60,.35);font-size:14px;font-weight:600}
.jg-comprado a{color:#9be7b5;text-decoration:underline;font-weight:600}
.jg-admin{display:flex;gap:10px;margin:0 0 16px}
.jg-admin div{min-width:104px;padding:8px 14px;border:1px solid rgba(255,255,255,.4);border-radius:10px;background:rgba(0,0,0,.35)}
.jg-admin span{display:block;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#d0d3d8}
.jg-admin b{display:block;font-size:24px;font-weight:600;line-height:1.2}
.jg-admin .zero b{color:#ff8a80}
.jg-fav{width:40px;height:40px;flex:0 0 40px;border-radius:50%;border:2px solid rgba(255,255,255,.5);background:rgba(0,0,0,.25);color:#fff;display:grid;place-items:center;cursor:pointer;padding:0}
.jg-fav:hover,.jg-fav[aria-pressed=true]{border-color:#fff}
.jg-fav[aria-pressed=true] svg{fill:#fff}
.jg-info{flex:1;display:grid;grid-template-columns:1fr 1.02fr 1.64fr;gap:35px;padding-top:0;font-size:16px;line-height:1.35;text-shadow:0 1px 6px rgba(0,0,0,.65)}
.jg-info ul{list-style:none;margin:0;padding:0;display:grid;gap:16px;align-content:start}
.jg-info li{display:flex;gap:14px;align-items:center}
.jg-info li[hidden]{display:none}
.jg-ico{flex:0 0 28px;color:#fff}
.jg-ver h3{margin:0 0 12px;font-size:16px;font-weight:700}
.jg-resto{margin-top:16px}
.jg-mais{background:none;border:0;color:#4da3ff;font:inherit;font-size:18px;cursor:pointer;padding:0;margin-top:18px}
.jg-mais:hover{text-decoration:underline}
.jg-classif{display:flex;gap:16px;align-items:flex-start;padding-bottom:16px;border-bottom:1px solid #44474d;margin-bottom:14px}
.jg-classif b{flex:0 0 48px;height:48px;display:grid;place-items:center;border:2px solid #fff;border-radius:4px;background:#000;font-size:26px}
.jg-sobre{padding:44px var(--px) 56px;background:#fff;color:#111}
.jg-sobre h2{font-size:22px;font-weight:600;margin:0 0 12px;color:#111;max-width:760px}
.jg-sobre p{margin:0;color:#333;white-space:pre-line;max-width:760px}
.jg-sobre .w{display:inline-block;white-space:nowrap}
.jg-sobre-in{position:relative;max-width:760px}
.jg-sobre-in p{cursor:zoom-in}
.jg-zoom{position:absolute;left:-28px;top:-24px;width:calc(100% + 56px);min-height:calc(100% + 48px);max-height:78vh;overflow:auto;box-sizing:border-box;padding:28px;background:rgba(14,16,20,.97);color:#e9ebef;border-radius:14px;box-shadow:0 18px 60px rgba(0,0,0,.55);z-index:20;opacity:0;visibility:hidden;transform:scale(.9);transform-origin:top left;transition:opacity .25s,transform .25s,visibility 0s .25s}
.jg-zoom.on{opacity:1;visibility:visible;transform:scale(1);transition:opacity .25s,transform .25s}
.jg-zoom h3{margin:0 0 12px;font-size:24px;font-weight:600;color:#fff}
.jg-zoom p{margin:0;font-size:21px;line-height:1.55;white-space:pre-line;color:#e9ebef;cursor:default}
.jg-zoom .w{display:inline-block;white-space:nowrap}
@media(max-width:1100px){
  :root{--hh:clamp(300px,62vw,520px)}
  .jg-stage{height:auto;display:block;overflow:visible;background:none}
  .jg-stage::after{display:none}
  .jg-hero{position:relative;inset:auto}
  .jg-corpo{flex-direction:column;align-items:stretch;gap:0}
  .jg-painel{flex:none;width:100%;max-width:100%;margin-top:-120px;padding:0 18px}
  .jg-painel h1{font-size:26px}
  .jg-info{grid-template-columns:1fr;padding-top:28px;width:100%;text-shadow:none}
}
.jg-stage.fs{position:fixed;inset:0;z-index:9999;width:100%;height:100vh;height:100dvh;--hh:100vh;display:flex;overflow:hidden}
.jg-stage.fs .jg-hero{position:absolute;inset:0;height:100%}
.jg-stage.fs::after{display:none}
</style>
<?php $css = ob_get_clean();
ps_topo($g['titulo'], $css, 'catalogo');
?>
<article>
  <div class="jg-stage" id="stage">
  <?php if ($temTrailer || $capa): ?><div class="jg-dica" id="dica" role="status" aria-live="polite"></div><?php endif; ?>
  <div class="jg-hero"<?= $temTrailer ? '' : ' role="img" aria-label="Capa de ' . h($g['titulo']) . '"' ?>
       <?php if ($capa): ?>style="background-image:url('<?= h($capa) ?>')"<?php endif; ?>>
    <?php if ($ytId): ?>
      <iframe id="trailer" class="jg-video" src="<?= h($ytSrc) ?>" title="Trailer de <?= h($g['titulo']) ?>" allow="autoplay; encrypted-media" tabindex="-1" aria-hidden="true"></iframe>
    <?php elseif ($trailerArq): ?>
      <video id="trailer" class="jg-video" src="<?= h($trailerArq) ?>" <?php if ($capa): ?>poster="<?= h($capa) ?>" <?php endif; ?>autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
    <?php endif; ?>
    <?php if ($temTrailer): ?>
      <div class="jg-volbox">
      <button type="button" class="jg-som" id="tela" aria-pressed="false" aria-label="Abrir trailer em tela cheia">
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/></svg>
      </button>
      <button type="button" class="jg-som" id="som" aria-pressed="false" aria-label="Ativar som do trailer">
        <svg class="off" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z"/><path d="M16 9.5l5 5M21 9.5l-5 5"/></svg>
        <svg class="on" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9.5h3.5L12 6v12l-4.5-3.5H4z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/></svg>
      </button>
      <input type="range" class="jg-vol" id="vol" min="0" max="100" step="1" value="50" aria-label="Volume do trailer">
      </div>
    <?php endif; ?>
  </div>

  <div class="jg-corpo">
    <section class="jg-painel">
      <h1><?= h($g['titulo']) ?></h1>
      <?php if ($dev): ?><p class="jg-dev"><?= h($dev) ?></p><?php endif; ?>
      <?php if ($tsLanc !== false): ?><p class="jg-data"><?= date('d/m/Y H:i', $tsLanc) ?> BRT</p><?php endif; ?>

      <?php if ($plataf || $edicao): ?>
      <ul class="jg-tags">
        <?php foreach ($plataf as $p): ?><li class="pl"><?= h($p) ?></li><?php endforeach; ?>
        <?php if ($edicao): ?><li><?= h($edicao) ?></li><?php endif; ?>
      </ul>
      <?php endif; ?>

      <?php if ($nota > 0): ?>
      <p class="jg-nota">
        <span><?= number_format($nota, 2, '.', '') ?></span>
        <span class="jg-estrelas" aria-hidden="true">★★★★★<span style="width:<?= round($nota / 5 * 100, 1) ?>%">★★★★★</span></span>
        <?php if ($avals): ?><span><?= milhar($avals) ?> classificações</span><?php endif; ?>
      </p>
      <?php elseif (!empty($g['categoria'])): ?>
      <p class="jg-cat"><?= h($g['categoria']) ?></p>
      <?php endif; ?>

      <p class="jg-preco"><?= moeda($g['preco']) ?></p>
      <?php if ($aviso !== ''): ?><p class="jg-aviso"><?= h($aviso) ?></p><?php endif; ?>

      <?php if ($jaComprou): ?>
      <p class="jg-comprado">&#10003; Você já comprou este jogo <a href="<?= BASE ?>/cliente/meus_jogos.php">Ver em Meus jogos</a></p>
      <?php endif; ?>

      <?php if ($ehAdmin): ?>
      <div class="jg-admin" aria-label="Dados de venda">
        <div class="<?= (int)$g['estoque'] <= 0 ? 'zero' : '' ?>"><span>Estoque</span><b><?= (int)$g['estoque'] ?></b></div>
        <div><span>Cópias vendidas</span><b><?= $vendidas ?></b></div>
      </div>
      <?php endif; ?>

      <div class="jg-acoes">
        <?php if ($ehAdmin): ?>
          <a class="jg-btn" href="<?= h($urlEditar) ?>">Editar jogo</a>
        <?php elseif ((int)$g['estoque'] > 0): ?>
          <a class="jg-btn" href="<?= BASE ?>/cliente/comprar.php?id=<?= (int)$g['id'] ?>"><?= $preVenda ? 'Comprar na pré-venda' : ($jaComprou ? 'Comprar novamente' : 'Comprar') ?></a>
          <button type="submit" form="carform" class="jg-btn sec<?= $noCarrinho ? ' no' : '' ?>" id="addcar"><?= $noCarrinho ? '&#10003; No carrinho' : 'Adicionar ao carrinho' ?></button>
        <?php else: ?>
          <a class="jg-btn" aria-disabled="true" href="#">Indisponível</a>
        <?php endif; ?>
        <button type="submit" form="favform" class="jg-fav" id="fav" aria-pressed="<?= $ehFav ? 'true' : 'false' ?>"
                aria-label="<?= $ehFav ? 'Remover da lista de desejos' : 'Adicionar à lista de desejos' ?>">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M12 20.5S4 15.5 4 9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 8 2.5c0 6-8 11-8 11z"/></svg>
        </button>
      </div>
      <form id="favform" method="POST" action="<?= BASE ?>/cliente/favoritar.php" hidden>
        <?= csrfCampo() ?><input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
      </form>
      <?php if (!$ehAdmin && (int)$g['estoque'] > 0): ?>
      <form id="carform" method="POST" action="<?= BASE ?>/cliente/carrinho_acao.php" hidden>
        <?= csrfCampo() ?><input type="hidden" name="acao" value="add"><input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
      </form>
      <p class="jg-cmsg" id="cmsg" role="status" aria-live="polite"><?= $noCarrinho ? 'Este jogo já está no seu carrinho. <a href="' . BASE . '/cliente/carrinho.php">Ver carrinho</a>' : '' ?></p>
      <?php endif; ?>
    </section>

    <section class="jg-info" aria-label="Informações do jogo">
      <div>
        <ul>
          <?php foreach ($itens as $i => $r): if ($i >= $metade) break; ?>
            <li><?= icone($r) ?><span><?= h($r) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <?php if ($versao): ?>
        <div class="jg-ver">
          <h3><?= $platTxt !== '' ? 'Versão para ' . h($platTxt) : 'Versão' ?></h3>
          <ul><li><?= icone($platIco) ?><span><?= h($versao) ?></span></li></ul>
        </div>
        <?php endif; ?>
        <ul class="jg-resto">
          <?php foreach ($itens as $i => $r): if ($i < $metade) continue; ?>
            <li<?= $i >= $LIMITE ? ' hidden data-extra' : '' ?>><?= icone($r) ?><span><?= h($r) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($itens) > $LIMITE): ?>
          <button type="button" class="jg-mais" id="mais" aria-expanded="false">Visualizar tudo &rsaquo;</button>
        <?php endif; ?>
      </div>

      <div>
        <?php if ($classif !== ''): ?>
        <div class="jg-classif">
          <b aria-label="Classificação indicativa <?= h($classif) ?>"><?= h($classif) ?></b>
          <span><?= h($conteudo) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($comprasJg): ?><p style="margin:0">Compras no jogo</p><?php endif; ?>
      </div>
    </section>
  </div>
  </div>

  <?php if ($descricao !== ''): ?>
  <section class="jg-sobre">
    <div class="jg-sobre-in" id="sobre">
      <h2>Sobre o jogo</h2>
      <p id="desc"><?= h($descricao) ?></p>
      <div class="jg-zoom" id="zoom" aria-hidden="true"><h3><?= h($g['titulo']) ?></h3><p id="zoomtxt"><?= h($descricao) ?></p></div>
    </div>
  </section>
  <?php endif; ?>
</article>

<script>
const ff = document.getElementById('favform'), fav = document.getElementById('fav');
if (ff && fav) ff.addEventListener('submit', async e => {
  e.preventDefault();
  try {
    const r = await fetch(ff.action, { method: 'POST', body: new FormData(ff), headers: { 'X-Requested-With': 'fetch' } });
    if (r.status === 401) { location.href = '<?= BASE ?>/login.php'; return; }
    const d = await r.json();
    if (d.erro) { location.reload(); return; }
    fav.setAttribute('aria-pressed', d.fav ? 'true' : 'false');
    fav.setAttribute('aria-label', d.fav ? 'Remover da lista de desejos' : 'Adicionar à lista de desejos');
  } catch (_) { ff.submit(); }
});
/* Adicionar ao carrinho (limite de 1 unidade por jogo; o servidor também garante a regra) */
const cf = document.getElementById('carform'), cb = document.getElementById('addcar'), cm = document.getElementById('cmsg');
if (cf && cb) cf.addEventListener('submit', async e => {
  e.preventDefault();
  cb.disabled = true;
  try {
    const r = await fetch(cf.action, { method: 'POST', body: new FormData(cf), headers: { 'X-Requested-With': 'fetch' } });
    const d = await r.json();
    if (d.erro) { cm.textContent = d.erro; cb.disabled = false; return; }
    cb.classList.add('no'); cb.innerHTML = '&#10003; No carrinho';
    cm.innerHTML = (d.ja_estava ? 'Esse jogo já está no carrinho (limite de 1 unidade por jogo). ' : 'Adicionado ao carrinho. ') + '<a href="<?= BASE ?>/cliente/carrinho.php">Ver carrinho</a>';
    document.querySelectorAll('.menu a, .conta a').forEach(a => {      // atualiza o contador do menu
      if (/carrinho\.php/.test(a.getAttribute('href') || '')) a.textContent = 'Carrinho' + (d.total ? ' (' + d.total + ')' : '');
    });
  } catch (_) { cf.submit(); return; }
  cb.disabled = false;
});
const mais = document.getElementById('mais');
if (mais) mais.addEventListener('click', () => {
  const abrir = mais.getAttribute('aria-expanded') !== 'true';
  document.querySelectorAll('[data-extra]').forEach(li => li.hidden = !abrir);
  mais.setAttribute('aria-expanded', abrir);
  mais.innerHTML = abrir ? 'Mostrar menos &rsaquo;' : 'Visualizar tudo &rsaquo;';
});
const som = document.getElementById('som'), vol = document.getElementById('vol'), trailer = document.getElementById('trailer');
if (som && vol && trailer) {
  let ultimo = 50;                       // volume lembrado ao reativar o som
  const yt = (func, args = []) => trailer.contentWindow.postMessage(JSON.stringify({ event: 'command', func, args }), '*');
  function aplicar(v) {                  // v = 0..100 (0 = mudo)
    const ligado = v > 0;
    if (trailer.tagName === 'VIDEO') {
      trailer.volume = v / 100; trailer.muted = !ligado;
      if (ligado) trailer.play().catch(() => {});
    } else {
      if (ligado) { yt('setVolume', [v]); yt('unMute'); } else yt('mute');
    }
    vol.value = v;
    if (ligado) ultimo = v;
    som.setAttribute('aria-pressed', ligado);
    som.setAttribute('aria-label', ligado ? 'Silenciar trailer' : 'Ativar som do trailer');
  }
  som.addEventListener('click', () => aplicar(som.getAttribute('aria-pressed') === 'true' ? 0 : ultimo));
  vol.addEventListener('input', () => aplicar(+vol.value));
  vol.value = 0;                         // o trailer começa mudo (exigência do autoplay)
}

/* Brilho (luz branca) passando letra por letra + caixa de zoom com a descrição completa */
(function () {
  const p = document.getElementById('desc'), sobre = document.getElementById('sobre'),
        zoom = document.getElementById('zoom'), zt = document.getElementById('zoomtxt');
  if (!p) return;
  const semMov = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function brilho(el, base, alto) {
    if (!el.animate || semMov) return;
    const texto = el.textContent;
    el.setAttribute('aria-label', texto);
    el.textContent = '';
    const letras = [];
    texto.split(/(\s+)/).forEach(tok => {
      if (tok === '') return;
      if (/^\s+$/.test(tok)) { el.appendChild(document.createTextNode(tok)); return; }
      const w = document.createElement('span'); w.className = 'w'; w.setAttribute('aria-hidden', 'true');
      for (const ch of tok) { const l = document.createElement('span'); l.textContent = ch; w.appendChild(l); letras.push(l); }
      el.appendChild(w);
    });
    const n = letras.length, passo = Math.min(28, 5000 / n), pulso = 800, pausa = 2500;
    const total = n * passo + pulso + pausa;
    letras.forEach((l, i) => {
      const a = i * passo / total, m = a + pulso / 2 / total, b = a + pulso / total;
      const kf = [];
      if (a > 0) kf.push({ offset: 0, ...base });
      kf.push({ offset: a, ...base }, { offset: m, ...alto }, { offset: b, ...base }, { offset: 1, ...base });
      l.animate(kf, { duration: total, iterations: Infinity });
    });
  }

  /* No fundo branco a luz é uma letra branca com halo suave; na caixa escura o brilho branco aparece em cheio */
  brilho(p,
    { color: '#333', textShadow: '0 0 0 rgba(0,0,0,0)' },
    { color: '#fff', textShadow: '0 0 1px rgba(0,0,0,.55), 0 0 9px rgba(0,0,0,.35)' });

  if (!sobre || !zoom || !zt) return;
  let feito = false, tAbrir, tFechar;
  function abrir() {
    clearTimeout(tFechar);
    if (!feito) {
      feito = true;
      brilho(zt,
        { color: '#e9ebef', textShadow: '0 0 0 rgba(255,255,255,0)' },
        { color: '#fff', textShadow: '0 0 6px #fff, 0 0 16px rgba(255,255,255,.9), 0 0 28px rgba(255,255,255,.6)' });
    }
    zoom.classList.add('on');
  }
  function fechar() { clearTimeout(tAbrir); tFechar = setTimeout(() => zoom.classList.remove('on'), 120); }
  if (matchMedia('(hover:hover)').matches) {
    sobre.addEventListener('mouseenter', () => { clearTimeout(tFechar); tAbrir = setTimeout(abrir, 180); });
    sobre.addEventListener('mouseleave', fechar);
  } else {                                           // celular: toque abre e fecha
    sobre.addEventListener('click', () => zoom.classList.contains('on') ? zoom.classList.remove('on') : abrir());
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') zoom.classList.remove('on'); });
})();

/* Toque duplo (ou botão) na tela do trailer: abre o vídeo em tela cheia, sem as informações */
(function () {
  const stage = document.getElementById('stage'), dica = document.getElementById('dica'), btn = document.getElementById('tela');
  if (!stage) return;
  let tDica;
  function aviso(txt) {
    if (!dica) return;
    dica.textContent = txt; dica.classList.add('on');
    clearTimeout(tDica); tDica = setTimeout(() => dica.classList.remove('on'), 2200);
  }
  const nativa = () => document.fullscreenElement || document.webkitFullscreenElement;
  const ativa = () => stage.classList.contains('fs');
  function pintar() {
    const on = ativa();
    stage.classList.toggle('solo', on);
    if (btn) {
      btn.setAttribute('aria-pressed', on);
      btn.setAttribute('aria-label', on ? 'Sair da tela cheia' : 'Abrir trailer em tela cheia');
    }
  }
  function entrar() {
    stage.classList.add('fs');                       // garante o layout de tela cheia mesmo sem a API nativa (ex.: iPhone)
    const req = stage.requestFullscreen || stage.webkitRequestFullscreen;
    if (req) { try { const r = req.call(stage); if (r && r.catch) r.catch(() => {}); } catch (_) {} }
    pintar(); aviso('Toque duas vezes para sair');
  }
  function sair() {
    const ex = document.exitFullscreen || document.webkitExitFullscreen;
    if (nativa() && ex) { try { const r = ex.call(document); if (r && r.catch) r.catch(() => {}); } catch (_) {} }
    stage.classList.remove('fs');
    pintar();
  }
  function alternar() { ativa() ? sair() : entrar(); }
  stage.addEventListener('dblclick', e => {
    if (e.target.closest('a, button, input, .jg-painel, .jg-info')) return;
    const sel = window.getSelection && window.getSelection(); if (sel) sel.removeAllRanges();
    alternar();
  });
  if (btn) btn.addEventListener('click', alternar);
  // saiu pelo Esc/gesto do navegador: desfaz o layout de tela cheia
  ['fullscreenchange', 'webkitfullscreenchange'].forEach(ev => document.addEventListener(ev, () => {
    if (!nativa() && ativa()) { stage.classList.remove('fs'); pintar(); }
  }));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && ativa() && !nativa()) sair(); });
})();

/* Controles de volume e tela cheia somem sozinhos e só voltam ao mexer o mouse (ou tocar na tela) */
(function () {
  const stage = document.getElementById('stage'), box = stage && stage.querySelector('.jg-volbox');
  if (!stage || !box) return;
  let t;
  function agendar() {
    clearTimeout(t);
    t = setTimeout(() => {
      if (box.matches(':hover') || box.contains(document.activeElement)) { agendar(); return; }   // não esconde enquanto está em uso
      stage.classList.add('idle');
    }, 2500);
  }
  function mostrar() { stage.classList.remove('idle'); agendar(); }
  ['mousemove', 'mousedown', 'touchstart', 'wheel'].forEach(ev => stage.addEventListener(ev, mostrar, { passive: true }));
  document.addEventListener('keydown', mostrar);
  ['fullscreenchange', 'webkitfullscreenchange'].forEach(ev => document.addEventListener(ev, mostrar));
  agendar();
})();
</script>
<?php ps_rodape();
