<?php
require_once __DIR__ . '/../admin/config/auth.php';
require_once __DIR__ . '/_ps_layout.php';
exigirLogin();
$u = usuario();

$itens = []; $semTabela = false;
try {
    $st = $pdo->prepare('SELECT g.* FROM favoritos f JOIN games g ON g.id = f.game_id WHERE f.usuario_id = ? ORDER BY f.criado_em DESC');
    $st->execute([(int)$u['id']]);
    $itens = $st->fetchAll();
} catch (Throwable $e) { $semTabela = true; }

ob_start(); ?>
<style>
.ld{padding:36px var(--px) 56px}
.ld h1{margin:0 0 6px;font-size:30px;font-weight:600}
.ld .sub{margin:0 0 28px;color:var(--mt)}
.ld-grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:28px 22px}
.ld-capa{position:relative;aspect-ratio:16/9;border-radius:10px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45);display:block}
.ld-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.ld-item a.t{display:block;margin:10px 0 2px;font-size:17px;line-height:1.35}
.ld-item a.t:hover{color:var(--ac)}
.ld-item .p{margin:0 0 10px;color:#4a5063}
.ld-item .indisp{color:#d93025;font-size:14px}
.ld-acoes{display:flex;gap:10px;align-items:center}
.ld-comprar{background:var(--ac);color:#fff;font-weight:600;padding:8px 22px;border-radius:999px}
.ld-comprar:hover{background:var(--ac2)}
.ld-rem{background:none;border:0;color:var(--ac);font:inherit;cursor:pointer;padding:0}
.ld-rem:hover{text-decoration:underline}
.ld-vazio{color:#4a5063}
.ld-vazio a{color:var(--ac);font-weight:600}
.msg{margin:0 0 20px;padding:10px 14px;border:1px solid #ffb703;background:#fff8e1;border-radius:6px}
</style>
<?php $css = ob_get_clean();
ps_topo('Lista de desejos', $css, 'lista');
?>
<main class="ld">
  <h1>Lista de desejos</h1>
  <p class="sub"><?= count($itens) ?> jogo(s) salvo(s)</p>

  <?php if ($semTabela): ?>
    <p class="msg">A lista de desejos ainda não foi ativada no banco. Rode o arquivo favoritos.sql no phpMyAdmin.</p>
  <?php elseif (!$itens): ?>
    <p class="ld-vazio">Você ainda não salvou nenhum jogo. Abra a página de um jogo e toque no coração para guardá-lo aqui. <a href="<?= BASE ?>/catalogo.php">Ver catálogo</a></p>
  <?php endif; ?>

  <div class="ld-grade">
  <?php foreach ($itens as $g):
      $id = (int)$g['id'];
      $img = !empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : '';
      $ok = (int)$g['estoque'] > 0; ?>
    <article class="ld-item">
      <a class="ld-capa" href="<?= BASE ?>/cliente/jogo.php?id=<?= $id ?>" aria-label="<?= h($g['titulo']) ?>">
        <?php if ($img): ?><img src="<?= h($img) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?>
      </a>
      <a class="t" href="<?= BASE ?>/cliente/jogo.php?id=<?= $id ?>"><?= h($g['titulo']) ?></a>
      <p class="p"><?= moeda($g['preco']) ?><?php if (!$ok): ?> <span class="indisp">· Indisponível</span><?php endif; ?></p>
      <div class="ld-acoes">
        <?php if ($ok): ?><a class="ld-comprar" href="<?= BASE ?>/cliente/comprar.php?id=<?= $id ?>">Comprar</a><?php endif; ?>
        <form method="POST" action="<?= BASE ?>/cliente/favoritar.php" style="margin:0">
          <?= csrfCampo() ?><input type="hidden" name="game_id" value="<?= $id ?>"><input type="hidden" name="volta" value="lista">
          <button class="ld-rem">Remover</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
</main>
<?php ps_rodape();
