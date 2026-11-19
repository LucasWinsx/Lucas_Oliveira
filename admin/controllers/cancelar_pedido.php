<?php
require_once '../config/auth.php';
require_once '../models/pedidos.php';
exigirAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $ok = (new Pedido)->cancelar((int)($_POST['id'] ?? 0));
    flash($ok ? 'Pedido cancelado e estoque devolvido.' : 'Pedido não encontrado ou já cancelado.');
}
header('Location: ../views/pedidos.php');
