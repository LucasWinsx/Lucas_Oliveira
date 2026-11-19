<?php
/* Layout compartilhado das páginas de cadastro/edição do admin (mesmo visual do catálogo).
   Uso: pagina_topo('Título da aba', 'jogos' | 'usuarios'); ... pagina_fim(); */
function pagina_topo($titulo, $ativo = '') {
    $logado = usuario();
    $on = function ($k) use ($ativo) { return $ativo === $k ? ' style="color:var(--ac);font-weight:600"' : ''; };
    $msg = flash(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($titulo) ?> | Loja de jogos</title>
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
main{padding:0 var(--px) 48px}
.pagina h2{font-size:26px;font-weight:600;text-align:center;margin:40px 0 22px}

/* formulários */
.cartao-form{max-width:640px;margin:0 auto;padding:26px 28px;border:1px solid var(--bd);border-radius:10px}
.cartao-form label{display:block;margin:0 0 16px;font-size:14px;font-weight:600;color:var(--mt)}
.cartao-form input,.cartao-form select,.cartao-form textarea{display:block;width:100%;margin-top:6px;padding:9px 14px;border:1px solid var(--bd);border-radius:10px;font:inherit;font-size:15px;font-weight:400;color:var(--tx);background:#fff}
.cartao-form textarea{resize:vertical}
.cartao-form input[type=file]{border:0;padding:6px 0}
.cartao-form input:focus,.cartao-form select:focus,.cartao-form textarea:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.cartao-form p{margin:0 0 16px;font-size:14px;color:var(--mt)}
.cartao-form .erro{margin:0 0 16px;padding:10px 14px;border-radius:10px;background:#fdeceb;color:#d93025;font-size:14px}
.form-botoes{display:flex;justify-content:center;align-items:center;gap:14px;margin-top:8px}
.form-botoes button{background:var(--ac);color:#fff;border:0;border-radius:99px;padding:9px 26px;font:inherit;font-size:15px;font-weight:600;cursor:pointer;transition:background .15s}
.form-botoes button:hover{background:var(--ac2)}
.form-botoes .cancelar{color:var(--mt);font-size:15px}.form-botoes .cancelar:hover{color:var(--ac)}

/* listagens em tabela (categorias, clientes) */
.acoes-topo{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin:0 0 22px}
.btn-add{background:var(--ac);color:#fff;padding:8px 22px;border-radius:99px;font-size:14px;font-weight:600;transition:background .15s}.btn-add:hover{background:var(--ac2)}
.busca-simples{display:flex;justify-content:center;gap:8px;margin:0 0 18px}
.busca-simples input{min-width:0;width:min(420px,100%);padding:8px 16px;border:1px solid var(--bd);border-radius:99px;font:inherit;font-size:14px}
.busca-simples button{background:#eef3fb;color:var(--ac);border:0;border-radius:99px;padding:8px 18px;font:inherit;font-size:14px;font-weight:600;cursor:pointer}
.tabela-wrap{overflow-x:auto;border:1px solid var(--bd);border-radius:10px}
table.tabela{width:100%;border-collapse:collapse;font-size:15px}
.tabela th,.tabela td{padding:11px 14px;text-align:left;border-bottom:1px solid var(--bd);vertical-align:middle}
.tabela th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--mt);background:#f7f9fd}
.tabela tr:last-child td{border-bottom:0}
.tabela td.acoes{white-space:nowrap;text-align:right}
.tabela td.acoes form{display:inline;margin:0}
.btn-mini{display:inline-block;border:0;border-radius:99px;padding:5px 14px;font:inherit;font-size:13px;font-weight:600;cursor:pointer;margin-left:6px}
.btn-mini.editar{background:#eef3fb;color:var(--ac)}.btn-mini.editar:hover{background:#dce8fa}
.btn-mini.excluir{background:#fdeceb;color:#d93025}.btn-mini.excluir:hover{background:#f9d6d3}
.vazio{color:var(--mt);text-align:center}
.tag{font-size:12px;font-weight:700;text-transform:uppercase;padding:2px 10px;border-radius:99px;white-space:nowrap}
.tag.pendente{background:#fff4d6;color:#a86b00}.tag.pago{background:#e8f6ec;color:#1e8a3e}.tag.cancelado{background:#fdeceb;color:#d93025}
.pequeno{color:var(--mt);font-size:13px}

.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape a:hover{color:var(--ac)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  </style>
</head>
<body id="inicio">
<header class="topo">
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <a href="<?= BASE ?>/admin/views/gerenciar_games.php"<?= $on('jogos') ?>>Gerenciar jogos</a>
    <a href="<?= BASE ?>/admin/views/ver_usuarios.php"<?= $on('usuarios') ?>>Gerenciar usuários</a>
    <a href="<?= BASE ?>/admin/views/ver_clientes.php"<?= $on('clientes') ?>>Clientes</a>
    <a href="<?= BASE ?>/admin/views/ver_categorias.php"<?= $on('categorias') ?>>Categorias</a>
    <a href="<?= BASE ?>/admin/views/dashboard.php"<?= $on('dashboard') ?>>Dashboard</a>
    <a href="<?= BASE ?>/admin/views/pedidos.php"<?= $on('pedidos') ?>>Pedidos</a>
  </nav>
  <div class="conta"><span><?= h($logado['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a></div>
</header>
<main>
<?php if ($msg): ?><p style="margin:16px 0 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1"><?= h($msg) ?></p><?php endif; ?>
<div class="pagina">
<?php }

function pagina_fim() { ?>
</div>
</main>
<footer class="rodape">
  <a href="<?= BASE ?>/index.php#inicio"><strong>Voltar ao início</strong></a>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>
<script src="<?= BASE ?>/admin/public/js/validacao.js" defer></script>
</body>
</html>
<?php }
