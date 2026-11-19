<?php
require_once '../config/auth.php';
require_once '../models/categorias.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $m = new Categoria();
    $id = (int)($_POST['id'] ?? 0);
    $c = $m->buscarPorId($id);
    if ($c) { $m->deletar($id); flash('Categoria "' . $c['nome'] . '" excluída.'); }
    else flash('Categoria não encontrada.');
}
header('Location: ../views/ver_categorias.php');
