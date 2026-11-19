<?php
require_once '../config/auth.php';
require_once '../models/pedidos.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $id = (int)($_POST['id'] ?? 0);
    try {
        flash((new Pedido())->deletar($id) ? 'Pedido #' . $id . ' excluído.' : 'Pedido não encontrado.');
    } catch (Throwable $e) { flash('Não foi possível excluir o pedido.'); }
}
header('Location: ../views/pedidos.php');
