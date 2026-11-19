<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/relatorios.php';
exigirAdmin();

// período (padrão: últimos 30 dias)
$hoje = new DateTime('today');
$de   = $_GET['de']  ?? (clone $hoje)->modify('-30 day')->format('Y-m-d');
$ate  = $_GET['ate'] ?? $hoje->format('Y-m-d');
$valida = function (string $d) { $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; };
$erro = '';
if (!$valida($de) || !$valida($ate)) { $erro = 'Datas inválidas.'; $de = (clone $hoje)->modify('-30 day')->format('Y-m-d'); $ate = $hoje->format('Y-m-d'); }
elseif ($de > $ate) { $erro = 'A data inicial não pode ser maior que a final.'; [$de, $ate] = [$ate, $de]; }

$vendas = (new Relatorio())->vendasPeriodo($de, $ate);
$rot = ['cartao' => 'Cartão', 'pix' => 'Pix', 'boleto' => 'Boleto'];

// exportação em CSV (abre no Excel)
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="vendas_' . $de . '_a_' . $ate . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Pedido', 'Data', 'Cliente', 'Jogo', 'Quantidade', 'Pagamento', 'Total'], ';');
    foreach ($vendas as $v) {
        // evita que o Excel interprete o conteúdo como fórmula
        $cel = fn($t) => preg_match('/^[=+\-@]/', (string)$t) ? "'" . $t : (string)$t;
        fputcsv($o, [$v['id'], date('d/m/Y H:i', strtotime($v['criado_em'])), $cel($v['cliente'] ?? ''), $cel($v['titulo_jogo']),
                     $v['quantidade'], $rot[$v['forma_pagamento']] ?? $v['forma_pagamento'], number_format((float)$v['valor_total'], 2, ',', '')], ';');
    }
    fclose($o); exit;
}

$total = array_sum(array_column($vendas, 'valor_total'));
$unid  = array_sum(array_column($vendas, 'quantidade'));

pagina_topo('Relatório de vendas', 'dashboard');
?>
<h2>Relatório de vendas</h2>
<?php if ($erro): ?><p class="erro" style="max-width:640px;margin:0 auto 16px;padding:10px 14px;border-radius:10px;background:#fdeceb;color:#d93025"><?= h($erro) ?></p><?php endif; ?>
<form class="busca-simples" method="get" action="relatorio_vendas.php" style="flex-wrap:wrap;align-items:center">
  <label class="pequeno">De <input type="date" name="de" value="<?= h($de) ?>" required></label>
  <label class="pequeno">Até <input type="date" name="ate" value="<?= h($ate) ?>" required></label>
  <button>Filtrar</button>
  <a class="btn-add" href="relatorio_vendas.php?de=<?= h($de) ?>&amp;ate=<?= h($ate) ?>&amp;csv=1">Exportar CSV</a>
</form>
<p class="pequeno" style="text-align:center">Considera apenas pedidos com status <strong>pago</strong>.
  Total no período: <strong><?= moeda($total) ?></strong> &middot; <?= count($vendas) ?> venda(s) &middot; <?= (int)$unid ?> unidade(s)</p>
<div class="tabela-wrap">
<table class="tabela">
  <thead><tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>Jogo</th><th>Qtd</th><th>Pagamento</th><th>Total</th></tr></thead>
  <tbody>
  <?php if (!$vendas): ?><tr><td colspan="7" class="vazio">Nenhuma venda no período.</td></tr><?php endif; ?>
  <?php foreach ($vendas as $v): ?>
    <tr>
      <td>#<?= (int)$v['id'] ?></td>
      <td><?= h(date('d/m/Y H:i', strtotime($v['criado_em']))) ?></td>
      <td><?= h($v['cliente'] ?? 'Usuário removido') ?></td>
      <td><?= h($v['titulo_jogo']) ?></td>
      <td><?= (int)$v['quantidade'] ?></td>
      <td><?= h($rot[$v['forma_pagamento']] ?? $v['forma_pagamento']) ?></td>
      <td><?= moeda($v['valor_total']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<p style="text-align:center;margin-top:18px"><a href="dashboard.php" class="pequeno">← Voltar ao dashboard</a></p>
<?php pagina_fim();
