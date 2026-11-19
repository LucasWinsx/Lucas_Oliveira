<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/pedidos.php';
exigirAdmin();

$clientes = $pdo->query("SELECT id, nome, email FROM usuarios WHERE tipo='cliente' ORDER BY nome")->fetchAll();
$jogos    = $pdo->query('SELECT id, titulo, preco, estoque FROM games WHERE estoque > 0 ORDER BY titulo')->fetchAll();

$erros = [];
$v = ['usuario_id' => '', 'game_id' => '', 'quantidade' => '1', 'forma_pagamento' => 'pix', 'status' => 'pendente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        $uid = (int)$v['usuario_id']; $gid = (int)$v['game_id']; $qtd = (int)$v['quantidade'];
        if ($uid < 1) $erros[] = 'Escolha o cliente.';
        if ($gid < 1) $erros[] = 'Escolha o jogo.';
        if ($qtd < 1) $erros[] = 'A quantidade deve ser de pelo menos 1.';
        if (!in_array($v['forma_pagamento'], ['cartao', 'pix', 'boleto'], true)) $erros[] = 'Forma de pagamento inválida.';
        if (!in_array($v['status'], ['pendente', 'pago'], true)) $erros[] = 'Status inválido.';
        if (!$erros) {
            try {
                $id = (new Pedido())->criar($uid, $gid, $qtd, $v['forma_pagamento'], $v['status']);
                flash('Pedido #' . $id . ' criado com sucesso.');
                header('Location: pedidos.php'); exit;
            } catch (Throwable $e) { $erros[] = $e->getMessage(); }
        }
    }
}
pagina_topo('Novo pedido', 'pedidos');
?>
<h2>Novo pedido</h2>
<form class="cartao-form" method="POST" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $m) echo h($m) . '<br>'; ?></div><?php endif; ?>
  <label>Cliente
    <select name="usuario_id" required>
      <option value="">Selecione…</option>
      <?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (string)$c['id'] === (string)$v['usuario_id'] ? ' selected' : '' ?>><?= h($c['nome']) ?> (<?= h($c['email']) ?>)</option><?php endforeach; ?>
    </select>
  </label>
  <label>Jogo
    <select name="game_id" required>
      <option value="">Selecione…</option>
      <?php foreach ($jogos as $j): ?><option value="<?= (int)$j['id'] ?>"<?= (string)$j['id'] === (string)$v['game_id'] ? ' selected' : '' ?>><?= h($j['titulo']) ?> — <?= moeda($j['preco']) ?> (estoque: <?= (int)$j['estoque'] ?>)</option><?php endforeach; ?>
    </select>
  </label>
  <label>Quantidade <input type="number" name="quantidade" min="1" step="1" required data-regra="inteiro" value="<?= h($v['quantidade']) ?>"></label>
  <label>Forma de pagamento
    <select name="forma_pagamento">
      <?php foreach (['pix' => 'Pix', 'cartao' => 'Cartão', 'boleto' => 'Boleto'] as $k => $r): ?><option value="<?= $k ?>"<?= $v['forma_pagamento'] === $k ? ' selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>Status inicial
    <select name="status">
      <option value="pendente"<?= $v['status'] === 'pendente' ? ' selected' : '' ?>>Pendente</option>
      <option value="pago"<?= $v['status'] === 'pago' ? ' selected' : '' ?>>Pago</option>
    </select>
  </label>
  <p>O estoque do jogo é reduzido automaticamente.</p>
  <div class="form-botoes"><button>Criar pedido</button><a class="cancelar" href="pedidos.php">Cancelar</a></div>
</form>
<?php pagina_fim();
