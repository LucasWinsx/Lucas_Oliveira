<?php
require_once __DIR__ . '/../admin/config/auth.php';
require_once __DIR__ . '/../admin/models/pedidos.php';
require_once __DIR__ . '/_ps_layout.php';

function luhn($n) {
    if (!preg_match('/^\d{13,19}$/', $n)) return false;
    $s = 0; $alt = false;
    for ($i = strlen($n) - 1; $i >= 0; $i--) { $d = (int)$n[$i]; if ($alt) { $d *= 2; if ($d > 9) $d -= 9; } $s += $d; $alt = !$alt; }
    return $s % 10 === 0;
}

/* Lê os jogos do carrinho. Tira o que foi apagado ou esgotou e limita a 1 unidade por jogo. */
function carregarCarrinho(PDO $pdo): array {
    $_SESSION['carrinho'] ??= [];
    $copia = $_SESSION['carrinho'];
    if (!$copia) return [[], []];
    $ids = array_map('intval', array_keys($copia));
    $st = $pdo->prepare('SELECT * FROM games WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $st->execute($ids);
    $jogos = [];
    foreach ($st->fetchAll() as $g) $jogos[(int)$g['id']] = $g;

    $itens = []; $avisos = [];
    foreach ($copia as $gid => $q) {
        $gid = (int)$gid; $q = (int)$q;
        if (!isset($jogos[$gid])) { unset($_SESSION['carrinho'][$gid]); continue; }
        $g = $jogos[$gid]; $est = (int)$g['estoque'];
        if ($est <= 0) { unset($_SESSION['carrinho'][$gid]); $avisos[] = '“' . $g['titulo'] . '” esgotou e saiu do carrinho.'; continue; }
        if ($q > 1) { $q = 1; $_SESSION['carrinho'][$gid] = 1; $avisos[] = 'Limite de 1 unidade por jogo: “' . $g['titulo'] . '” foi ajustado para 1 unidade.'; }
        if ($q < 1) { unset($_SESSION['carrinho'][$gid]); continue; }
        $itens[] = ['g' => $g, 'q' => $q];
    }
    return [$itens, $avisos];
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'finalizar') {
    exigirLogin();
    try {
        if (!csrfOk()) throw new Exception('Sessão expirada. Tente novamente.');
        [$itens] = carregarCarrinho($pdo);
        if (!$itens) throw new Exception('Seu carrinho está vazio.');
        $forma = $_POST['forma'] ?? '';
        if (!in_array($forma, ['cartao', 'pix', 'boleto'], true)) throw new Exception('Escolha a forma de pagamento.');
        if ($forma === 'cartao') {
            // Dados do cartão são apenas validados; NUNCA são gravados no banco.
            if (!luhn(preg_replace('/\D/', '', $_POST['numero'] ?? ''))) throw new Exception('Número do cartão inválido.');
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $_POST['validade'] ?? '')) throw new Exception('Validade inválida (use MM/AA).');
            if (!preg_match('/^\d{3,4}$/', $_POST['cvv'] ?? '')) throw new Exception('CVV inválido.');
            if (trim($_POST['titular'] ?? '') === '') throw new Exception('Informe o nome do titular.');
        }
        // Um pedido por jogo (é como o sistema de pedidos funciona). Se algum falhar, os já criados são cancelados e o estoque volta.
        $ped = new Pedido; $criados = [];
        try {
            foreach ($itens as $i) $criados[] = $ped->criar((int)usuario()['id'], (int)$i['g']['id'], $i['q'], $forma);
        } catch (Throwable $e) {
            foreach ($criados as $pid) { try { $ped->cancelar($pid); } catch (Throwable $x) {} }
            throw new Exception($e->getMessage() . ' Revise o carrinho e tente de novo.');
        }
        $_SESSION['carrinho'] = [];
        if (count($criados) === 1) { header('Location: pagamento.php?id=' . $criados[0]); exit; }
        flash(count($criados) . ' pedidos criados. ' . ($forma === 'cartao' ? 'Pagamento no cartão aprovado.' : 'Pague cada pedido pendente em “Pagar”.'));
        header('Location: meus_pedidos.php'); exit;
    } catch (Exception $e) { $erro = $e->getMessage(); }
}

