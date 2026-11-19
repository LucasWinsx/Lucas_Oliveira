<?php
require_once __DIR__ . '/../admin/config/auth.php';
require_once __DIR__ . '/_ps_layout.php';
require_once __DIR__ . '/../admin/config/biblioteca.php';
$u = usuario();
if (!$u) { flash('Entre na sua conta para ver os seus jogos.'); header('Location: ' . BASE . '/login.php'); exit; }

/* Jogos comprados (pedidos não cancelados), do mais recente para o mais antigo. */
$comp = jogosComprados($pdo, (int)$u['id']);
$meus = [];
if ($comp) {
    try {
        $ids = array_keys($comp);
        $st = $pdo->prepare('SELECT * FROM games WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        $jogos = [];
        foreach ($st->fetchAll() as $g) $jogos[(int)$g['id']] = $g;
        foreach ($comp as $gid => $c) {
            if (!isset($jogos[$gid])) continue;
            $meus[] = ['id' => $gid, 'titulo' => $jogos[$gid]['titulo'], 'imagem' => $jogos[$gid]['imagem'] ?? '',
                       'qtd' => $c['qtd'], 'data' => $c['data'], 'pago' => $c['pago'], 'lancamento' => $jogos[$gid]['lancamento'] ?? ''];
        }
    } catch (Throwable $e) {}
}

/* Diagnóstico: meus_jogos.php?diagnostico=1 */
$diag = null;
if (isset($_GET['diagnostico'])) {
    $ped = pedidosDoUsuario($pdo, (int)$u['id']);
    $diag = ['usuario_id' => (int)$u['id'], 'pedidos_encontrados' => count($ped),
             'colunas_do_primeiro_pedido' => $ped ? array_keys((array)$ped[0]) : [],
             'status' => array_values(array_unique(array_map(fn($r) => $r['status'] ?? '?', $ped))),
             'jogos_na_biblioteca' => count($meus)];
}

ob_start(); ?>
<style>
.mj{padding:40px var(--px,5vw) 70px;min-height:60vh}
.mj h1{margin:0 0 6px;font-size:30px;font-weight:600;color:var(--tx)}
.mj .sub{margin:0 0 30px;color:var(--mt);font-size:15px}
.mj-grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:30px 22px}
.mj-card{display:block}
.mj-capa{position:relative;aspect-ratio:4/5;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45);box-shadow:0 12px 26px -14px rgba(0,0,0,.4),inset 0 0 0 1px rgba(0,0,0,.06);transition:transform .3s,box-shadow .3s}
.mj-card:hover .mj-capa,.mj-card:focus-visible .mj-capa{transform:scale(1.04);box-shadow:0 26px 44px -18px rgba(0,0,0,.5),0 0 0 2px var(--ac)}
.mj-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.mj-ini{position:absolute;inset:0;display:grid;place-items:center;font-size:64px;font-weight:700;color:rgba(255,255,255,.3)}
.mj-capa .selo{position:absolute;left:10px;top:10px;z-index:2;background:rgba(18,120,60,.92);color:#fff;padding:3px 11px;border-radius:99px;font-size:12px;font-weight:600}
.mj-capa .selo.pend{background:rgba(190,120,0,.95)}
.mj-card h3{margin:12px 0 2px;font-size:15.5px;font-weight:600;line-height:1.35;color:var(--tx);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mj-card p{margin:0;font-size:13px;color:var(--mt)}
<?= cssPreVenda() ?>
.mj-vazio{max-width:520px;padding:34px;border:1px dashed var(--bd);border-radius:16px;background:var(--bg2);color:#4a5063}
.mj-vazio a{display:inline-block;margin-top:14px;background:var(--ac);color:#fff;font-weight:600;padding:10px 26px;border-radius:999px}
.mj-vazio a:hover{background:var(--ac2)}
</style>
<?php $css = ob_get_clean();
ps_topo('Meus jogos', $css, 'meus_jogos');
?>
<main class="mj">
  <?php if ($diag): ?><pre style="background:var(--bg2);border:1px solid var(--bd);padding:14px;border-radius:10px;overflow:auto;font-size:13px"><?= h(json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre><?php endif; ?>
  <h1>Meus jogos</h1>
  <p class="sub"><?= count($meus) ? count($meus) . (count($meus) === 1 ? ' jogo' : ' jogos') . ' na sua biblioteca' : 'Os jogos que você comprar aparecem aqui.' ?></p>

  <?php if (!$meus): ?>
    <div class="mj-vazio">Você ainda não comprou nenhum jogo.<br><a href="<?= BASE ?>/catalogo.php">Ver catálogo</a></div>
  <?php else: ?>
  <div class="mj-grade">
    <?php foreach ($meus as $m): $img = $m['imagem'] !== '' ? BASE . '/admin/public/imagens/' . rawurlencode($m['imagem']) : ''; ?>
      <a class="mj-card" href="<?= BASE ?>/cliente/jogo.php?id=<?= (int)$m['id'] ?>">
        <div class="mj-capa">
          <span class="mj-ini"><?= h(mb_substr($m['titulo'], 0, 1)) ?></span>
          <?php if ($img): ?><img src="<?= h($img) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?>
          <span class="selo<?= $m['pago'] ? '' : ' pend' ?>"><?= $m['pago'] ? '&#10003; Comprado' : 'Aguardando pagamento' ?></span>
          <?= relogioPreVenda($m) ?>
        </div>
        <h3><?= h($m['titulo']) ?></h3>
        <p><?= $m['qtd'] > 1 ? (int)$m['qtd'] . ' cópias' : '1 cópia' ?><?php if ($m['data'] && ($ts = strtotime($m['data']))): ?> · <?= date('d/m/Y', $ts) ?><?php endif; ?></p>
        <?= estreiaHtml($m) ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<?php ps_rodape();
