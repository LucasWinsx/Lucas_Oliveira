<?php
/* Botão flutuante do assistente de recomendação (incluído nas páginas públicas).
   Precisa de admin/config/auth.php já carregado (BASE, usuario(), csrfToken(), h()). */
$iawLogado = (bool)usuario();
?>
<style>
.iaw-btn{position:fixed;right:20px;bottom:20px;z-index:9998;display:flex;align-items:center;gap:8px;border:0;border-radius:999px;padding:12px 18px;background:#0070d1;color:#fff;font:600 15px/1 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.35);transition:background .15s,transform .15s}
.iaw-btn:hover{background:#0a84ee;transform:translateY(-1px)}
.iaw-btn svg{width:20px;height:20px}
.iaw-painel{position:fixed;right:20px;bottom:78px;z-index:9999;width:370px;max-width:calc(100vw - 24px);height:520px;max-height:calc(100vh - 100px);display:none;flex-direction:column;border-radius:16px;overflow:hidden;background:#fff;color:#1a1d24;border:1px solid rgba(0,0,0,.12);box-shadow:0 18px 48px rgba(0,0,0,.4);font:14px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;text-shadow:none}
.iaw-painel.aberto{display:flex}
.iaw-topo{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 14px;background:#0d1b4c;color:#fff}
.iaw-topo strong{font-size:15px}
.iaw-topo small{display:block;font-size:12px;opacity:.8;font-weight:400}
.iaw-topo button{border:0;background:rgba(255,255,255,.14);color:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12px;cursor:pointer}
.iaw-topo button:hover{background:rgba(255,255,255,.26)}
.iaw-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#f4f6fb}
.iaw-m{max-width:88%;padding:9px 12px;border-radius:14px;white-space:pre-wrap;word-wrap:break-word}
.iaw-m.bot{align-self:flex-start;background:#fff;border:1px solid #dfe5f1;border-bottom-left-radius:4px}
.iaw-m.eu{align-self:flex-end;background:#0070d1;color:#fff;border-bottom-right-radius:4px}
.iaw-m.erro{align-self:flex-start;background:#fdeceb;color:#a1241b;border:1px solid #f4c7c3}
.iaw-m.erro small{display:block;margin-top:6px;font-size:11px;opacity:.85;white-space:pre-wrap}
.iaw-cards{align-self:flex-start;display:flex;flex-direction:column;gap:8px;width:92%}
.iaw-card{display:flex;align-items:center;gap:10px;padding:8px;border-radius:12px;background:#fff;border:1px solid #dfe5f1;color:inherit;text-decoration:none}
.iaw-card:hover{border-color:#0070d1}
.iaw-card img{width:48px;height:48px;border-radius:8px;object-fit:cover;background:#dfe5f1;flex:0 0 48px}
.iaw-card b{display:block;font-size:14px;line-height:1.25}
.iaw-card span{font-size:13px;color:#0070d1;font-weight:600}
.iaw-sug{display:flex;flex-wrap:wrap;gap:6px}
.iaw-sug button{border:1px solid #c9d4ea;background:#fff;color:#0d1b4c;border-radius:999px;padding:6px 11px;font:inherit;font-size:12.5px;cursor:pointer}
.iaw-sug button:hover{background:#e8f0fd}
.iaw-form{display:flex;gap:8px;padding:10px;background:#fff;border-top:1px solid #dfe5f1}
.iaw-form input{flex:1;min-width:0;padding:10px 12px;border:1px solid #c9d4ea;border-radius:999px;font:inherit;color:#1a1d24;background:#fff}
.iaw-form button{border:0;border-radius:999px;padding:0 16px;background:#0070d1;color:#fff;font:inherit;font-weight:600;cursor:pointer}
.iaw-form button:disabled{opacity:.55;cursor:default}
.iaw-login{padding:22px;text-align:center;background:#f4f6fb;flex:1;display:flex;flex-direction:column;justify-content:center;gap:12px}
.iaw-login a{display:inline-block;align-self:center;background:#0070d1;color:#fff;padding:9px 22px;border-radius:999px;text-decoration:none;font-weight:600}
.iaw-pont{display:inline-flex;gap:4px}
.iaw-pont i{width:6px;height:6px;border-radius:50%;background:#8a96b2;animation:iawp 1s infinite}
.iaw-pont i:nth-child(2){animation-delay:.15s}.iaw-pont i:nth-child(3){animation-delay:.3s}
@keyframes iawp{0%,80%,100%{opacity:.25}40%{opacity:1}}
@media(prefers-reduced-motion:reduce){.iaw-pont i{animation:none}.iaw-btn{transition:none}}
@media(max-width:480px){.iaw-painel{right:12px;bottom:72px}.iaw-btn{right:12px;bottom:12px}}
</style>

<button type="button" class="iaw-btn" id="iaw-abrir" aria-expanded="false" aria-controls="iaw-painel">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>
  Assistente
</button>

<section class="iaw-painel" id="iaw-painel" role="dialog" aria-label="Assistente de recomendação de jogos">
  <div class="iaw-topo">
    <div><strong>Assistente de jogos</strong><small>Recomendações feitas com IA</small></div>
    <?php if ($iawLogado): ?><button type="button" id="iaw-limpar" title="Começar uma nova conversa">Nova conversa</button><?php endif; ?>
  </div>
  <?php if (!$iawLogado): ?>
    <div class="iaw-login">
      <p>Entre na sua conta para pedir recomendações de jogos.</p>
      <a href="<?= h(BASE) ?>/login.php">Entrar</a>
    </div>
  <?php else: ?>
    <div class="iaw-msgs" id="iaw-msgs" aria-live="polite"></div>
    <form class="iaw-form" id="iaw-form" autocomplete="off">
      <input type="text" id="iaw-texto" maxlength="300" placeholder="Ex.: jogo de aventura até R$ 150" aria-label="Sua pergunta">
      <button type="submit" id="iaw-enviar">Enviar</button>
    </form>
  <?php endif; ?>
</section>

<?php if ($iawLogado): ?>
<script>
(function () {
  var URL_API = <?= json_encode(BASE . '/cliente/assistente_api.php') ?>;
  var CSRF = <?= json_encode(csrfToken()) ?>;
  var abrir = document.getElementById('iaw-abrir'), painel = document.getElementById('iaw-painel');
  var msgs = document.getElementById('iaw-msgs'), form = document.getElementById('iaw-form');
  var texto = document.getElementById('iaw-texto'), enviar = document.getElementById('iaw-enviar');
  var limpar = document.getElementById('iaw-limpar');
  var ocupado = false, iniciou = false;

  function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt !== undefined) e.textContent = txt; return e; }
  function rolar() { msgs.scrollTop = msgs.scrollHeight; }
  function msg(cls, txt) { var m = el('div', 'iaw-m ' + cls, txt); msgs.appendChild(m); rolar(); return m; }

  function sugestoes() {
    var box = el('div', 'iaw-sug');
    ['Um jogo de terror', 'Aventura até R$ 150', 'Algo difícil, estilo Souls', 'O que tem em promoção?'].forEach(function (s) {
      var b = el('button', '', s); b.type = 'button';
      b.addEventListener('click', function () { box.remove(); perguntar(s); });
      box.appendChild(b);
    });
    msgs.appendChild(box);
  }
  function inicio() {
    msgs.textContent = '';
    msg('bot', 'Olá! Me conte o que você gosta de jogar e quanto quer gastar que eu indico jogos da nossa loja.');
    sugestoes();
  }

  function cartoes(jogos) {
    if (!jogos || !jogos.length) return;
    var box = el('div', 'iaw-cards');
    jogos.forEach(function (g) {
      var a = el('a', 'iaw-card'); a.href = g.link;
      if (g.imagem) { var im = el('img'); im.src = g.imagem; im.alt = ''; im.onerror = function () { im.style.visibility = 'hidden'; }; a.appendChild(im); }
      var d = el('div'); d.appendChild(el('b', '', g.titulo)); d.appendChild(el('span', '', g.preco + ' · Ver jogo')); a.appendChild(d);
      box.appendChild(a);
    });
    msgs.appendChild(box);
  }

  function perguntar(t) {
    t = (t || '').trim();
    if (!t || ocupado) return;
    ocupado = true; enviar.disabled = true; texto.value = '';
    var sug = msgs.querySelector('.iaw-sug'); if (sug) sug.remove();
    msg('eu', t);
    var pensando = el('div', 'iaw-m bot'); var p = el('span', 'iaw-pont'); p.appendChild(el('i')); p.appendChild(el('i')); p.appendChild(el('i')); pensando.appendChild(p);
    msgs.appendChild(pensando); rolar();

    var fd = new FormData(); fd.append('csrf', CSRF); fd.append('pergunta', t);
    fetch(URL_API, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return { erro: 'Resposta inesperada do servidor.' }; }); })
      .then(function (d) {
        pensando.remove();
        if (d.erro) {
          var m = msg('erro', d.erro);
          if (d.detalhe) m.appendChild(el('small', '', 'Detalhe (visível só para admin): ' + d.detalhe));
        } else { msg('bot', d.resposta); cartoes(d.jogos); }
      })
      .catch(function () { pensando.remove(); msg('erro', 'Sem conexão com o servidor. Tente de novo.'); })
      .then(function () { ocupado = false; enviar.disabled = false; texto.focus(); rolar(); });
  }

  function alternar(abrirPainel) {
    var aberto = abrirPainel !== undefined ? abrirPainel : !painel.classList.contains('aberto');
    painel.classList.toggle('aberto', aberto);
    abrir.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    if (aberto) { if (!iniciou) { iniciou = true; inicio(); } texto.focus(); }
  }
  abrir.addEventListener('click', function () { alternar(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && painel.classList.contains('aberto')) { alternar(false); abrir.focus(); } });
  form.addEventListener('submit', function (e) { e.preventDefault(); perguntar(texto.value); });
  limpar.addEventListener('click', function () {
    var fd = new FormData(); fd.append('csrf', CSRF); fd.append('acao', 'limpar');
    fetch(URL_API, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
    inicio();
  });
})();
</script>
<?php else: ?>
<script>
(function () {
  var abrir = document.getElementById('iaw-abrir'), painel = document.getElementById('iaw-painel');
  abrir.addEventListener('click', function () {
    var a = !painel.classList.contains('aberto');
    painel.classList.toggle('aberto', a); abrir.setAttribute('aria-expanded', a ? 'true' : 'false');
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { painel.classList.remove('aberto'); abrir.setAttribute('aria-expanded', 'false'); } });
})();
</script>
<?php endif; ?>
