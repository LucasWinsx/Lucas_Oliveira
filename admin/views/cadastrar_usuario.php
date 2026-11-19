<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/usuarios.php';
exigirAdmin();

$m = new Usuario();
$erros = [];
$v = ['nome' => '', 'email' => '', 'tipo' => 'cliente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST, true);
        if (!$erros) {
            $m->cadastrar($d['nome'], $d['email'], $d['senha'], $d['tipo']);
            flash('Usuário cadastrado com sucesso.');
            header('Location: ver_usuarios.php'); exit;
        }
    }
}
$senhaOpcional = false;
$botao = 'Cadastrar usuário';
pagina_topo('Cadastrar usuário', 'usuarios');
?>
<h2>Cadastrar novo usuário</h2>
<?php include '_form_usuario.php'; ?>
<?php pagina_fim();
