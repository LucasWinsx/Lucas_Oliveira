<?php
require_once '../config/auth.php';
require_once '../models/clientes.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $m = new Cliente();
    $id = (int)($_POST['id'] ?? 0);
    $c = $m->buscarPorId($id);
    if (!$c) {
        flash('Cliente não encontrado.');
    } elseif ((int)$c['usuario_id'] === (int)usuario()['id']) {
        flash('Você não pode excluir a própria conta.');
    } else {
        try { $m->deletar($id); flash('Cliente "' . $c['nome'] . '" excluído.'); }
        catch (Throwable $e) { flash($e->getMessage()); }
    }
}
header('Location: ../views/ver_clientes.php');
