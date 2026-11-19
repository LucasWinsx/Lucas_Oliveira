<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/clientes.php';
exigirAdmin();

$m = new Cliente();
$id = (int)($_GET['id'] ?? 0);
$c = $m->buscarPorId($id);
if (!$c) { flash('Cliente não encontrado.'); header('Location: ver_clientes.php'); exit; }

$erros = [];
$v = [
    'nome' => $c['nome'], 'email' => $c['email'],
    'telefone' => Cliente::formatarTelefone((string)$c['telefone']),
    'cpf' => (string)$c['cpf'], 'data_nascimento' => (string)$c['data_nascimento'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST, false, $id);
        if (!$erros) {
            try {
                $m->atualizar($id, $d);
                if ((int)$c['usuario_id'] === (int)usuario()['id']) $_SESSION['u']['nome'] = $d['nome'];
                flash('Cliente atualizado com sucesso.');
                header('Location: ver_clientes.php'); exit;
            } catch (Throwable $e) { $erros[] = 'Não foi possível salvar as alterações.'; }
        }
    }
}
$senhaOpcional = true;
$botao = 'Salvar alterações';
pagina_topo('Editar cliente', 'clientes');
?>
<h2>Editar cliente #<?= (int)$c['id'] ?></h2>
<?php include '_form_cliente.php'; ?>
<?php pagina_fim();