[$itens, $avisos] = carregarCarrinho($pdo);
$u = usuario();
$total = 0; $qtdTotal = 0;
foreach ($itens as $i) { $total += (float)$i['g']['preco'] * $i['q']; $qtdTotal += $i['q']; }

ob_start(); ?>
<style>
.cr{padding:36px var(--px) 56px}
.cr h1{margin:0 0 6px;font-size:30px;font-weight:600}
.cr .sub{margin:0 0 28px;color:var(--mt)}
.cr-wrap{display:flex;gap:40px;align-items:flex-start}
.cr-lista{flex:1;min-width:0;display:grid;gap:0}
.cr-item{display:grid;grid-template-columns:200px 1fr auto;gap:20px;align-items:center;padding:18px 0;border-bottom:1px solid var(--bd)}
.cr-item:first-child{border-top:1px solid var(--bd)}
.cr-capa{position:relative;aspect-ratio:16/9;border-radius:8px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45);display:block}
.cr-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.cr-info a.t{font-size:19px;line-height:1.3}.cr-info a.t:hover{color:var(--ac)}
.cr-info p{margin:4px 0 0;color:var(--mt);font-size:14px}
.cr-dir{text-align:right}
.cr-preco{font-size:20px;margin:0 0 8px}
.cr-qtd{display:flex;gap:8px;align-items:center;justify-content:flex-end;margin:0 0 6px}
.cr-qtd label{color:var(--mt);font-size:14px}
.cr-qtd input{width:64px;padding:6px 8px;background:#fff;color:var(--tx);border:1px solid var(--bd);border-radius:8px;font:inherit}
.cr-rem{background:none;border:0;color:var(--ac);font:inherit;cursor:pointer;padding:0}
.cr-rem:hover{text-decoration:underline}
.cr-resumo{flex:0 0 400px;background:var(--bg2);border:1px solid var(--bd);border-radius:10px;padding:28px 30px;position:sticky;top:90px}
.cr-resumo h2{margin:0 0 14px;font-size:22px;font-weight:600}
.cr-linha{display:flex;justify-content:space-between;margin:0 0 8px;color:#4a5063}
.cr-total{display:flex;justify-content:space-between;font-size:26px;margin:14px 0 20px;padding-top:14px;border-top:1px solid var(--bd)}
.cr-resumo fieldset{border:1px solid var(--bd);border-radius:10px;margin:0 0 14px;padding:10px 14px}
.cr-resumo legend{padding:0 6px;color:var(--mt);font-size:14px}
.op{display:flex;gap:8px;align-items:center;padding:3px 0}
.op input{width:auto;margin:0}
.cr-cartao label{display:block;margin-bottom:10px;font-size:14px;color:var(--mt)}
.cr-cartao input{display:block;width:100%;margin-top:4px;padding:9px 12px;background:#fff;color:var(--tx);border:1px solid var(--bd);border-radius:10px;font:inherit}
.cr-cartao input:focus,.cr-qtd input:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.cr-fin{display:block;width:100%;background:var(--ac);color:#fff;border:0;border-radius:999px;padding:13px 0;font:inherit;font-size:18px;font-weight:600;text-align:center;cursor:pointer}
.cr-fin:hover{background:var(--ac2)}
.cr-erro{margin:0 0 14px;padding:10px 14px;border-radius:10px;background:#fdeceb;color:#d93025}
.cr-aviso{margin:0 0 14px;padding:10px 14px;border:1px solid #ffb703;background:#fff8e1;color:#7a5600;border-radius:6px}
.cr-vazio{color:#4a5063}.cr-vazio a{color:var(--ac);font-weight:600}
.cr-nota{margin:12px 0 0;font-size:13px;color:var(--mt)}
@media(max-width:1000px){
  .cr-wrap{flex-direction:column}.cr-resumo{flex-basis:auto;width:100%;position:static}
  .cr-item{grid-template-columns:120px 1fr}.cr-dir{grid-column:1/-1;text-align:left}.cr-qtd{justify-content:flex-start}
}
</style>
<?php $css = ob_get_clean();
ps_topo('Carrinho', $css, 'carrinho');
?>
<main class="cr">
  <h1>Carrinho</h1>
  <p class="sub"><?= $qtdTotal ?> item(ns)</p>

  <?php foreach ($avisos as $a): ?><p class="cr-aviso"><?= h($a) ?></p><?php endforeach; ?>

  <?php if (!$itens): ?>
    <p class="cr-vazio">Seu carrinho está vazio. <a href="<?= BASE ?>/catalogo.php">Ver catálogo</a></p>
  <?php else: ?>
  <div class="cr-wrap">
    <section class="cr-lista" aria-label="Itens do carrinho">
    <?php foreach ($itens as $i): $g = $i['g']; $gid = (int)$g['id'];
        $img = !empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : ''; ?>
      <article class="cr-item">
        <a class="cr-capa" href="jogo.php?id=<?= $gid ?>" aria-label="<?= h($g['titulo']) ?>">
          <?php if ($img): ?><img src="<?= h($img) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?>
        </a>
        <div class="cr-info">
          <a class="t" href="jogo.php?id=<?= $gid ?>"><?= h($g['titulo']) ?></a>
          <p><?= moeda($g['preco']) ?> cada · <?= (int)$g['estoque'] ?> em estoque</p>
        </div>
        <div class="cr-dir">
          <p class="cr-preco"><?= moeda((float)$g['preco'] * $i['q']) ?></p>
          <p class="cr-qtd"><label>Qtd</label> <strong>1</strong></p>
          <form method="POST" action="carrinho_acao.php" style="margin:0">
            <?= csrfCampo() ?><input type="hidden" name="acao" value="remove"><input type="hidden" name="game_id" value="<?= $gid ?>"><input type="hidden" name="volta" value="carrinho">
            <button class="cr-rem">Remover</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
    </section>

    <aside class="cr-resumo" aria-label="Resumo do pedido">
      <h2>Resumo do pedido</h2>
      <p class="cr-linha"><span>Itens (<?= $qtdTotal ?>)</span><span><?= moeda($total) ?></span></p>
      <p class="cr-total"><span>Total</span><strong><?= moeda($total) ?></strong></p>

      <?php if (!$u): ?>
        <a class="cr-fin" href="<?= BASE ?>/login.php">Entrar para finalizar</a>
        <p class="cr-nota">Seu carrinho fica guardado enquanto você entra.</p>
      <?php else: ?>
        <?php if ($erro): ?><p class="cr-erro"><?= h($erro) ?></p><?php endif; ?>
        <form method="POST" id="f">
          <?= csrfCampo() ?><input type="hidden" name="acao" value="finalizar">
          <fieldset><legend>Forma de pagamento</legend>
            <label class="op"><input type="radio" name="forma" value="pix" required <?= ($_POST['forma'] ?? '') === 'pix' ? 'checked' : '' ?>> Pix (QR Code)</label>
            <label class="op"><input type="radio" name="forma" value="cartao" <?= ($_POST['forma'] ?? '') === 'cartao' ? 'checked' : '' ?>> Cartão</label>
            <label class="op"><input type="radio" name="forma" value="boleto" <?= ($_POST['forma'] ?? '') === 'boleto' ? 'checked' : '' ?>> Boleto</label>
          </fieldset>
          <div id="cartao" class="cr-cartao" hidden>
            <label>Nome do titular <input name="titular" autocomplete="cc-name"></label>
            <label>Número do cartão <input name="numero" inputmode="numeric" maxlength="23" autocomplete="cc-number"></label>
            <label>Validade (MM/AA) <input name="validade" maxlength="5" placeholder="MM/AA" autocomplete="cc-exp"></label>
            <label>CVV <input name="cvv" inputmode="numeric" maxlength="4" autocomplete="cc-csc"></label>
          </div>
          <button class="cr-fin">Finalizar compra</button>
        </form>
        <p class="cr-nota">Limite de 1 unidade por jogo. Cada jogo vira um pedido. Com Pix ou boleto, você paga cada pedido em “Meus pedidos”.</p>
      <?php endif; ?>
    </aside>
  </div>
  <?php endif; ?>
</main>

<script>
const radios = document.querySelectorAll('input[name=forma]'), cartao = document.getElementById('cartao');
const mostrar = () => { if (cartao) cartao.hidden = !document.querySelector('input[name=forma][value=cartao]:checked'); };
radios.forEach(r => r.addEventListener('change', mostrar)); mostrar();
</script>
<?php ps_rodape();
