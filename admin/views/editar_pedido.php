<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/pedidos.php';
exigirAdmin();

$m = new Pedido();
$id = (int)($_GET['id'] ?? 0);
$p = $m->buscar($id);
if (!$p) { flash('Pedido não encontrado.'); header('Location: pedidos.php'); exit; }

$st = $pdo->prepare('SELECT nome, email FROM usuarios WHERE id=?');
$st->execute([$p['usuario_id']]);
$cli = $st->fetch();

$erros = [];
$v = ['quantidade' => (string)$p['quantidade'], 'forma_pagamento' => $p['forma_pagamento'], 'status' => $p['status']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        try {
            $m->atualizar($id, (int)$v['quantidade'], (string)$v['forma_pagamento'], (string)$v['status']);
            flash('Pedido #' . $id . ' atualizado com sucesso.');
            header('Location: pedidos.php'); exit;
        } catch (Throwable $e) { $erros[] = $e->getMessage(); }
    }
}
pagina_topo('Editar pedido', 'pedidos');
?>
<h2>Editar pedido #<?= (int)$p['id'] ?></h2>
<form class="cartao-form" method="POST" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $e) echo h($e) . '<br>'; ?></div><?php endif; ?>
  <p><strong>Jogo:</strong> <?= h($p['titulo_jogo']) ?><br>
     <strong>Cliente:</strong> <?= h($cli['nome'] ?? 'Usuário removido') ?> <span class="pequeno"><?= h($cli['email'] ?? '') ?></span><br>
     <strong>Valor atual:</strong> <?= moeda($p['valor_total']) ?></p>
  <label>Quantidade <input type="number" name="quantidade" min="1" step="1" required data-regra="inteiro" value="<?= h($v['quantidade']) ?>"></label>
  <label>Forma de pagamento
    <select name="forma_pagamento">
      <?php foreach (['pix' => 'Pix', 'cartao' => 'Cartão', 'boleto' => 'Boleto'] as $k => $r): ?><option value="<?= $k ?>"<?= $v['forma_pagamento'] === $k ? ' selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>Status
    <select name="status">
      <?php foreach (['pendente' => 'Pendente', 'pago' => 'Pago', 'cancelado' => 'Cancelado'] as $k => $r): ?><option value="<?= $k ?>"<?= $v['status'] === $k ? ' selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
    </select>
  </label>
  <p>Ao mudar a quantidade ou cancelar/reativar o pedido, o estoque do jogo é ajustado automaticamente.</p>
  <div class="form-botoes"><button>Salvar alterações</button><a class="cancelar" href="pedidos.php">Cancelar</a></div>
</form>
<?php pagina_fim();
