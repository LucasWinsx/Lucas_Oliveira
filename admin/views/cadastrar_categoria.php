<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/categorias.php';
exigirAdmin();

$m = new Categoria();
$erros = [];
$v = ['nome' => '', 'descricao' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST);
        if (!$erros) {
            $m->cadastrar($d['nome'], $d['descricao']);
            flash('Categoria cadastrada com sucesso.');
            header('Location: ver_categorias.php'); exit;
        }
    }
}
$botao = 'Cadastrar categoria';
pagina_topo('Cadastrar categoria', 'categorias');
?>
<h2>Cadastrar nova categoria</h2>
<?php include '_form_categoria.php'; ?>
<?php pagina_fim();
