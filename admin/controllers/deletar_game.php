<?php
require_once '../config/auth.php';
require_once '../models/games.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfOk()) {
    $game = new Game();
    $id = (int)($_POST['id'] ?? 0);
    $g = $game->buscarPorId($id);
    if ($g) {
        $game->deletar($id);
        if (!empty($g['imagem']) && !$game->imagemEmUso($g['imagem'])) Game::removerImagem($g['imagem']);
        flash('Jogo "' . $g['titulo'] . '" excluído.');
    } else {
        flash('Jogo não encontrado.');
    }
}
header('Location: ../views/gerenciar_games.php');
