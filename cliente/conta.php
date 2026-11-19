<?php
require_once __DIR__ . '/../admin/config/auth.php';
$u = usuario();
if (!$u) { header('Location: ' . BASE . '/login.php'); exit; }

/* ===== AJUSTE AQUI se os nomes no seu banco forem diferentes ===== */
const TABELA = 'usuarios';   // tabela dos usuários
const COL_ID = 'id';         // chave primária
/* ================================================================= */

$UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
$uid = (int)($u['id'] ?? 0);
$erros = [];

// as colunas de endereço (usuario, rua, numero, cidade, estado, pais, cep, idade) já são criadas pelo sql/script.sql
try {
    $pdo->query('SELECT 1 FROM `' . TABELA . '` LIMIT 1');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erro ao acessar a tabela de usuários. Importe o arquivo sql/script.sql no phpMyAdmin.');
}

if (empty($_SESSION['csrf_conta'])) $_SESSION['csrf_conta'] = bin2hex(random_bytes(16));

$st = $pdo->prepare('SELECT * FROM `' . TABELA . '` WHERE `' . COL_ID . '` = ?');
$st->execute([$uid]);
$d = $st->fetch(PDO::FETCH_ASSOC);
if (!$d) { http_response_code(404); exit('Usuário não encontrado.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_conta'], $_POST['csrf'] ?? '')) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente de novo.';
    }
    $usuario = trim($_POST['usuario'] ?? '');
    $nome    = trim($_POST['nome'] ?? '');
    $rua     = trim($_POST['rua'] ?? '');
    $numero  = trim($_POST['numero'] ?? '');
    $cidade  = trim($_POST['cidade'] ?? '');
    $estado  = strtoupper(trim($_POST['estado'] ?? ''));
    $pais    = trim($_POST['pais'] ?? '') ?: 'Brasil';
    $cep     = preg_replace('/\D/', '', $_POST['cep'] ?? '');
    $idadeIn = trim($_POST['idade'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $usuario)) $erros[] = 'O ID de usuário deve ter de 3 a 40 caracteres (letras, números, ponto, hífen ou _).';
    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100)    $erros[] = 'Informe o nome completo (3 a 100 caracteres).';
    if (mb_strlen($rua) > 150)                             $erros[] = 'Nome da rua muito longo.';
    if ($numero !== '' && !preg_match('/^[A-Za-z0-9\/ .-]{1,10}$/', $numero)) $erros[] = 'Número inválido (use até 10 caracteres, ex.: 123, 45A ou s/n).';
    if (mb_strlen($cidade) > 100)                          $erros[] = 'Cidade muito longa.';
    if ($estado !== '' && !in_array($estado, $UFS, true))  $erros[] = 'Estado inválido.';
    if (mb_strlen($pais) > 60)                             $erros[] = 'País muito longo.';
    if ($cep !== '' && strlen($cep) !== 8)                 $erros[] = 'O CEP deve ter 8 dígitos.';
    $idade = null;
    if ($idadeIn !== '') {
        if (!ctype_digit($idadeIn) || (int)$idadeIn < 1 || (int)$idadeIn > 120) $erros[] = 'Idade deve ser entre 1 e 120.';
        else $idade = (int)$idadeIn;
    }
    if (!$erros) {
        $q = $pdo->prepare('SELECT 1 FROM `' . TABELA . '` WHERE usuario = ? AND `' . COL_ID . '` <> ?');
        $q->execute([$usuario, $uid]);
        if ($q->fetch()) $erros[] = 'Esse ID de usuário já está em uso.';
    }
    if (!$erros) {
        $up = $pdo->prepare('UPDATE `' . TABELA . '` SET usuario=?, nome=?, rua=?, numero=?, cidade=?, estado=?, pais=?, cep=?, idade=? WHERE `' . COL_ID . '` = ?');
        $up->execute([$usuario, $nome, $rua ?: null, $numero ?: null, $cidade ?: null, $estado ?: null, $pais, $cep ?: null, $idade, $uid]);
        header('Location: ' . BASE . '/cliente/conta.php?ok=1');
        exit;
    }
    // mantém o que o usuário digitou para ele corrigir
    $d = array_merge($d, ['usuario' => $usuario, 'nome' => $nome, 'rua' => $rua, 'numero' => $numero, 'cidade' => $cidade, 'estado' => $estado, 'pais' => $pais, 'cep' => $cep, 'idade' => $idadeIn]);
}

