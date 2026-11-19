<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/clientes.php';
exigirAdmin();

$busca = trim($_GET['buscar'] ?? '');
$clientes = (new Cliente())->listar($busca);

pagina_topo('Clientes', 'clientes');
?>
<h2>Clientes</h2>
<div class="acoes-topo"><a class="btn-add" href="cadastrar_cliente.php">+ Cadastrar cliente</a></div>
<form class="busca-simples" method="get" action="ver_clientes.php" role="search">
  <input type="search" name="buscar" value="<?= h($busca) ?>" placeholder="Buscar por nome, e-mail ou telefone" aria-label="Buscar cliente">
  <button>Buscar</button>
</form>
<div class="tabela-wrap">
<table class="tabela">
  <thead><tr><th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>CPF</th><th></th></tr></thead>
  <tbody>
  <?php if (!$clientes): ?><tr><td colspan="6" class="vazio">Nenhum cliente encontrado.</td></tr><?php endif; ?>
  <?php foreach ($clientes as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td>
      <td><strong><?= h($c['nome']) ?></strong></td>
      <td><?= h($c['email']) ?></td>
      <td><?= h(Cliente::formatarTelefone((string)$c['telefone'])) ?: '<span class="pequeno">—</span>' ?></td>
      <td class="pequeno"><?= h($c['cpf'] ?? '') ?: '—' ?></td>
      <td class="acoes">
        <a class="btn-mini editar" href="editar_cliente.php?id=<?= (int)$c['id'] ?>">Editar</a>
        <form method="POST" action="../controllers/deletar_cliente.php"
              onsubmit="return confirm('Excluir o cliente &quot;<?= h(addslashes($c['nome'])) ?>&quot; e o login dele?')">
          <?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn-mini excluir">Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php pagina_fim();
