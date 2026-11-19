<?php
require_once '../config/auth.php';
exigirAdmin();

// ---------- layout (mesmo do catálogo) ----------
function pagina_topo() { $logado = usuario(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pedidos | Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--bd:#dfe3ee;--px:clamp(16px,5vw,90px)}
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
.busca select{min-width:0;flex:0 1 170px;padding:7px 14px;border:1px solid var(--bd);border-radius:99px;font:inherit;font-size:14px;color:var(--tx);background:#fff;cursor:pointer}
.busca select:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.total{text-align:center;margin:14px 0 0;color:var(--mt);font-size:14px}.total strong{color:var(--tx);font-size:18px;margin-left:6px}
.grade .card{animation:surge .45s cubic-bezier(.2,.7,.2,1) calc(var(--i,0) * 45ms) backwards}
@keyframes surge{from{opacity:0;transform:translateY(18px) scale(.96)}}
@media(prefers-reduced-motion:reduce){.grade .card{animation:none}html{scroll-behavior:auto}}
main{padding:0 var(--px) 48px}
.bloco{margin-top:40px}
.bloco h2{font-size:26px;font-weight:600;margin:0 0 18px}
.vazio{color:var(--mt)}
.erro{color:#d93025;font-weight:600}
.caixa{max-width:640px;margin:30px auto 0;padding:20px 24px;border:1px solid var(--bd);border-radius:10px}
.caixa button{background:var(--ac);color:#fff;border:0;border-radius:99px;padding:8px 22px;font:inherit;font-size:14px;font-weight:600;cursor:pointer}
.caixa button:hover{background:var(--ac2)}
.limpar{display:flex;justify-content:center;margin:12px 0 0}
.limpar form{margin:0}
.limpar button{background:#fdeceb;color:#d93025;border:0;border-radius:99px;padding:7px 18px;font:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s}
.limpar button:hover{background:#f9d6d3}
.grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:30px 22px}
.card{display:flex;flex-direction:column;min-width:0;padding:20px 18px 18px;border:1px solid var(--bd);border-radius:10px}
.topo-pedido{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;color:var(--mt);font-size:13px}
.tag{font-size:12px;font-weight:700;text-transform:uppercase;padding:2px 10px;border-radius:99px;white-space:nowrap}
.tag.pendente{background:#fff4d6;color:#a86b00}
.tag.pago{background:#e8f6ec;color:#1e8a3e}
.tag.cancelado{background:#fdeceb;color:#d93025}
.card h3{font-size:18px;font-weight:600;line-height:1.4;margin:12px 0 2px}
.info{margin:0 0 6px;font-size:14px;color:var(--mt)}
.preco{font-size:20px;color:#4a5063;margin:0 0 10px}
.cliente{margin:0 0 14px;font-size:14px;color:var(--tx)}.cliente small{color:var(--mt)}
.botoes{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto}
.botoes form{flex:1 1 auto;margin:0;display:flex}
.botoes button{flex:1;padding:7px 12px;border:0;border-radius:99px;font:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s}
.botoes .ok{background:#e8f6ec;color:#1e8a3e}.botoes .ok:hover{background:#d3eddb}
.botoes .perigo{background:#fdeceb;color:#d93025}.botoes .perigo:hover{background:#f9d6d3}
.botoes .editar-ped{flex:1 1 auto;text-align:center;padding:7px 12px;border-radius:99px;font-size:14px;font-weight:600;background:#eef3fb;color:var(--ac)}.botoes .editar-ped:hover{background:#dce8fa}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape a:hover{color:var(--ac)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(max-width:700px){.grade{grid-template-columns:1fr;gap:22px 14px}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <a href="<?= BASE ?>/admin/views/gerenciar_games.php">Gerenciar jogos</a>
    <a href="<?= BASE ?>/admin/views/ver_usuarios.php">Gerenciar usuários</a>
    <a href="<?= BASE ?>/admin/views/ver_clientes.php">Clientes</a>
    <a href="<?= BASE ?>/admin/views/ver_categorias.php">Categorias</a>
    <a href="<?= BASE ?>/admin/views/dashboard.php">Dashboard</a>
    <a href="<?= BASE ?>/admin/views/pedidos.php" style="color:var(--ac);font-weight:600">Pedidos</a>
  </nav>
  <div class="conta"><span><?= h($logado['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a></div>
</header>
<main>
<?php if ($m = flash()): ?><p style="margin:16px 0 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1"><?= h($m) ?></p><?php endif; ?>
<?php }

function pagina_fim() { ?>
</main>
<footer class="rodape">
  <a href="<?= BASE ?>/index.php#inicio"><strong>Voltar ao início</strong></a>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>
<script>
document.querySelectorAll('.grade .card').forEach(function (c, i) { c.style.setProperty('--i', Math.min(i, 14)); });
</script>
</body>
</html>
<?php }

// ---------- lógica ----------
if (!is_file(__DIR__ . '/../models/pedidos.php')) {
    pagina_topo();
    echo '<div class="caixa"><p class="erro">Falta o arquivo <strong>admin/models/pedidos.php</strong>. Copie-o do pacote e recarregue a página.</p></div>';
    pagina_fim(); exit;
}
require_once '../models/pedidos.php';

// Remove definitivamente todos os pedidos cancelados (o estoque já foi devolvido ao cancelar).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpar_cancelados'])) {
    if (!csrfOk()) {
        flash('Sessão expirada. Tente novamente.');
    } else {
        try {
            $qtd = $pdo->exec("DELETE FROM pedidos WHERE status = 'cancelado'");
            flash($qtd > 0
                ? $qtd . ($qtd === 1 ? ' pedido cancelado removido.' : ' pedidos cancelados removidos.')
                : 'Não há pedidos cancelados para remover.');
        } catch (Throwable $e) {
            flash('Não foi possível limpar os pedidos cancelados: ' . $e->getMessage());
        }
    }
    header('Location: pedidos.php'); exit;
}

$filtro = $_GET['status'] ?? '';
$pedidos = []; $erroBanco = null;
$cancelados = 0;
try {
    $pedidos = (new Pedido)->listarTodos($filtro);
    $cancelados = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'cancelado'")->fetchColumn();
}
catch (Throwable $e) { $erroBanco = $e->getMessage(); }

$vendido = 0; foreach ($pedidos as $p) if ($p['status'] === 'pago') $vendido += $p['valor_total'];
$rot = ['cartao' => 'Cartão', 'pix' => 'Pix', 'boleto' => 'Boleto'];

pagina_topo();
?>
<?php if ($erroBanco !== null): ?>
  <div class="caixa">
    <p class="erro">Não foi possível carregar os pedidos.</p>
    <p><small>Detalhe técnico: <?= h($erroBanco) ?></small></p>
    <p>Isso costuma acontecer quando o banco ainda não foi criado. Importe o arquivo <strong>sql/script.sql</strong> no phpMyAdmin.</p>
  </div>
<?php else: ?>
<div style="display:flex;justify-content:center;margin:22px 0 0"><a href="cadastrar_pedido.php" style="background:var(--ac);color:#fff;padding:8px 22px;border-radius:99px;font-size:14px;font-weight:600">+ Novo pedido</a></div>
<form class="busca" method="get" action="pedidos.php">
  <select name="status" aria-label="Filtrar por status" onchange="this.form.submit()">
    <option value="">Todos os status</option>
    <?php foreach (['pendente', 'pago', 'cancelado'] as $s): ?>
      <option value="<?= $s ?>"<?= $filtro === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
</form>
<p class="total">Total pago na lista<strong><?= moeda($vendido) ?></strong></p>
<?php if ($cancelados > 0): ?>
<div class="limpar">
  <form method="POST" onsubmit="return confirm('Remover definitivamente <?= $cancelados ?> pedido(s) cancelado(s)? Esta ação não pode ser desfeita.')">
    <?= csrfCampo() ?>
    <button name="limpar_cancelados" value="1">Limpar pedidos cancelados (<?= $cancelados ?>)</button>
  </form>
</div>
<?php endif; ?>

<section class="bloco">
  <h2>Pedidos</h2>
  <?php if (!$pedidos): ?><p class="vazio">Nenhum pedido encontrado.</p><?php endif; ?>
  <div class="grade">
    <?php foreach ($pedidos as $p): ?>
    <article class="card">
      <div class="topo-pedido">
        <span>Pedido #<?= (int)$p['id'] ?> &middot; <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></span>
        <span class="tag <?= h($p['status']) ?>"><?= h($p['status']) ?></span>
      </div>
      <h3><?= h($p['titulo_jogo']) ?></h3>
      <p class="info"><?= (int)$p['quantidade'] ?> &times; &middot; <?= h($rot[$p['forma_pagamento']] ?? $p['forma_pagamento']) ?></p>
      <strong class="preco"><?= moeda($p['valor_total']) ?></strong>
      <p class="cliente"><?= h($p['cliente'] ?? 'Usuário removido') ?><br><small><?= h($p['email'] ?? '') ?></small></p>
      <div class="botoes">
        <a class="editar-ped" href="editar_pedido.php?id=<?= (int)$p['id'] ?>">Editar</a>
        <form method="POST" action="../controllers/deletar_pedido.php" onsubmit="return confirm('Excluir o pedido #<?= (int)$p['id'] ?>? Esta ação não pode ser desfeita.')"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="perigo">Excluir</button></form>
        <?php if ($p['status'] === 'pendente'): ?>
          <form method="POST" action="../controllers/confirmar_pedido.php"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="ok">Confirmar pagamento</button></form>
        <?php endif; ?>
        <?php if ($p['status'] !== 'cancelado'): ?>
          <form method="POST" action="../controllers/cancelar_pedido.php" onsubmit="return confirm('Cancelar o pedido #<?= (int)$p['id'] ?>? O estoque será devolvido.')"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="perigo">Cancelar</button></form>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php pagina_fim();
