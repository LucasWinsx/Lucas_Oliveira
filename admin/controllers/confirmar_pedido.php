<?php
require_once '../config/auth.php';
require_once '../models/pedidos.php';
exigirAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $ok = (new Pedido)->confirmarPagamento((int)($_POST['id'] ?? 0));
    flash($ok ? 'Pagamento confirmado.' : 'Só pedidos pendentes podem ser confirmados.');
}
header('Location: ../views/pedidos.php');
