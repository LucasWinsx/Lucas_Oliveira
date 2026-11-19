<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/categorias.php';
exigirAdmin();

$busca = trim($_GET['buscar'] ?? '');
$categorias = (new Categoria())->listar($busca);

pagina_topo('Categorias', 'categorias');
?>
<h2>Categorias</h2>
<div class="acoes-topo"><a class="btn-add" href="cadastrar_categoria.php">+ Cadastrar categoria</a></div>
<form class="busca-simples" method="get" action="ver_categorias.php" role="search">
  <input type="search" name="buscar" value="<?= h($busca) ?>" placeholder="Buscar categoria" aria-label="Buscar categoria">
  <button>Buscar</button>
</form>
<div class="tabela-wrap">
<table class="tabela">
  <thead><tr><th>#</th><th>Nome</th><th>Descrição</th><th>Jogos</th><th></th></tr></thead>
  <tbody>
  <?php if (!$categorias): ?><tr><td colspan="5" class="vazio">Nenhuma categoria encontrada.</td></tr><?php endif; ?>
  <?php foreach ($categorias as $c): ?>
    <tr>
      <td><?= (int)$c['id'] ?></td>
      <td><strong><?= h($c['nome']) ?></strong></td>
      <td class="pequeno"><?= h($c['descricao'] ?? '') ?></td>
      <td><?= (int)$c['total_jogos'] ?></td>
      <td class="acoes">
        <a class="btn-mini editar" href="editar_categoria.php?id=<?= (int)$c['id'] ?>">Editar</a>
        <form method="POST" action="../controllers/deletar_categoria.php"
              onsubmit="return confirm('Excluir a categoria &quot;<?= h(addslashes($c['nome'])) ?>&quot;? Os <?= (int)$c['total_jogos'] ?> jogo(s) dela ficarão sem categoria.')">
          <?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn-mini excluir">Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php pagina_fim();
