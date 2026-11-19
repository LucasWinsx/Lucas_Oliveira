<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/categorias.php';
exigirAdmin();

$m = new Categoria();
$id = (int)($_GET['id'] ?? 0);
$c = $m->buscarPorId($id);
if (!$c) { flash('Categoria não encontrada.'); header('Location: ver_categorias.php'); exit; }

$erros = [];
$v = ['nome' => $c['nome'], 'descricao' => (string)$c['descricao']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = $m->validar($_POST, $id);
        if (!$erros) {
            $m->atualizar($id, $d['nome'], $d['descricao']);
            flash('Categoria atualizada com sucesso.');
            header('Location: ver_categorias.php'); exit;
        }
    }
}
$botao = 'Salvar alterações';
pagina_topo('Editar categoria', 'categorias');
?>
<h2>Editar categoria #<?= (int)$c['id'] ?></h2>
<?php include '_form_categoria.php'; ?>
<?php pagina_fim();
