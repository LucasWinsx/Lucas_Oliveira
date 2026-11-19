<?php /* Formulário compartilhado por cadastrar_game.php e editar_game.php */ ?>
<style>
/* caixas de plataforma (sobrepõe o estilo genérico de inputs do formulário) */
.plats-rot{display:block;font-size:14px;font-weight:600;color:#6b7287}
.plats{display:flex;flex-wrap:wrap;gap:8px 20px;margin:8px 0 10px}
.cartao-form .plats-box .plats label{display:inline-flex;align-items:center;gap:7px;margin:0;font-size:14px;font-weight:400;color:#1f2330;cursor:pointer}
.cartao-form .plats-box .plats input[type=checkbox],
.form-horizontal .fg .plats input[type=checkbox]{display:inline-block;width:18px;height:18px;margin:0;padding:0;accent-color:#0070d1;cursor:pointer}
.cartao-form .plats-box:not(.fg){margin:0 0 16px}
</style>
<form class="cartao-form larga" method="POST" enctype="multipart/form-data" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $m) echo h($m) . '<br>'; ?></div><?php endif; ?>
  <label>Título <input name="titulo" required maxlength="200" data-regra="nome" value="<?= h($v['titulo']) ?>"></label>
  <label>Descrição <textarea name="descricao" rows="4"><?= h($v['descricao']) ?></textarea></label>
  <label>Preço (R$) <input name="preco" required data-regra="preco" inputmode="decimal" placeholder="229,90" value="<?= h($v['preco']) ?>"></label>
  <label>Estoque <input type="number" name="estoque" min="0" step="1" required data-regra="inteiro" value="<?= h($v['estoque']) ?>"></label>
  <label>Categoria
    <input name="categoria" list="cats" maxlength="100" value="<?= h($v['categoria']) ?>">
    <datalist id="cats"><?php foreach ($categorias as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?></datalist>
  </label>
  <label><strong>Trailer do jogo</strong> (cole o link do YouTube ou de um vídeo .mp4/.webm — opcional)
    <input name="trailer" id="campo-trailer" maxlength="255" data-regra="trailer" inputmode="url" autocomplete="off" placeholder="https://www.youtube.com/watch?v=..." value="<?= h($v['trailer']) ?>">
    <span id="prev-trailer" hidden></span>
  </label>
  <?php
    $clasOpcoes = ['L' => 'L (livre)', '10' => '10 anos', '12' => '12 anos', '14' => '14 anos', '16' => '16 anos', '18' => '18 anos'];
    $clasAtual  = (string)($v['classificacao'] ?? '');
    if ($clasAtual !== '' && !isset($clasOpcoes[$clasAtual])) $clasOpcoes[$clasAtual] = $clasAtual;   // mantém um valor antigo do banco
  ?>
  <label>Desenvolvedora
    <input name="desenvolvedora" maxlength="120" placeholder="Ex.: Sony Interactive Entertainment" value="<?= h($v['desenvolvedora'] ?? '') ?>">
  </label>
  <?php
    $platPadrao = ['PS4', 'PS5', 'Xbox Series S', 'Xbox Series X', 'PC'];
    $platNorm = function ($x) {            // mesmos nomes que a página do jogo reconhece
        $t = mb_strtolower(trim($x));
        if (preg_match('/ps\s*5|playstation\s*5/u', $t)) return 'PS5';
        if (preg_match('/ps\s*4|playstation\s*4/u', $t)) return 'PS4';
        if (preg_match('/series\s*s\b/u', $t)) return 'Xbox Series S';
        if (preg_match('/series\s*x\b/u', $t)) return 'Xbox Series X';
        if (preg_match('/^(pc|windows|steam|computador)/u', $t)) return 'PC';
        return trim($x);
    };
    $platRaw = $v['plataforma'] ?? '';
    $platMarcadas = []; $platOutras = [];
    if (is_array($platRaw)) {              // formulário reexibido após um erro
        foreach ($platRaw as $x) { $x = $platNorm((string)$x); if (in_array($x, $platPadrao, true)) $platMarcadas[] = $x; }
        $platOutraTxt = (string)($v['plataforma_outra'] ?? '');
    } else {                               // valor salvo no banco: "PS5, PC, Nintendo Switch"
        foreach (preg_split('/[,\/|;]+/', (string)$platRaw) as $x) {
            $x = trim($x);
            if ($x === '') continue;
            $n = $platNorm($x);
            if (in_array($n, $platPadrao, true)) $platMarcadas[] = $n; else $platOutras[] = $x;
        }
        $platOutraTxt = implode(', ', $platOutras);
    }
  ?>
  <div class="plats-box" role="group" aria-label="Plataformas">
    <span class="plats-rot">Plataformas</span>
    <div class="plats">
      <?php foreach ($platPadrao as $pn): ?>
        <label><input type="checkbox" name="plataforma[]" value="<?= h($pn) ?>"<?= in_array($pn, $platMarcadas, true) ? ' checked' : '' ?>> <?= h($pn) ?></label>
      <?php endforeach; ?>
    </div>
    <input name="plataforma_outra" maxlength="60" placeholder="Outra plataforma (opcional). Ex.: Nintendo Switch" value="<?= h($platOutraTxt) ?>">
  </div>
  <label>Lançamento
    <input type="datetime-local" name="lancamento" value="<?= h($v['lancamento'] ?? '') ?>">
  </label>
  <label>Classificação
    <select name="classificacao">
      <option value="">Não informada</option>
      <?php foreach ($clasOpcoes as $cv => $ct): ?><option value="<?= h($cv) ?>"<?= $clasAtual === (string)$cv ? ' selected' : '' ?>><?= h($ct) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>Jogadores
    <input name="jogadores" list="lista-jogadores" maxlength="60" placeholder="Ex.: 1 jogador" value="<?= h($v['jogadores'] ?? '') ?>">
    <datalist id="lista-jogadores"><option value="1 jogador"><option value="1-2 jogadores"><option value="2 jogadores"><option value="1-4 jogadores"><option value="Online multijogador"></datalist>
  </label>
  <?php if (!empty($imagemAtual)): ?>
    <p>Imagem atual:<br><img src="../public/imagens/<?= rawurlencode($imagemAtual) ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:8px" onerror="this.remove()"></p>
  <?php endif; ?>
  <label>Imagem (JPG, PNG, WEBP ou GIF, até 3 MB)<?= !empty($imagemAtual) ? ' — deixe vazio para manter a atual' : '' ?>
    <input type="file" name="imagem" accept="image/*">
  </label>
  <div class="form-botoes"><button><?= h($botao) ?></button><a class="cancelar" href="gerenciar_games.php">Cancelar</a></div>
</form>
<script>
/* Pré-visualização do trailer ao colar o link (YouTube ou arquivo de vídeo) */
(function () {
  var inp = document.getElementById('campo-trailer'), box = document.getElementById('prev-trailer');
  if (!inp || !box) return;
  var atual = '';
  function atualizar() {
    var v = inp.value.trim();
    var m = v.match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{11})/i);
    var chave = m ? 'yt:' + m[1] : (/^https?:\/\/\S+\.(mp4|webm|ogv)(\?\S*)?$/i.test(v) ? 'v:' + v : '');
    if (chave === atual) return;
    atual = chave;
    box.textContent = '';
    if (!chave) { box.hidden = true; return; }
    var el;
    if (m) {
      el = document.createElement('iframe');
      el.src = 'https://www.youtube-nocookie.com/embed/' + m[1];
      el.title = 'Pré-visualização do trailer';
      el.allow = 'encrypted-media; fullscreen';
      el.allowFullscreen = true;
      el.loading = 'lazy';
    } else {
      el = document.createElement('video');
      el.src = v; el.controls = true; el.preload = 'metadata'; el.muted = true;
    }
    el.style.cssText = 'display:block;width:100%;max-width:480px;aspect-ratio:16/9;border:0;border-radius:10px;margin-top:10px;background:#000';
    box.appendChild(el);
    box.hidden = false;
  }
  inp.addEventListener('input', atualizar);
  inp.addEventListener('change', atualizar);
  atualizar();
})();
</script>
