<?php
require_once '../admin/config/auth.php';
require_once '../admin/models/pedidos.php';
require_once '../admin/models/games.php';
require_once __DIR__ . '/_ps_layout.php';
exigirLogin();
$id = (int)($_GET['id'] ?? $_POST['game_id'] ?? 0);
$st = $pdo->prepare(Game::SQL_SELECT . ' WHERE g.id=?'); $st->execute([$id]);
$g = $st->fetch();
if (!$g) { flash('Jogo não encontrado.'); header('Location: ' . BASE . '/catalogo.php'); exit; }

function luhn($n) {
    if (!preg_match('/^\d{13,19}$/', $n)) return false;
    $s = 0; $alt = false;
    for ($i = strlen($n) - 1; $i >= 0; $i--) { $d = (int)$n[$i]; if ($alt) { $d *= 2; if ($d > 9) $d -= 9; } $s += $d; $alt = !$alt; }
    return $s % 10 === 0;
}

$preco = (float)$g['preco'];
$maxParc = max(1, min(12, (int)floor($preco / 20)));   // até 12x sem juros, parcela mínima de R$ 20
$img = !empty($g['imagem']) ? BASE . '/admin/public/imagens/' . rawurlencode($g['imagem']) : '';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrfOk()) throw new Exception('Sessão expirada. Tente novamente.');
        $forma = $_POST['forma'] ?? '';
        if (!in_array($forma, ['cartao', 'pix', 'boleto'], true)) throw new Exception('Escolha a forma de pagamento.');
        $parc = 1;
        if ($forma === 'cartao') {
            // Dados do cartão são apenas validados; NUNCA são gravados no banco.
            if (!luhn(preg_replace('/\D/', '', $_POST['numero'] ?? ''))) throw new Exception('Número do cartão inválido.');
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $_POST['validade'] ?? '')) throw new Exception('Validade inválida (use MM/AA).');
            if (!preg_match('/^\d{3,4}$/', $_POST['cvv'] ?? '')) throw new Exception('CVV inválido.');
            if (trim($_POST['titular'] ?? '') === '') throw new Exception('Informe o nome do titular.');
            $parc = (int)($_POST['parcelas'] ?? 1);
            if ($parc < 1 || $parc > $maxParc) throw new Exception('Parcelamento inválido.');
        }
        $pid = (new Pedido)->criar((int)usuario()['id'], $id, 1, $forma);   // sempre 1 unidade
        if ($parc > 1) $_SESSION['parcelas'][$pid] = $parc;
        header('Location: pagamento.php?id=' . $pid); exit;
    } catch (Exception $e) { $erro = $e->getMessage(); }
}
$formaSel = $_POST['forma'] ?? '';
$parcSel  = (int)($_POST['parcelas'] ?? 1);

ob_start(); ?>
<style>
.cp-wrap{display:flex;gap:40px;align-items:flex-start;max-width:1000px;margin:0 auto}
.cp-jogo{flex:0 0 320px}
.cp-capa{position:relative;aspect-ratio:1/1;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#27409c,#101a45)}
.cp-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.cp-capa .ini{position:absolute;inset:0;display:grid;place-items:center;font-size:72px;font-weight:700;color:rgba(255,255,255,.3)}
.cp-jogo .rot{margin:12px 0 0;font-size:12px;font-weight:700;color:var(--mt);text-transform:uppercase}
.cp-jogo h3{margin:2px 0 6px;font-size:22px;font-weight:600;line-height:1.3}
.cp-jogo .preco{margin:0;font-size:24px;color:#4a5063}
.cp-wrap .cartao-form{flex:1;margin:0;max-width:none}
.cartao-form select{display:block;width:100%;margin-top:6px;padding:9px 14px;border:1px solid var(--bd);border-radius:10px;font:inherit;font-size:15px;font-weight:400;color:var(--tx);background:#fff;cursor:pointer}
.cartao-form select:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.cp-pg{margin:0 0 6px;font-size:14px;color:var(--mt)}
@media(max-width:800px){.cp-wrap{flex-direction:column}.cp-jogo{flex-basis:auto;width:100%;max-width:320px}}
</style>
<?php $css = ob_get_clean();
ps_topo('Comprar', $css, 'catalogo');
?>
<main class="pg">
<h2 style="text-align:center">Finalizar compra</h2>
<div class="cp-wrap">
  <aside class="cp-jogo">
    <div class="cp-capa">
      <span class="ini"><?= h(mb_substr($g['titulo'], 0, 1)) ?></span>
      <?php if ($img): ?><img src="<?= h($img) ?>" alt="<?= h($g['titulo']) ?>" onerror="this.remove()"><?php endif; ?>
    </div>
    <?php if (!empty($g['categoria'])): ?><p class="rot"><?= h($g['categoria']) ?></p><?php endif; ?>
    <h3><?= h($g['titulo']) ?></h3>
    <p class="preco"><?= moeda($preco) ?></p>
  </aside>

  <form class="cartao-form" method="POST" id="f">
    <?= csrfCampo() ?><input type="hidden" name="game_id" value="<?= (int)$g['id'] ?>">
    <?php if ($erro): ?><p class="erro"><?= h($erro) ?></p><?php endif; ?>
    <p>Total: <strong><?= moeda($preco) ?></strong></p>
    <fieldset><legend>Forma de pagamento</legend>
      <label class="op"><input type="radio" name="forma" value="pix" required <?= $formaSel === 'pix' ? 'checked' : '' ?>> Pix (QR Code)</label>
      <label class="op"><input type="radio" name="forma" value="cartao" <?= $formaSel === 'cartao' ? 'checked' : '' ?>> Cartão</label>
      <label class="op"><input type="radio" name="forma" value="boleto" <?= $formaSel === 'boleto' ? 'checked' : '' ?>> Boleto</label>
    </fieldset>
    <div id="cartao" hidden>
      <label>Nome do titular <input name="titular" autocomplete="cc-name" value="<?= h($_POST['titular'] ?? '') ?>"></label>
      <label>Número do cartão <input name="numero" inputmode="numeric" maxlength="23" autocomplete="cc-number"></label>
      <label>Validade (MM/AA) <input name="validade" maxlength="5" placeholder="MM/AA" autocomplete="cc-exp"></label>
      <label>CVV <input name="cvv" inputmode="numeric" maxlength="4" autocomplete="cc-csc"></label>
      <label>Parcelamento
        <select name="parcelas">
          <?php for ($n = 1; $n <= $maxParc; $n++): ?>
            <option value="<?= $n ?>"<?= $parcSel === $n ? ' selected' : '' ?>><?= $n === 1 ? 'À vista · ' . moeda($preco) : $n . 'x de ' . moeda($preco / $n) . ' sem juros' ?></option>
          <?php endfor; ?>
        </select>
      </label>
    </div>
    <div class="form-botoes"><button>Finalizar compra</button></div>
  </form>
</div>
</main>
<script>
const cartao = document.getElementById('cartao');
const mostrar = () => { cartao.hidden = !document.querySelector('input[name=forma][value=cartao]:checked'); };
document.querySelectorAll('input[name=forma]').forEach(r => r.addEventListener('change', mostrar));
mostrar();
</script>
<?php ps_rodape();
