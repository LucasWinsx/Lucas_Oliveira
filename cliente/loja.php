<?php
/* A vitrine agora é o catálogo (catalogo.php); esta página só redireciona. */
require_once '../admin/config/auth.php';
header('Location: ' . BASE . '/catalogo.php');
exit;
