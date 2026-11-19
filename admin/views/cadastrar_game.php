<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/games.php';
exigirAdmin();

$game = new Game();
$erros = [];
$v = ['titulo' => '', 'descricao' => '', 'preco' => '', 'estoque' => '0', 'categoria' => '', 'trailer' => '',
      'desenvolvedora' => '', 'lancamento' => '', 'classificacao' => '', 'jogadores' => '', 'plataforma' => '', 'plataforma_outra' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = Game::validar($_POST);
        if (!$erros) {
            try {
                $d['imagem'] = Game::salvarImagem($_FILES['imagem'] ?? []) ?? '';
                $game->cadastrar($d);
                flash('Jogo cadastrado com sucesso.');
                header('Location: gerenciar_games.php'); exit;
            } catch (Exception $e) { $erros[] = $e->getMessage(); }
        }
    }
}
$categorias = $game->categorias();
$imagemAtual = null;
$botao = 'Cadastrar jogo';
pagina_topo('Cadastrar jogo', 'jogos');
?>
<h2>Cadastrar novo jogo</h2>
<?php include '_form_game.php'; ?>
<?php pagina_fim();
