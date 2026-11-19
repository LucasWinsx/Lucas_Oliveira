<?php
require_once __DIR__ . '/../config/db.php';

/* Model Categoria: CRUD da tabela categorias. */
class Categoria {
    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    // lista com a quantidade de jogos de cada categoria
    public function listar(string $busca = ''): array {
        $sql = 'SELECT c.id, c.nome, c.descricao, COUNT(g.id) AS total_jogos
                FROM categorias c LEFT JOIN games g ON g.categoria_id = c.id';
        $args = [];
        if ($busca !== '') { $sql .= ' WHERE c.nome LIKE ? OR c.descricao LIKE ?'; $args = ['%' . $busca . '%', '%' . $busca . '%']; }
        $st = $this->db->prepare($sql . ' GROUP BY c.id, c.nome, c.descricao ORDER BY c.nome');
        $st->execute($args);
        return $st->fetchAll();
    }
    public function listarNomes(): array {
        return $this->db->query('SELECT nome FROM categorias ORDER BY nome')->fetchAll(PDO::FETCH_COLUMN);
    }
    public function buscarPorId(int $id) {
        $st = $this->db->prepare('SELECT * FROM categorias WHERE id=?');
        $st->execute([$id]);
        return $st->fetch();
    }
    public function nomeExiste(string $nome, int $ignorarId = 0): bool {
        $st = $this->db->prepare('SELECT COUNT(*) FROM categorias WHERE nome=? AND id<>?');
        $st->execute([$nome, $ignorarId]);
        return (int)$st->fetchColumn() > 0;
    }
    public function cadastrar(string $nome, string $descricao): int {
        $this->db->prepare('INSERT INTO categorias (nome, descricao) VALUES (?,?)')
            ->execute([$nome, $descricao !== '' ? $descricao : null]);
        return (int)$this->db->lastInsertId();
    }
    public function atualizar(int $id, string $nome, string $descricao): void {
        $this->db->prepare('UPDATE categorias SET nome=?, descricao=? WHERE id=?')
            ->execute([$nome, $descricao !== '' ? $descricao : null, $id]);
    }
    // os jogos da categoria ficam sem categoria (ON DELETE SET NULL)
    public function deletar(int $id): void {
        $this->db->prepare('DELETE FROM categorias WHERE id=?')->execute([$id]);
    }

    // Devolve o id da categoria pelo nome; se não existir e $criar for true, cria. Nome vazio = null.
    public function idPorNome(string $nome, bool $criar = true): ?int {
        $nome = trim($nome);
        if ($nome === '') return null;
        $st = $this->db->prepare('SELECT id FROM categorias WHERE nome=?');
        $st->execute([$nome]);
        $id = $st->fetchColumn();
        if ($id !== false) return (int)$id;
        return $criar ? $this->cadastrar($nome, '') : null;
    }

    // Valida o formulário. Retorna [dados, erros].
    public function validar(array $p, int $id = 0): array {
        $e = [];
        $nome = trim($p['nome'] ?? '');
        $descricao = trim($p['descricao'] ?? '');
        if ($nome === '' || mb_strlen($nome) > 100) $e[] = 'Informe o nome da categoria (até 100 caracteres).';
        elseif ($this->nomeExiste($nome, $id)) $e[] = 'Já existe uma categoria com esse nome.';
        if (mb_strlen($descricao) > 255) $e[] = 'A descrição pode ter no máximo 255 caracteres.';
        return [['nome' => $nome, 'descricao' => $descricao], $e];
    }
}
