<?php
require_once __DIR__ . '/../config/db.php';

/* Consultas do dashboard e do relatório de vendas (somente leitura).
   "Venda" = pedido com status 'pago'. */
class Relatorio {
    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    private function um(string $sql, array $args = []) {
        $st = $this->db->prepare($sql); $st->execute($args);
        return $st->fetchColumn();
    }
    private function todos(string $sql, array $args = []): array {
        $st = $this->db->prepare($sql); $st->execute($args);
        return $st->fetchAll();
    }

    public function resumo(): array {
        $pagos = $this->db->query("SELECT COUNT(*) AS n, COALESCE(SUM(valor_total),0) AS total, COALESCE(SUM(quantidade),0) AS unidades FROM pedidos WHERE status='pago'")->fetch();
        return [
            'faturamento'   => (float)$pagos['total'],
            'vendas'        => (int)$pagos['n'],
            'unidades'      => (int)$pagos['unidades'],
            'ticket_medio'  => (int)$pagos['n'] > 0 ? (float)$pagos['total'] / (int)$pagos['n'] : 0.0,
            'pendentes'     => (int)$this->um("SELECT COUNT(*) FROM pedidos WHERE status='pendente'"),
            'cancelados'    => (int)$this->um("SELECT COUNT(*) FROM pedidos WHERE status='cancelado'"),
            'clientes'      => (int)$this->um('SELECT COUNT(*) FROM clientes'),
            'jogos'         => (int)$this->um('SELECT COUNT(*) FROM games'),
            'categorias'    => (int)$this->um('SELECT COUNT(*) FROM categorias'),
        ];
    }

    // faturamento dos últimos $meses meses (inclui meses sem venda): [['mes'=>'2026-11','rotulo'=>'11/2026','total'=>0.0], ...]
    public function vendasPorMes(int $meses = 6): array {
        $linhas = $this->todos("SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, SUM(valor_total) AS total FROM pedidos WHERE status='pago' GROUP BY mes");
        $por = [];
        foreach ($linhas as $l) $por[$l['mes']] = (float)$l['total'];
        $out = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $d = new DateTime('first day of this month');
            $d->modify("-$i month");
            $k = $d->format('Y-m');
            $out[] = ['mes' => $k, 'rotulo' => $d->format('m/Y'), 'total' => $por[$k] ?? 0.0];
        }
        return $out;
    }

    public function vendasPorCategoria(): array {
        return $this->todos("SELECT COALESCE(c.nome, 'Sem categoria') AS categoria, SUM(p.quantidade) AS unidades, SUM(p.valor_total) AS total
            FROM pedidos p LEFT JOIN games g ON g.id = p.game_id LEFT JOIN categorias c ON c.id = g.categoria_id
            WHERE p.status='pago' GROUP BY categoria ORDER BY total DESC");
    }

    public function maisVendidos(int $limite = 5): array {
        return $this->todos("SELECT titulo_jogo, SUM(quantidade) AS unidades, SUM(valor_total) AS total
            FROM pedidos WHERE status='pago' GROUP BY titulo_jogo ORDER BY unidades DESC, total DESC LIMIT " . (int)$limite);
    }

    public function estoqueBaixo(int $limite = 5, int $ate = 5): array {
        return $this->todos('SELECT id, titulo, estoque FROM games WHERE estoque <= ? ORDER BY estoque, titulo LIMIT ' . (int)$limite, [$ate]);
    }

    public function ultimosPedidos(int $limite = 5): array {
        return $this->todos('SELECT p.id, p.titulo_jogo, p.quantidade, p.valor_total, p.status, p.criado_em, u.nome AS cliente
            FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id DESC LIMIT ' . (int)$limite);
    }

    // Relatório de vendas (pedidos pagos) em um período, datas no formato Y-m-d
    public function vendasPeriodo(string $de, string $ate): array {
        return $this->todos("SELECT p.id, p.criado_em, p.titulo_jogo, p.quantidade, p.valor_total, p.forma_pagamento, u.nome AS cliente
            FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.status='pago' AND p.criado_em >= ? AND p.criado_em < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY p.criado_em DESC",
            [$de . ' 00:00:00', $ate]);
    }
}
