<?php /* Formulário compartilhado por cadastrar_categoria.php e editar_categoria.php */ ?>
<form class="cartao-form" method="POST" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $m) echo h($m) . '<br>'; ?></div><?php endif; ?>
  <label>Nome <input name="nome" required maxlength="100" data-regra="nome" value="<?= h($v['nome']) ?>"></label>
  <label>Descrição (opcional) <input name="descricao" maxlength="255" value="<?= h($v['descricao']) ?>"></label>
  <div class="form-botoes"><button><?= h($botao) ?></button><a class="cancelar" href="ver_categorias.php">Cancelar</a></div>
</form>
