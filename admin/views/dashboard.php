<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/relatorios.php';
exigirAdmin();

$rel = new Relatorio();
$r       = $rel->resumo();
$meses   = $rel->vendasPorMes(6);
$porCat  = $rel->vendasPorCategoria();
$top     = $rel->maisVendidos(5);
$baixo   = $rel->estoqueBaixo(6, 5);
$ultimos = $rel->ultimosPedidos(6);
$maxMes  = max(1.0, max(array_column($meses, 'total')));

pagina_topo('Dashboard', 'dashboard');
?>
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin:0 0 30px}
.kpi{border:1px solid var(--bd);border-radius:10px;padding:16px 18px}
.kpi small{display:block;color:var(--mt);font-size:12px;text-transform:uppercase;letter-spacing:.04em}
.kpi strong{display:block;font-size:26px;font-weight:600;margin-top:4px}
.paineis{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:22px}
.painel{border:1px solid var(--bd);border-radius:10px;padding:18px 20px;min-width:0}
.painel h3{margin:0 0 14px;font-size:17px;font-weight:600}
.barras{display:flex;align-items:flex-end;gap:12px;height:180px;padding-top:8px}
.barra{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;min-width:0}
.barra div{width:100%;max-width:56px;background:linear-gradient(180deg,#0070d1,#0d1b4c);border-radius:6px 6px 0 0;min-height:2px}
.barra span{font-size:12px;color:var(--mt);margin-top:6px}
.barra b{font-size:12px;font-weight:600;margin-bottom:4px;white-space:nowrap}
.lista{list-style:none;margin:0;padding:0}
.lista li{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--bd);font-size:14px}
.lista li:last-child{border-bottom:0}
.lista .vazio{justify-content:center}
.alerta{color:#d93025;font-weight:600}
</style>
<h2>Dashboard</h2>
<div class="acoes-topo"><a class="btn-add" href="relatorio_vendas.php">Relatório de vendas</a></div>

<div class="kpis">
  <div class="kpi"><small>Faturamento (pedidos pagos)</small><strong><?= moeda($r['faturamento']) ?></strong></div>
  <div class="kpi"><small>Vendas realizadas</small><strong><?= (int)$r['vendas'] ?></strong></div>
  <div class="kpi"><small>Unidades vendidas</small><strong><?= (int)$r['unidades'] ?></strong></div>
  <div class="kpi"><small>Ticket médio</small><strong><?= moeda($r['ticket_medio']) ?></strong></div>
  <div class="kpi"><small>Pedidos pendentes</small><strong><?= (int)$r['pendentes'] ?></strong></div>
  <div class="kpi"><small>Pedidos cancelados</small><strong><?= (int)$r['cancelados'] ?></strong></div>
  <div class="kpi"><small>Clientes</small><strong><?= (int)$r['clientes'] ?></strong></div>
  <div class="kpi"><small>Jogos / Categorias</small><strong><?= (int)$r['jogos'] ?> / <?= (int)$r['categorias'] ?></strong></div>
</div>

<div class="paineis">
  <section class="painel">
    <h3>Faturamento dos últimos 6 meses</h3>
    <div class="barras" role="img" aria-label="Gráfico de barras do faturamento mensal">
      <?php foreach ($meses as $m): $alt = round($m['total'] / $maxMes * 100); ?>
        <div class="barra">
          <b><?= $m['total'] > 0 ? h(number_format($m['total'], 0, ',', '.')) : '' ?></b>
          <div style="height:<?= (int)$alt ?>%" title="<?= h(moeda($m['total'])) ?>"></div>
          <span><?= h($m['rotulo']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="painel">
    <h3>Vendas por categoria</h3>
    <ul class="lista">
      <?php if (!$porCat): ?><li class="vazio">Ainda não há vendas.</li><?php endif; ?>
      <?php foreach ($porCat as $c): ?>
        <li><span><?= h($c['categoria']) ?> <small class="pequeno">(<?= (int)$c['unidades'] ?> un.)</small></span><strong><?= moeda($c['total']) ?></strong></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="painel">
    <h3>Jogos mais vendidos</h3>
    <ul class="lista">
      <?php if (!$top): ?><li class="vazio">Ainda não há vendas.</li><?php endif; ?>
      <?php foreach ($top as $t): ?>
        <li><span><?= h($t['titulo_jogo']) ?></span><strong><?= (int)$t['unidades'] ?> un.</strong></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="painel">
    <h3>Estoque baixo (5 ou menos)</h3>
    <ul class="lista">
      <?php if (!$baixo): ?><li class="vazio">Nenhum jogo com estoque baixo.</li><?php endif; ?>
      <?php foreach ($baixo as $b): ?>
        <li><a href="editar_game.php?id=<?= (int)$b['id'] ?>"><?= h($b['titulo']) ?></a>
            <strong class="<?= (int)$b['estoque'] === 0 ? 'alerta' : '' ?>"><?= (int)$b['estoque'] === 0 ? 'Esgotado' : (int)$b['estoque'] . ' un.' ?></strong></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="painel" style="grid-column:1/-1">
    <h3>Últimos pedidos</h3>
    <div class="tabela-wrap">
    <table class="tabela">
      <thead><tr><th>#</th><th>Data</th><th>Cliente</th><th>Jogo</th><th>Qtd</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (!$ultimos): ?><tr><td colspan="7" class="vazio">Nenhum pedido ainda.</td></tr><?php endif; ?>
      <?php foreach ($ultimos as $p): ?>
        <tr>
          <td><a href="editar_pedido.php?id=<?= (int)$p['id'] ?>"><?= (int)$p['id'] ?></a></td>
          <td><?= h(date('d/m/Y H:i', strtotime($p['criado_em']))) ?></td>
          <td><?= h($p['cliente'] ?? 'Usuário removido') ?></td>
          <td><?= h($p['titulo_jogo']) ?></td>
          <td><?= (int)$p['quantidade'] ?></td>
          <td><?= moeda($p['valor_total']) ?></td>
          <td><span class="tag <?= h($p['status']) ?>"><?= h($p['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </section>
</div>
<?php pagina_fim();
