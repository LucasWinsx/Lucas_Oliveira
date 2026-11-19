<?php
require_once '../config/auth.php';
require_once '../models/usuarios.php';
exigirAdmin();

$busca    = trim($_GET['buscar'] ?? '');
$usuarios = (new Usuario)->listar($busca);
$logado   = usuario();
$eu       = (int)$logado['id'];

function realce($t) {
    global $busca;
    $t = h($t);
    return $busca === '' ? $t : preg_replace('/(' . preg_quote(h($busca), '/') . ')/iu', '<mark>$1</mark>', $t) ?? $t;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gerenciar usuários | Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--bd:#dfe3ee;--px:clamp(16px,5vw,90px)}
*{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:80px}
body{margin:0;color:var(--tx);font:16px/1.5 "Segoe UI",system-ui,sans-serif;background:#fff;min-height:100vh}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid var(--ac);outline-offset:2px}
.topo{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:22px;padding:14px var(--px);background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid var(--bd)}
.topo::before{content:""}
.menu{display:flex;gap:26px;color:var(--mt);flex-wrap:wrap;justify-content:center}.menu a:hover{color:var(--ac)}
.conta{justify-self:end;display:flex;gap:14px;align-items:center;color:var(--mt)}.conta a:hover{color:var(--ac)}
.busca{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;align-items:center;margin:22px auto 0;max-width:480px}
.busca input{min-width:0;padding:7px 14px;border:1px solid var(--bd);border-radius:99px;font:inherit;font-size:14px;color:var(--tx);background:#fff}
.campo{position:relative;flex:1 1 220px;display:flex}
.campo input{width:100%;text-align:center;padding-right:34px;padding-left:34px}
.campo::after{content:"";position:absolute;right:13px;top:50%;width:14px;height:14px;margin-top:-7px;border:2px solid var(--bd);border-top-color:var(--ac);border-radius:50%;opacity:0;transition:opacity .15s;animation:gira .7s linear infinite}
.buscando .campo::after{opacity:1}
@keyframes gira{to{transform:rotate(360deg)}}
.grade .card{animation:surge .45s cubic-bezier(.2,.7,.2,1) calc(var(--i,0) * 45ms) backwards}
@keyframes surge{from{opacity:0;transform:translateY(18px) scale(.96)}}
mark{background:#cfe6ff;color:inherit;border-radius:3px;padding:0 2px}
@media(prefers-reduced-motion:reduce){.grade .card,.campo::after{animation:none}html{scroll-behavior:auto}}
.busca input:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.acoes{display:flex;justify-content:center;margin:14px 0 0}
.btn-add{background:var(--ac);color:#fff;padding:8px 22px;border-radius:99px;font-size:14px;font-weight:600;transition:background .15s}
.btn-add:hover{background:var(--ac2)}
#conteudo{transition:opacity .15s}#conteudo.carregando{opacity:.45;transition:opacity .2s}
main{padding:0 var(--px) 48px}

.bloco{margin-top:40px}
.bloco h2{font-size:26px;font-weight:600;margin:0 0 18px}
.vazio{color:var(--mt)}
.grade{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:30px 22px}
.card{display:flex;flex-direction:column;align-items:center;text-align:center;min-width:0;padding:26px 18px 18px;border:1px solid var(--bd);border-radius:10px}
.avatar{width:84px;height:84px;border-radius:50%;display:grid;place-items:center;font-size:34px;font-weight:700;color:#fff;background:linear-gradient(135deg,#27409c,#101a45)}
.avatar.admin{background:linear-gradient(135deg,#0070d1,#0d1b4c)}
.card h3{font-size:17px;font-weight:400;line-height:1.5;margin:14px 0 0;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.card h3 small{color:var(--mt);font-size:13px}
.email{margin:0 0 10px;font-size:14px;color:var(--mt);max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tag{font-size:12px;font-weight:700;text-transform:uppercase;padding:2px 10px;border-radius:99px}
.tag.admin{background:#e6f1fc;color:var(--ac)}
.tag.cliente{background:#e8f6ec;color:#1e8a3e}
.botoes{display:flex;gap:8px;width:100%;margin-top:auto;padding-top:16px}
.botoes form{flex:1;margin:0;display:flex}
.botoes a,.botoes button{flex:1;text-align:center;padding:7px 12px;border:0;border-radius:99px;font:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s}
.botoes .editar{background:#eef3fb;color:var(--ac)}.botoes .editar:hover{background:#dce8fa}
.botoes .excluir{background:#fdeceb;color:#d93025}.botoes .excluir:hover{background:#f9d6d3}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape a:hover{color:var(--ac)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(max-width:700px){.grade{grid-template-columns:repeat(2,1fr);gap:22px 14px}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <a href="<?= BASE ?>/admin/views/gerenciar_games.php">Gerenciar jogos</a>
    <a href="<?= BASE ?>/admin/views/ver_usuarios.php" style="color:var(--ac);font-weight:600">Gerenciar usuários</a>
    <a href="<?= BASE ?>/admin/views/ver_clientes.php">Clientes</a>
    <a href="<?= BASE ?>/admin/views/ver_categorias.php">Categorias</a>
    <a href="<?= BASE ?>/admin/views/dashboard.php">Dashboard</a>
    <a href="<?= BASE ?>/admin/views/pedidos.php">Pedidos</a>
  </nav>
  <div class="conta"><span><?= h($logado['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a></div>
</header>
<main>
<?php if ($m = flash()): ?><p style="margin:16px 0 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1"><?= h($m) ?></p><?php endif; ?>
<form class="busca" id="filtros" method="get" action="ver_usuarios.php" role="search">
  <div class="campo"><input type="search" name="buscar" value="<?= h($busca) ?>" placeholder="Buscar por nome ou e-mail" aria-label="Buscar por nome ou e-mail" autocomplete="off"></div>
</form>
<div class="acoes"><a class="btn-add" href="cadastrar_usuario.php">+ Cadastrar usuário</a></div>
<div id="conteudo">
  <section class="bloco">
    <h2><?= $busca !== '' ? 'Resultados para “' . h($busca) . '”' : 'Usuários' ?></h2>
    <?php if (!$usuarios): ?><p class="vazio">Nenhum usuário encontrado.</p><?php endif; ?>
    <div class="grade">
      <?php foreach ($usuarios as $x): $adm = $x['tipo'] === 'admin'; ?>
      <article class="card">
        <div class="avatar<?= $adm ? ' admin' : '' ?>"><?= h(mb_strtoupper(mb_substr($x['nome'], 0, 1))) ?></div>
        <h3><?= realce($x['nome']) ?><?= (int)$x['id'] === $eu ? ' <small>(você)</small>' : '' ?></h3>
        <p class="email"><?= realce($x['email']) ?></p>
        <span class="tag <?= $adm ? 'admin' : 'cliente' ?>"><?= $adm ? 'administrador' : 'cliente' ?></span>
        <div class="botoes">
          <a class="editar" href="editar_usuario.php?id=<?= (int)$x['id'] ?>">Editar</a>
          <?php if ((int)$x['id'] !== $eu): ?>
            <form method="POST" action="../controllers/deletar_usuario.php" onsubmit="return confirm('Excluir o usuário &quot;<?= h(addslashes($x['nome'])) ?>&quot;?')">
              <?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="excluir">Excluir</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
</main>

<footer class="rodape">
  <a href="<?= BASE ?>/index.php#inicio"><strong>Voltar ao início</strong></a>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>

<script>
// busca ao vivo: atualiza a lista enquanto digita, sem recarregar a página
(function () {
  const form = document.getElementById('filtros'), alvo = document.getElementById('conteudo');
  let espera, ctrl;
  const animar = () => alvo.querySelectorAll('.grade .card').forEach((c, i) => c.style.setProperty('--i', Math.min(i, 14)));
  animar();
  const atualizar = async () => {
    const qs = new URLSearchParams();
    for (const [k, v] of new FormData(form)) if (v.trim() !== '') qs.set(k, v.trim());
    const url = form.getAttribute('action') + (qs.toString() ? '?' + qs : '');
    history.replaceState(null, '', url);
    if (ctrl) ctrl.abort();
    const meu = ctrl = new AbortController();
    alvo.classList.add('carregando'); form.classList.add('buscando');
    try {
      const r = await fetch(url, { signal: meu.signal });
      const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
      const novo = doc.getElementById('conteudo');
      if (novo) { alvo.innerHTML = novo.innerHTML; animar(); }
    } catch (e) { if (e.name !== 'AbortError') console.error(e); }
    if (ctrl === meu) { alvo.classList.remove('carregando'); form.classList.remove('buscando'); }
  };
  form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(espera); atualizar(); });
  form.elements.buscar.addEventListener('input', () => { clearTimeout(espera); espera = setTimeout(atualizar, 250); });
})();
</script>
</body>
</html>
