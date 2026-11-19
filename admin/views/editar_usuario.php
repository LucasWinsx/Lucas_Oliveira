<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/usuarios.php';
exigirAdmin();

$m = new Usuario();
$id = (int)($_GET['id'] ?? 0);
$u = $m->buscarPorId($id);
if (!$u) { flash('Usuário não encontrado.'); header('Location: ver_usuarios.php'); exit; }

$erros = [];
$v = ['nome' => $u['nome'], 'email' => $u['email'], 'tipo' => $u['tipo']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST, false, $id);
        if ($id === (int)usuario()['id'] && $d['tipo'] !== 'admin')
            $erros[] = 'Você não pode remover seu próprio acesso de administrador.';
        if (!$erros) {
            $m->atualizar($id, $d['nome'], $d['email'], $d['tipo'], $d['senha']);
            if ($id === (int)usuario()['id']) $_SESSION['u']['nome'] = $d['nome'];
            flash('Usuário atualizado com sucesso.');
            header('Location: ver_usuarios.php'); exit;
        }
    }
}
$senhaOpcional = true;
$botao = 'Salvar alterações';
pagina_topo('Editar usuário', 'usuarios');
?>
<h2>Editar usuário #<?= (int)$u['id'] ?></h2>
<?php include '_form_usuario.php'; ?>
<?php pagina_fim();
