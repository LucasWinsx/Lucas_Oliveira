<?php
require_once '../config/auth.php';
require_once '../models/usuarios.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)usuario()['id']) {
        flash('Você não pode excluir a própria conta.');
    } else {
        $m = new Usuario();
        $u = $m->buscarPorId($id);
        if ($u) {
            try { $m->deletar($id); flash('Usuário "' . $u['nome'] . '" excluído.'); }
            catch (PDOException $e) { flash('Não foi possível excluir "' . $u['nome'] . '": ele possui pedidos registrados.'); }
        }
        else flash('Usuário não encontrado.');
    }
}
header('Location: ../views/ver_usuarios.php');
