<?php
require_once __DIR__ . '/../config/db.php';

class Usuario {
    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    public function listar(string $busca = ''): array {
        if ($busca === '') return $this->db->query('SELECT id, nome, email, tipo FROM usuarios ORDER BY id')->fetchAll();
        $st = $this->db->prepare('SELECT id, nome, email, tipo FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY id');
        $st->execute(['%' . $busca . '%', '%' . $busca . '%']);
        return $st->fetchAll();
    }
    public function buscarPorId(int $id) {
        $st = $this->db->prepare('SELECT id, nome, email, tipo FROM usuarios WHERE id=?');
        $st->execute([$id]);
        return $st->fetch();
    }
    public function emailExiste(string $email, int $ignorarId = 0): bool {
        $st = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE email=? AND id<>?');
        $st->execute([$email, $ignorarId]);
        return (int)$st->fetchColumn() > 0;
    }
    public function cadastrar(string $nome, string $email, string $senha, string $tipo): int {
        $this->db->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?,?,?,?)')
            ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
        $id = (int)$this->db->lastInsertId();
        if ($tipo === 'cliente') $this->garantirCliente($id);
        return $id;
    }
    public function atualizar(int $id, string $nome, string $email, string $tipo, ?string $senha): void {
        if ($senha !== null && $senha !== '') {
            $this->db->prepare('UPDATE usuarios SET nome=?, email=?, tipo=?, senha=? WHERE id=?')
                ->execute([$nome, $email, $tipo, password_hash($senha, PASSWORD_DEFAULT), $id]);
        } else {
            $this->db->prepare('UPDATE usuarios SET nome=?, email=?, tipo=? WHERE id=?')
                ->execute([$nome, $email, $tipo, $id]);
        }
        if ($tipo === 'cliente') $this->garantirCliente($id);
    }
    // todo usuário do tipo cliente precisa ter o registro correspondente em clientes
    private function garantirCliente(int $usuarioId): void {
        $this->db->prepare("INSERT IGNORE INTO clientes (usuario_id, telefone) VALUES (?, '')")->execute([$usuarioId]);
    }
    public function deletar(int $id): void {
        $this->db->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
    }

    // Valida o formulário. Retorna [dados, erros].
    public function validar(array $p, bool $senhaObrigatoria, int $id = 0): array {
        $e = [];
        $nome  = trim($p['nome'] ?? '');
        $email = trim($p['email'] ?? '');
        $senha = $p['senha'] ?? '';
        $tipo  = $p['tipo'] ?? 'cliente';
        if ($nome === '') $e[] = 'Informe o nome.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e[] = 'Informe um e-mail válido.';
        elseif ($this->emailExiste($email, $id)) $e[] = 'Este e-mail já está cadastrado.';
        if (!in_array($tipo, ['admin', 'cliente'], true)) $e[] = 'Tipo de usuário inválido.';
        if (($senhaObrigatoria || $senha !== '') && strlen($senha) < 6) $e[] = 'A senha precisa ter ao menos 6 caracteres.';
        return [['nome' => $nome, 'email' => $email, 'tipo' => $tipo, 'senha' => $senha], $e];
    }
}
