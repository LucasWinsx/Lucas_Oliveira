<?php /* Formulário compartilhado por cadastrar_cliente.php e editar_cliente.php */ ?>
<form class="cartao-form" method="POST" data-validar>
  <?= csrfCampo() ?>
  <?php if ($erros): ?><div class="erro"><?php foreach ($erros as $m) echo h($m) . '<br>'; ?></div><?php endif; ?>
  <label>Nome <input name="nome" required maxlength="100" data-regra="nome" value="<?= h($v['nome']) ?>"></label>
  <label>E-mail <input type="email" name="email" required maxlength="150" data-regra="email" value="<?= h($v['email']) ?>"></label>
  <label>Telefone (com DDD) <input type="tel" name="telefone" required inputmode="numeric" data-regra="telefone" data-mascara="telefone" placeholder="(38) 99999-0000" value="<?= h($v['telefone']) ?>"></label>
  <label>CPF (opcional) <input name="cpf" inputmode="numeric" data-regra="cpf" data-mascara="cpf" placeholder="000.000.000-00" value="<?= h($v['cpf']) ?>"></label>
  <label>Data de nascimento (opcional) <input type="date" name="data_nascimento" data-regra="data" value="<?= h($v['data_nascimento']) ?>"></label>
  <label>Senha<?= $senhaOpcional ? ' (deixe vazio para manter a atual)' : ' (mín. 6 caracteres)' ?>
    <input type="password" name="senha" autocomplete="new-password" data-regra="<?= $senhaOpcional ? 'senhaOpcional' : 'senha' ?>" <?= $senhaOpcional ? '' : 'required' ?>>
  </label>
  <div class="form-botoes"><button><?= h($botao) ?></button><a class="cancelar" href="ver_clientes.php">Cancelar</a></div>
</form>
