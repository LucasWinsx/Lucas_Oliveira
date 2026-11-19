<?php
require_once '../admin/config/auth.php';
require_once '../admin/config/pix.php';
require_once '../admin/models/pedidos.php';
require_once __DIR__ . '/_ps_layout.php';
exigirLogin();
$p = (new Pedido)->buscar((int)($_GET['id'] ?? 0));
if (!$p || (usuario()['tipo'] !== 'admin' && (int)$p['usuario_id'] !== (int)usuario()['id'])) {
    flash('Pedido não encontrado.'); header('Location: ' . BASE . '/catalogo.php'); exit;
}
ps_topo('Pagamento do pedido #' . $p['id'], '', 'pedidos');
?>
<main class="pg estreita">
<h2>Pedido #<?= $p['id'] ?></h2>
<div class="resumo">
  <p><?= h($p['titulo_jogo']) ?> × <?= $p['quantidade'] ?> — <strong><?= moeda($p['valor_total']) ?></strong></p>
  <p>Status: <span class="tag <?= $p['status'] ?>"><?= $p['status'] ?></span></p>
  <?php $parc = (int)($_SESSION['parcelas'][$p['id']] ?? 0); if ($p['forma_pagamento'] === 'cartao' && $parc > 1): ?>
    <p>Cartão em <?= $parc ?>x de <strong><?= moeda($p['valor_total'] / $parc) ?></strong> sem juros</p>
  <?php endif; ?>
</div>

<?php if ($p['status'] === 'cancelado'): ?>
  <p class="erro">Este pedido foi cancelado.</p>

<?php elseif ($p['status'] === 'pago'): ?>
  <p class="ok-msg">Pagamento confirmado. Obrigado pela compra!</p>

<?php elseif ($p['forma_pagamento'] === 'pix' && !pixConfigurado()): ?>
  <p class="erro">O pagamento por Pix ainda não está configurado nesta loja. Fale com o administrador.</p>
  <?php if (($_SESSION['u']['tipo'] ?? '') === 'admin'): ?><p><small>Administrador: defina PIX_CHAVE em admin/config/pix.php.</small></p><?php endif; ?>

<?php elseif ($p['forma_pagamento'] === 'pix'): $payload = pixPayload($p['valor_total'], 'PEDIDO' . $p['id']); ?>
  <h3>Pague com Pix</h3>
  <p>Abra o app do seu banco e escaneie o QR Code, ou use o Pix copia e cola.</p>
  <div id="qr" class="qr"></div>
  <textarea id="copia" readonly rows="4"><?= h($payload) ?></textarea>
  <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('copia').value);this.textContent='Copiado!'">Copiar código Pix</button>
  <p><small>Após o pagamento, o administrador confirma o recebimento e o status muda para "pago".</small></p>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    if (window.QRCode) new QRCode(document.getElementById('qr'), {text: <?= json_encode($payload) ?>, width: 240, height: 240, correctLevel: QRCode.CorrectLevel.M});
    else document.getElementById('qr').textContent = 'Não foi possível carregar o QR Code. Use o código copia e cola abaixo.';
  </script>

<?php elseif ($p['forma_pagamento'] === 'boleto'):
  $linha = sprintf('23793.38128 %05d.%06d 00000.000000 1 %014d', $p['id'], $p['id'] * 7, round($p['valor_total'] * 100));
  $venc = date('d/m/Y', strtotime($p['criado_em'] . ' +3 days')); ?>
  <h3>Boleto bancário</h3>
  <div class="boleto">
    <p>Vencimento: <strong><?= $venc ?></strong></p>
    <p>Valor: <strong><?= moeda($p['valor_total']) ?></strong></p>
    <p>Linha digitável:</p>
    <code><?= $linha ?></code>
  </div>
  <p><small>Boleto de demonstração. Após o pagamento, o administrador confirma o recebimento.</small></p>
  <button type="button" onclick="window.print()">Imprimir</button>
<?php endif; ?>
<p><a href="meus_pedidos.php">Ver meus pedidos</a> · <a href="<?= BASE ?>/cliente/meus_jogos.php">Meus jogos</a> · <a href="<?= BASE ?>/catalogo.php">Continuar comprando</a></p>
</main>
<?php ps_rodape();