$cepFmt = strlen((string)($d['cep'] ?? '')) === 8 ? substr($d['cep'], 0, 5) . '-' . substr($d['cep'], 5) : (string)($d['cep'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Minha conta · Loja de jogos</title>
  <style>
:root{--tx:#1f2330;--mt:#6b7287;--ac:#0070d1;--ac2:#005bb0;--navy:#0d1b4c;--bd:#dfe3ee;--px:clamp(16px,5vw,90px)}
*{box-sizing:border-box}
body{margin:0;color:var(--tx);font:16px/1.5 "Segoe UI",system-ui,sans-serif;background:#fff}
a{color:inherit;text-decoration:none}
:focus-visible{outline:2px solid var(--ac);outline-offset:2px}
.topo{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;padding:14px var(--px);border-bottom:1px solid var(--bd)}
.topo::before{content:""}
.topo nav{display:flex;gap:22px;flex-wrap:wrap;justify-content:center;color:var(--mt)}.topo a:hover{color:var(--ac)}
.topo .sair{justify-self:end}
.topo .on{color:var(--ac);font-weight:600}
main{max-width:720px;margin:0 auto;padding:36px 16px 56px}
h1{font-size:28px;font-weight:600;margin:0 0 6px}
.sub{color:var(--mt);margin:0 0 24px}
form{display:grid;grid-template-columns:repeat(6,1fr);gap:16px}
.f{display:flex;flex-direction:column;gap:6px;grid-column:span 6;position:relative}
.f.m{grid-column:span 3}.f.t{grid-column:span 2}.f.q{grid-column:span 4}
label{font-size:14px;font-weight:600}
input,select{font:inherit;padding:10px 12px;border:1px solid var(--bd);border-radius:8px;background:#fff;color:var(--tx);width:100%}
input:focus,select:focus{border-color:var(--ac)}
.dica{font-size:13px;color:var(--mt);min-height:19px}
.msg{grid-column:span 6;padding:10px 14px;border-radius:8px}
.msg.ok{background:#e8f6ec;border:1px solid #9ad3a8}
.msg.erro{background:#fdecea;border:1px solid #f2a9a3}
.msg.erro ul{margin:0;padding-left:18px}
.btn{grid-column:span 6;justify-self:start;border:0;cursor:pointer;background:var(--ac);color:#fff;padding:11px 28px;border-radius:99px;font:inherit;font-weight:700}
.btn:hover{background:var(--ac2)}
#sug{position:absolute;left:0;right:0;top:100%;margin:4px 0 0;padding:4px;list-style:none;max-height:240px;overflow:auto;background:#fff;border:1px solid var(--bd);border-radius:8px;box-shadow:0 14px 30px -12px rgba(13,27,76,.35);z-index:10}
#sug li{padding:7px 10px;border-radius:6px;cursor:pointer}
#sug li:hover,#sug li.on{background:#eaf3fc;color:var(--ac2)}
@media(max-width:900px){.topo{display:flex;flex-direction:column;gap:10px}.topo::before{display:none}.topo .sair{justify-self:auto}}
@media(max-width:600px){.f.m,.f.t,.f.q{grid-column:span 6}}
  </style>
</head>
<body>
<header class="topo">
  <nav>
    <a href="<?= BASE ?>/index.php">Início</a>
    <a href="<?= BASE ?>/catalogo.php">Catálogo</a>
    <a href="<?= BASE ?>/cliente/carrinho.php">Carrinho</a>
    <a href="<?= BASE ?>/cliente/lista_desejos.php">Lista de desejos</a>
    <a href="<?= BASE ?>/cliente/meus_jogos.php">Meus jogos</a>
    <a href="<?= BASE ?>/cliente/meus_pedidos.php">Meus pedidos</a>
    <a class="on" href="<?= BASE ?>/cliente/conta.php">Conta</a>
  </nav>
  <a class="sair" href="<?= logoutUrl() ?>" style="color:var(--mt)">Sair</a>
</header>

<main>
  <h1>Minha conta</h1>
  <p class="sub">Edite suas informações pessoais.</p>

  <form method="post" autocomplete="off" novalidate>
    <?php if (isset($_GET['ok']) && !$erros): ?><div class="msg ok">Dados atualizados com sucesso.</div><?php endif; ?>
    <?php if ($erros): ?><div class="msg erro"><ul><?php foreach ($erros as $e) echo '<li>' . h($e) . '</li>'; ?></ul></div><?php endif; ?>

    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_conta']) ?>">

    <div class="f m"><label for="usuario">ID de usuário</label>
      <input id="usuario" name="usuario" maxlength="40" required value="<?= h($d['usuario'] ?? '') ?>"></div>
    <div class="f m"><label for="nome">Nome completo</label>
      <input id="nome" name="nome" maxlength="100" required value="<?= h($d['nome'] ?? '') ?>"></div>

    <div class="f m"><label for="cep">CEP</label>
      <input id="cep" name="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" value="<?= h($cepFmt) ?>">
      <span class="dica" id="cepmsg">Digite o CEP para preencher rua, cidade, estado e país.</span></div>
    <div class="f m"><label for="idade">Idade</label>
      <input id="idade" name="idade" type="number" min="1" max="120" value="<?= h($d['idade'] ?? '') ?>"></div>

    <div class="f q"><label for="rua">Rua</label>
      <input id="rua" name="rua" maxlength="150" value="<?= h($d['rua'] ?? '') ?>"></div>
    <div class="f t"><label for="numero">Número</label>
      <input id="numero" name="numero" maxlength="10" placeholder="123 ou s/n" value="<?= h($d['numero'] ?? '') ?>"></div>

    <div class="f m"><label for="pais">País</label>
      <input id="pais" name="pais" maxlength="60" value="<?= h($d['pais'] ?? 'Brasil') ?>"></div>
    <div class="f m"><label for="estado">Estado</label>
      <select id="estado" name="estado">
        <option value="">Selecione</option>
        <?php foreach ($UFS as $uf): ?><option<?= ($d['estado'] ?? '') === $uf ? ' selected' : '' ?>><?= $uf ?></option><?php endforeach; ?>
      </select></div>

    <div class="f"><label for="cidade">Cidade</label>
      <input id="cidade" name="cidade" maxlength="100" role="combobox" aria-expanded="false" aria-controls="sug" value="<?= h($d['cidade'] ?? '') ?>">
      <ul id="sug" role="listbox" hidden></ul>
      <span class="dica">Comece a digitar e escolha uma cidade da lista.</span></div>

    <button class="btn" type="submit">Salvar alterações</button>
  </form>
</main>

<script>
const $ = id => document.getElementById(id);
const cep = $('cep'), rua = $('rua'), cidade = $('cidade'), estado = $('estado'), pais = $('pais'), cepmsg = $('cepmsg'), lista = $('sug');
const norm = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const noBrasil = () => !pais.value.trim() || norm(pais.value.trim()) === 'brasil';

/* ---------- CEP: máscara + busca automática (ViaCEP) ---------- */
let reqCep = 0;
cep.addEventListener('input', () => {
  const d = cep.value.replace(/\D/g, '').slice(0, 8);
  cep.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
  if (d.length === 8) buscaCep(d); else { reqCep++; cepmsg.textContent = 'Digite o CEP para preencher rua, cidade, estado e país.'; }
});
async function buscaCep(d) {
  const id = ++reqCep;
  cepmsg.textContent = 'Buscando CEP…';
  try {
    const j = await (await fetch('https://viacep.com.br/ws/' + d + '/json/')).json();
    if (id !== reqCep) return;
    if (j.erro) { cepmsg.textContent = 'CEP não encontrado. Preencha manualmente.'; return; }
    rua.value = j.logradouro || '';
    cidade.value = j.localidade || '';
    estado.value = j.uf || '';
    pais.value = 'Brasil';
    lista.hidden = true;
    cepmsg.textContent = 'Dados preenchidos automaticamente.';
    if (rua.value) $('numero').focus();
  } catch (e) {
    if (id === reqCep) cepmsg.textContent = 'Não foi possível consultar o CEP agora. Preencha manualmente.';
  }
}

/* ---------- Cidade: autocomplete com todos os municípios (IBGE) ---------- */
let cidades = null;
async function carregar() {
  if (cidades) return cidades;
  try {
    const c = JSON.parse(localStorage.getItem('cidadesBR') || 'null');
    if (c && c.length > 5000) return (cidades = c);
  } catch (e) {}
  const j = await (await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome')).json();
  cidades = j.map(m => {
    const uf = m.microrregiao?.mesorregiao?.UF?.sigla || m['regiao-imediata']?.['regiao-intermediaria']?.UF?.sigla || '';
    return [m.nome, uf, norm(m.nome)];
  });
  try { localStorage.setItem('cidadesBR', JSON.stringify(cidades)); } catch (e) {}
  return cidades;
}

let itens = [], sel = -1;
function mostrar() {
  lista.innerHTML = '';
  itens.forEach((c, i) => {
    const li = document.createElement('li');
    li.setAttribute('role', 'option');
    li.textContent = c[0] + (c[1] ? ' — ' + c[1] : '');
    if (i === sel) li.className = 'on';
    li.addEventListener('mousedown', e => { e.preventDefault(); escolher(c); });
    lista.appendChild(li);
  });
  lista.hidden = !itens.length;
  cidade.setAttribute('aria-expanded', String(!!itens.length));
  const ativo = lista.querySelector('.on'); if (ativo) ativo.scrollIntoView({ block: 'nearest' });
}
function escolher(c) {
  cidade.value = c[0];
  if (c[1]) estado.value = c[1];
  if (!pais.value.trim()) pais.value = 'Brasil';
  itens = []; sel = -1; mostrar();
}

cidade.addEventListener('focus', () => { if (noBrasil()) carregar().catch(() => {}); });
cidade.addEventListener('input', async () => {
  if (!noBrasil()) { itens = []; mostrar(); return; }
  const q = norm(cidade.value.trim());
  if (!q) { itens = []; mostrar(); return; }
  try {
    const todas = await carregar();
    if (q !== norm(cidade.value.trim())) return;   // o usuário já digitou mais
    const ini = [], meio = [];
    for (const c of todas) {
      const p = c[2].indexOf(q);
      if (p === 0) ini.push(c); else if (p > 0) meio.push(c);
      if (ini.length >= 40) break;
    }
    itens = ini.concat(meio).slice(0, 40); sel = -1; mostrar();
  } catch (e) { /* sem internet/IBGE fora: segue digitação manual */ }
});
cidade.addEventListener('keydown', e => {
  if (lista.hidden) return;
  if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, itens.length - 1); mostrar(); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); mostrar(); }
  else if (e.key === 'Enter' && sel >= 0) { e.preventDefault(); escolher(itens[sel]); }
  else if (e.key === 'Escape') { itens = []; mostrar(); }
});
cidade.addEventListener('blur', () => { lista.hidden = true; });
</script>
</body>
</html>
