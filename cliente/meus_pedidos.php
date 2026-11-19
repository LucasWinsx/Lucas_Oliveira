<?php
require_once '../admin/config/auth.php';
require_once '../admin/models/pedidos.php';
require_once __DIR__ . '/_ps_layout.php';
exigirLogin();
$pedidos = (new Pedido)->listarDoUsuario((int)usuario()['id']);
$rot = ['cartao' => 'Cartão', 'pix' => 'Pix', 'boleto' => 'Boleto'];
ps_topo('Meus pedidos', '', 'pedidos');
?>
<main class="pg">
<h2>Meus pedidos</h2>
<div class="rolagem"><table>
<tr><th>#</th><th>Jogo</th><th>Qtd</th><th>Total</th><th>Pagamento</th><th>Status</th><th>Data</th><th></th></tr>
<?php if (!$pedidos): ?><tr><td colspan="8">Você ainda não fez nenhuma compra. <a href="<?= BASE ?>/catalogo.php">Ver jogos</a></td></tr><?php endif; ?>
<?php foreach ($pedidos as $p): ?>
<tr>
  <td><?= $p['id'] ?></td><td><?= h($p['titulo_jogo']) ?></td><td><?= $p['quantidade'] ?></td>
  <td><?= moeda($p['valor_total']) ?></td><td><?= $rot[$p['forma_pagamento']] ?></td>
  <td><span class="tag <?= $p['status'] ?>"><?= $p['status'] ?></span></td>
  <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
  <td><?php if ($p['status'] === 'pendente'): ?><a href="pagamento.php?id=<?= $p['id'] ?>">Pagar</a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table></div>
</main>
<?php ps_rodape();
