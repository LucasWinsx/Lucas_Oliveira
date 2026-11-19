<?php
/* Layout compartilhado das páginas da loja (cliente): mesmo visual do catálogo, fundo branco.
   Use: ps_topo('Título', '<style>...</style>', 'carrinho'); ... ps_rodape();
   O 3º parâmetro (opcional) destaca o item do menu: catalogo | carrinho | lista | pedidos */

function ps_topo(string $titulo, string $extraHead = '', string $ativo = ''): void {
    $u = usuario();
    $admin = $u && $u['tipo'] === 'admin';
    $cliente = $u && !$admin;
    $nCarrinho = array_sum($_SESSION['carrinho'] ?? []);
    $on = function ($k) use ($ativo) { return $ativo === $k ? ' style="color:var(--ac);font-weight:600"' : ''; };
    $msg = flash();
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($titulo) ?> | Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--bd:#dfe3ee;--bg2:#f6f8fc;--px:clamp(16px,5vw,90px)}
*{box-sizing:border-box}
[hidden]{display:none!important}
html{scroll-behavior:smooth;scroll-padding-top:80px}
body{margin:0;background:#fff;color:var(--tx);font:16px/1.5 "Segoe UI",system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column}
body>article,body>main{flex:1}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid var(--ac);outline-offset:2px}
.topo{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:22px;padding:14px var(--px);background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid var(--bd);color:var(--tx)}
.topo::before{content:""}
.menu{display:flex;gap:26px;color:var(--mt);flex-wrap:wrap;justify-content:center}.menu a:hover{color:var(--ac)}
.conta{justify-self:end;display:flex;gap:14px;align-items:center;color:var(--mt);flex-wrap:wrap}.conta a:hover{color:var(--ac)}
.conta .destaque{background:var(--ac);color:#fff;padding:7px 16px;border-radius:99px;font-weight:600}
.conta .destaque:hover{background:var(--ac2);color:#fff}
.flash{margin:16px var(--px) 0;padding:10px 14px;border:1px solid #ffb703;border-radius:6px;background:#fff8e1}
.rodape{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:24px var(--px);border-top:1px solid var(--bd);color:var(--mt)}
.rodape a:hover{color:var(--ac)}.rodape strong{color:var(--tx)}

/* base das páginas simples (comprar, pagamento, meus pedidos) */
.pg{padding:36px var(--px) 56px}
.estreita{width:100%;max-width:760px;margin:0 auto}
.pg>h1,.pg>h2{margin:0 0 22px;font-size:30px;font-weight:600}
.btn,.pg button{background:var(--ac);color:#fff;border:0;border-radius:99px;padding:9px 24px;font:inherit;font-size:15px;font-weight:600;cursor:pointer;display:inline-block;transition:background .15s}
.btn:hover,.pg button:hover{background:var(--ac2)}
.tag{font-size:12px;font-weight:700;text-transform:uppercase;padding:2px 10px;border-radius:99px;white-space:nowrap}
.tag.pendente{background:#fff4d6;color:#a86b00}
.tag.pago{background:#e8f6ec;color:#1e8a3e}
.tag.cancelado{background:#fdeceb;color:#d93025}
.erro{margin:0 0 16px;padding:10px 14px;border-radius:10px;background:#fdeceb;color:#d93025}
.ok-msg{margin:0 0 16px;padding:10px 14px;border-radius:10px;background:#e8f6ec;color:#1e8a3e;font-weight:600}
.resumo{margin:0 0 22px;padding:18px 22px;border:1px solid var(--bd);border-radius:10px;background:var(--bg2)}
.resumo p{margin:0 0 6px}.resumo p:last-child{margin:0}
.cartao-form{max-width:640px;margin:0 auto;padding:26px 28px;border:1px solid var(--bd);border-radius:10px}
.cartao-form label{display:block;margin:0 0 16px;font-size:14px;font-weight:600;color:var(--mt)}
.cartao-form input:not([type=radio]){display:block;width:100%;margin-top:6px;padding:9px 14px;border:1px solid var(--bd);border-radius:10px;font:inherit;font-size:15px;font-weight:400;color:var(--tx);background:#fff}
.cartao-form input:focus{border-color:var(--ac);outline:none;box-shadow:0 0 0 3px rgba(0,112,209,.15)}
.cartao-form p{margin:0 0 16px;color:#4a5063}
.cartao-form fieldset{border:1px solid var(--bd);border-radius:10px;margin:0 0 16px;padding:10px 16px}
.cartao-form legend{padding:0 6px;color:var(--mt);font-size:14px}
.cartao-form .op{display:flex;gap:8px;align-items:center;margin:0;padding:3px 0;font-size:16px;font-weight:400;color:var(--tx)}
.cartao-form .op input{width:auto;margin:0;display:inline-block}
.form-botoes{display:flex;justify-content:center;margin-top:8px}
.rolagem{overflow-x:auto}
.rolagem table{width:100%;border-collapse:collapse}
.rolagem th{text-align:left;font-size:13px;text-transform:uppercase;color:var(--mt);padding:10px 12px;border-bottom:2px solid var(--bd)}
.rolagem td{padding:14px 12px;border-bottom:1px solid var(--bd)}
.rolagem td a{color:var(--ac);font-weight:600}.rolagem td a:hover{text-decoration:underline}
textarea{width:100%;padding:10px 14px;border:1px solid var(--bd);border-radius:10px;font:inherit;font-size:14px;margin:0 0 12px}
.qr{margin:14px 0}
.boleto{padding:18px 22px;border:1px dashed #8b93a8;border-radius:10px;margin:0 0 14px}
.boleto p{margin:0 0 6px}.boleto code{display:block;font-size:15px;word-break:break-all}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.conta{justify-self:auto}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
  </style>
  <?= $extraHead ?>
</head>
<body>
<header class="topo">
  <nav class="menu">
    <a href="<?= BASE ?>/index.php#inicio">Início</a>
    <a href="<?= BASE ?>/catalogo.php"<?= $on('catalogo') ?>>Catálogo</a>
    <?php if ($admin): ?>
      <a href="<?= BASE ?>/admin/views/gerenciar_games.php">Gerenciar jogos</a>
      <a href="<?= BASE ?>/admin/views/ver_usuarios.php">Gerenciar usuários</a>
      <a href="<?= BASE ?>/admin/views/ver_clientes.php">Clientes</a>
      <a href="<?= BASE ?>/admin/views/ver_categorias.php">Categorias</a>
      <a href="<?= BASE ?>/admin/views/dashboard.php">Dashboard</a>
      <a href="<?= BASE ?>/admin/views/pedidos.php">Pedidos</a>
    <?php elseif ($cliente): ?>
      <a href="<?= BASE ?>/cliente/carrinho.php"<?= $on('carrinho') ?>>Carrinho<?= $nCarrinho ? ' (' . $nCarrinho . ')' : '' ?></a>
      <a href="<?= BASE ?>/cliente/lista_desejos.php"<?= $on('lista') ?>>Lista de desejos</a>
      <a href="<?= BASE ?>/cliente/meus_jogos.php"<?= $on('meus_jogos') ?>>Meus jogos</a>
      <a href="<?= BASE ?>/cliente/meus_pedidos.php"<?= $on('pedidos') ?>>Meus pedidos</a>
    <?php endif; ?>
  </nav>
  <div class="conta">
    <?php if (!$u): ?>
      <a href="<?= BASE ?>/cliente/carrinho.php"<?= $on('carrinho') ?>>Carrinho<?= $nCarrinho ? ' (' . $nCarrinho . ')' : '' ?></a>
      <a href="<?= BASE ?>/login.php">Entrar</a><a class="destaque" href="<?= BASE ?>/registro.php">Criar conta</a>
    <?php else: ?>
      <span><?= h($u['nome']) ?></span><a href="<?= logoutUrl() ?>">Sair</a>
    <?php endif; ?>
  </div>
</header>
<?php if ($msg): ?><p class="flash"><?= h($msg) ?></p><?php endif; ?>
<?php
}

function ps_rodape(): void {
    ?>
<footer class="rodape">
  <a href="<?= BASE ?>/index.php#inicio"><strong>Voltar ao início</strong></a>
  <span>Pague com cartão, Pix ou boleto</span>
</footer>
<?php require __DIR__ . '/_assistente_widget.php'; ?>
</body>
</html>
<?php
}
