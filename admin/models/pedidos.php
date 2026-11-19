<?php
require_once __DIR__ . '/../config/db.php';

class Pedido {
    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    // Cria o pedido e BAIXA o estoque na mesma transação.
    public function criar(int $uid, int $gid, int $qtd, string $forma, ?string $statusInicial = null): int {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT titulo, preco, estoque FROM games WHERE id=? FOR UPDATE');
            $st->execute([$gid]);
            $g = $st->fetch();
            if (!$g) throw new Exception('Jogo não encontrado.');
            if ($qtd < 1 || $qtd > (int)$g['estoque']) throw new Exception('Quantidade indisponível em estoque.');
            $this->db->prepare('UPDATE games SET estoque = estoque - ? WHERE id=?')->execute([$qtd, $gid]);
            $status = $statusInicial ?? ($forma === 'cartao' ? 'pago' : 'pendente');
            $this->db->prepare('INSERT INTO pedidos (usuario_id, game_id, titulo_jogo, quantidade, valor_total, forma_pagamento, status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$uid, $gid, $g['titulo'], $qtd, $g['preco'] * $qtd, $forma, $status]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    // Cancela o pedido e DEVOLVE a quantidade ao estoque.
    public function cancelar(int $id): bool {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p || $p['status'] === 'cancelado') { $this->db->rollBack(); return false; }
            if ($p['game_id'] !== null) $this->db->prepare('UPDATE games SET estoque = estoque + ? WHERE id=?')->execute([$p['quantidade'], $p['game_id']]);
            $this->db->prepare("UPDATE pedidos SET status='cancelado', cancelado_em=NOW() WHERE id=?")->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function confirmarPagamento(int $id): bool {
        $st = $this->db->prepare("UPDATE pedidos SET status='pago' WHERE id=? AND status='pendente'");
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    // Edita quantidade, forma de pagamento e status. Ajusta o estoque pela diferença, na mesma transação.
    public function atualizar(int $id, int $qtd, string $forma, string $status): void {
        if (!in_array($forma, ['cartao', 'pix', 'boleto'], true)) throw new Exception('Forma de pagamento inválida.');
        if (!in_array($status, ['pendente', 'pago', 'cancelado'], true)) throw new Exception('Status inválido.');
        if ($qtd < 1) throw new Exception('A quantidade deve ser de pelo menos 1.');
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p) throw new Exception('Pedido não encontrado.');

            $reservadoAntes  = $p['status'] === 'cancelado' ? 0 : (int)$p['quantidade'];
            $reservadoDepois = $status === 'cancelado' ? 0 : $qtd;
            $delta = $reservadoDepois - $reservadoAntes;   // positivo = sai mais estoque

            if ($p['game_id'] !== null) {
                $g = $this->db->prepare('SELECT estoque FROM games WHERE id=? FOR UPDATE');
                $g->execute([$p['game_id']]);
                $estoque = $g->fetchColumn();
                if ($estoque !== false) {
                    if ($delta > (int)$estoque) throw new Exception('Estoque insuficiente para essa quantidade (disponível: ' . (int)$estoque . ').');
                    if ($delta !== 0) $this->db->prepare('UPDATE games SET estoque = estoque - ? WHERE id=?')->execute([$delta, $p['game_id']]);
                }
            }
            $unitario = (float)$p['valor_total'] / max(1, (int)$p['quantidade']);   // mantém o preço da época da compra
            $canceladoEm = $status === 'cancelado' ? ($p['cancelado_em'] ?: date('Y-m-d H:i:s')) : null;
            $this->db->prepare('UPDATE pedidos SET quantidade=?, valor_total=?, forma_pagamento=?, status=?, cancelado_em=? WHERE id=?')
                ->execute([$qtd, round($unitario * $qtd, 2), $forma, $status, $canceladoEm, $id]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    // Exclui o pedido. Se ele ainda reservava estoque (não cancelado), devolve as unidades.
    public function deletar(int $id): bool {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT * FROM pedidos WHERE id=? FOR UPDATE');
            $st->execute([$id]);
            $p = $st->fetch();
            if (!$p) { $this->db->rollBack(); return false; }
            if ($p['status'] !== 'cancelado' && $p['game_id'] !== null)
                $this->db->prepare('UPDATE games SET estoque = estoque + ? WHERE id=?')->execute([$p['quantidade'], $p['game_id']]);
            $this->db->prepare('DELETE FROM pedidos WHERE id=?')->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function buscar(int $id) {
        $st = $this->db->prepare('SELECT * FROM pedidos WHERE id=?');
        $st->execute([$id]);
        return $st->fetch();
    }

    public function listarTodos(string $status = ''): array {
        $sql = 'SELECT p.*, u.nome AS cliente, u.email FROM pedidos p LEFT JOIN usuarios u ON u.id=p.usuario_id';
        $args = [];
        if (in_array($status, ['pendente', 'pago', 'cancelado'], true)) { $sql .= ' WHERE p.status=?'; $args[] = $status; }
        $st = $this->db->prepare($sql . ' ORDER BY p.id DESC');
        $st->execute($args);
        return $st->fetchAll();
    }

    public function listarDoUsuario(int $uid): array {
        $st = $this->db->prepare('SELECT * FROM pedidos WHERE usuario_id=? ORDER BY id DESC');
        $st->execute([$uid]);
        return $st->fetchAll();
    }
}
