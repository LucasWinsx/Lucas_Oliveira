<?php /* Formulário compartilhado por cadastrar_usuario.php e editar_usuario.php */ ?>
<form class="cartao-form larga" method="POST" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $m) echo h($m) . '<br>'; ?></div><?php endif; ?>
  <label>Nome <input name="nome" required maxlength="100" data-regra="nome" value="<?= h($v['nome']) ?>"></label>
  <label>E-mail <input type="email" name="email" required maxlength="150" data-regra="email" value="<?= h($v['email']) ?>"></label>
  <label>Tipo de usuário
    <select name="tipo">
      <option value="cliente" <?= $v['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
      <option value="admin" <?= $v['tipo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
    </select>
  </label>
  <label>Senha<?= $senhaOpcional ? ' (deixe vazio para manter a atual)' : ' (mín. 6 caracteres)' ?>
    <input type="password" name="senha" autocomplete="new-password" data-regra="<?= $senhaOpcional ? 'senhaOpcional' : 'senha' ?>" <?= $senhaOpcional ? '' : 'required' ?>>
  </label>
  <div class="form-botoes"><button><?= h($botao) ?></button><a class="cancelar" href="ver_usuarios.php">Cancelar</a></div>
</form>
