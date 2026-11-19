<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/clientes.php';
exigirAdmin();

$m = new Cliente();
$erros = [];
$v = ['nome' => '', 'email' => '', 'telefone' => '', 'cpf' => '', 'data_nascimento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST, true);
        if (!$erros) {
            try {
                $m->cadastrar($d);
                flash('Cliente cadastrado com sucesso.');
                header('Location: ver_clientes.php'); exit;
            } catch (Throwable $e) { $erros[] = 'Não foi possível cadastrar o cliente.'; }
        }
    }
}
$senhaOpcional = false;
$botao = 'Cadastrar cliente';
pagina_topo('Cadastrar cliente', 'clientes');
?>
<h2>Cadastrar novo cliente</h2>
<?php include '_form_cliente.php'; ?>
<?php pagina_fim();
