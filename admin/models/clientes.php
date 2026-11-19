<?php
require_once __DIR__ . '/../config/db.php';

/* Model Cliente: dados de contato do cliente (tabela clientes) ligados a um usuário (tabela usuarios). */
class Cliente {
    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    private const SELECT = 'SELECT c.id, c.usuario_id, c.telefone, c.cpf, c.data_nascimento, c.criado_em, u.nome, u.email
                            FROM clientes c JOIN usuarios u ON u.id = c.usuario_id';

    public function listar(string $busca = ''): array {
        if ($busca === '') return $this->db->query(self::SELECT . ' ORDER BY u.nome')->fetchAll();
        $st = $this->db->prepare(self::SELECT . ' WHERE u.nome LIKE ? OR u.email LIKE ? OR c.telefone LIKE ? ORDER BY u.nome');
        $like = '%' . $busca . '%';
        $st->execute([$like, $like, $like]);
        return $st->fetchAll();
    }
    public function buscarPorId(int $id) {
        $st = $this->db->prepare(self::SELECT . ' WHERE c.id=?');
        $st->execute([$id]);
        return $st->fetch();
    }
    public function emailExiste(string $email, int $ignorarUsuarioId = 0): bool {
        $st = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE email=? AND id<>?');
        $st->execute([$email, $ignorarUsuarioId]);
        return (int)$st->fetchColumn() > 0;
    }
    public function cpfExiste(string $cpf, int $ignorarId = 0): bool {
        $st = $this->db->prepare('SELECT COUNT(*) FROM clientes WHERE cpf=? AND id<>?');
        $st->execute([$cpf, $ignorarId]);
        return (int)$st->fetchColumn() > 0;
    }
    public function totalPedidos(int $usuarioId): int {
        $st = $this->db->prepare('SELECT COUNT(*) FROM pedidos WHERE usuario_id=?');
        $st->execute([$usuarioId]);
        return (int)$st->fetchColumn();
    }

    // Cria o usuário (tipo cliente) e o cliente na mesma transação. Usado pelo admin e pelo registro público.
    public function cadastrar(array $d): int {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?,?,?, 'cliente')")
                ->execute([$d['nome'], $d['email'], password_hash($d['senha'], PASSWORD_DEFAULT)]);
            $uid = (int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO clientes (usuario_id, telefone, cpf, data_nascimento) VALUES (?,?,?,?)')
                ->execute([$uid, $d['telefone'], $d['cpf'], $d['data_nascimento']]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function atualizar(int $id, array $d): void {
        $c = $this->buscarPorId($id);
        if (!$c) throw new Exception('Cliente não encontrado.');
        $this->db->beginTransaction();
        try {
            if ($d['senha'] !== '') {
                $this->db->prepare('UPDATE usuarios SET nome=?, email=?, senha=? WHERE id=?')
                    ->execute([$d['nome'], $d['email'], password_hash($d['senha'], PASSWORD_DEFAULT), $c['usuario_id']]);
            } else {
                $this->db->prepare('UPDATE usuarios SET nome=?, email=? WHERE id=?')
                    ->execute([$d['nome'], $d['email'], $c['usuario_id']]);
            }
            $this->db->prepare('UPDATE clientes SET telefone=?, cpf=?, data_nascimento=? WHERE id=?')
                ->execute([$d['telefone'], $d['cpf'], $d['data_nascimento'], $id]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    // Exclui o cliente e o usuário de login dele. Não permite se houver pedidos (histórico de vendas).
    public function deletar(int $id): void {
        $c = $this->buscarPorId($id);
        if (!$c) throw new Exception('Cliente não encontrado.');
        if ($this->totalPedidos((int)$c['usuario_id']) > 0)
            throw new Exception('Este cliente possui pedidos e não pode ser excluído.');
        $this->db->prepare('DELETE FROM usuarios WHERE id=?')->execute([$c['usuario_id']]); // clientes some em cascata
    }

    // Valida o formulário (admin e registro público). Retorna [dados, erros].
    public function validar(array $p, bool $senhaObrigatoria, int $id = 0): array {
        $e = [];
        $c = $id ? $this->buscarPorId($id) : null;
        $nome  = trim($p['nome'] ?? '');
        $email = trim($p['email'] ?? '');
        $tel   = preg_replace('/\D+/', '', (string)($p['telefone'] ?? ''));
        $cpf   = preg_replace('/\D+/', '', (string)($p['cpf'] ?? ''));
        $nasc  = trim($p['data_nascimento'] ?? '');
        $senha = (string)($p['senha'] ?? '');

        if ($nome === '' || mb_strlen($nome) > 100) $e[] = 'Informe o nome (até 100 caracteres).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e[] = 'Informe um e-mail válido.';
        elseif ($this->emailExiste($email, $c ? (int)$c['usuario_id'] : 0)) $e[] = 'Este e-mail já está cadastrado.';
        if (strlen($tel) < 10 || strlen($tel) > 11) $e[] = 'Informe o telefone com DDD (10 ou 11 dígitos).';
        if ($cpf !== '') {
            if (!self::cpfValido($cpf)) $e[] = 'CPF inválido.';
            elseif ($this->cpfExiste($cpf, $id)) $e[] = 'Este CPF já está cadastrado.';
        }
        if ($nasc !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $nasc);
            if (!$dt || $dt->format('Y-m-d') !== $nasc || $dt > new DateTime('today')) $e[] = 'Data de nascimento inválida.';
        }
        if (($senhaObrigatoria || $senha !== '') && strlen($senha) < 6) $e[] = 'A senha precisa ter ao menos 6 caracteres.';
        return [[
            'nome' => $nome, 'email' => $email, 'telefone' => $tel,
            'cpf' => $cpf !== '' ? $cpf : null,
            'data_nascimento' => $nasc !== '' ? $nasc : null,
            'senha' => $senha,
        ], $e];
    }

    public static function cpfValido(string $cpf): bool {
        if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += (int)$cpf[$i] * (($t + 1) - $i);
            $dv = ((10 * $soma) % 11) % 10;
            if ((int)$cpf[$t] !== $dv) return false;
        }
        return true;
    }

    public static function formatarTelefone(string $t): string {
        $t = preg_replace('/\D+/', '', $t);
        if (strlen($t) === 11) return sprintf('(%s) %s-%s', substr($t, 0, 2), substr($t, 2, 5), substr($t, 7));
        if (strlen($t) === 10) return sprintf('(%s) %s-%s', substr($t, 0, 2), substr($t, 2, 4), substr($t, 6));
        return $t;
    }
}
