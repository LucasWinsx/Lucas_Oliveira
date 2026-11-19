<?php
/* Biblioteca do cliente: jogos que ele já comprou (pedidos não cancelados).
   Usa a mesma consulta da página "Meus pedidos" (Pedido::listarDoUsuario). */

function pedidosDoUsuario(PDO $pdo, int $uid): array {
    $rows = [];
    try {
        if (!class_exists('Pedido')) require_once __DIR__ . '/../models/pedidos.php';
        if (class_exists('Pedido')) $rows = (new Pedido)->listarDoUsuario($uid);
    } catch (Throwable $e) { $rows = []; }
    if (!$rows) {                                   // plano B: direto na tabela
        try {
            $st = $pdo->prepare('SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC');
            $st->execute([$uid]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $rows = []; }
    }
    return is_array($rows) ? $rows : [];
}

/* [game_id => ['qtd'=>, 'data'=>, 'pago'=>bool]] dos jogos comprados e não cancelados. */
function jogosComprados(PDO $pdo, int $uid): array {
    $out = [];
    if ($uid <= 0) return $out;
    foreach (pedidosDoUsuario($pdo, $uid) as $r) {
        if (($r['status'] ?? '') === 'cancelado') continue;
        $gid = (int)($r['game_id'] ?? 0);
        if (!$gid && !empty($r['titulo_jogo'])) {   // pedido sem game_id: acha o jogo pelo título
            try { $q = $pdo->prepare('SELECT id FROM games WHERE titulo = ? LIMIT 1'); $q->execute([$r['titulo_jogo']]); $gid = (int)$q->fetchColumn(); } catch (Throwable $e) {}
        }
        if (!$gid) continue;
        if (!isset($out[$gid])) $out[$gid] = ['qtd' => 0, 'data' => $r['criado_em'] ?? '', 'pago' => false];
        $out[$gid]['qtd'] += max(1, (int)($r['quantidade'] ?? 1));
        if (($r['status'] ?? '') === 'pago') $out[$gid]['pago'] = true;
    }
    return $out;
}

function idsComprados(PDO $pdo, int $uid): array {
    return array_map('intval', array_keys(jogosComprados($pdo, $uid)));
}

/* Selo "Comprado" para colocar dentro da capa de um card. */
function seloComprado($gameId, array $comprados): string {
    return in_array((int)$gameId, $comprados, true) ? '<span class="comprado">&#10003; Comprado</span>' : '';
}

/* CSS do selo (use dentro de um <style>). */
function cssComprado(): string {
    return '.capa .comprado{position:absolute;right:10px;top:10px;z-index:3;background:rgba(18,120,60,.92);color:#fff;padding:3px 11px;border-radius:99px;font-size:12px;font-weight:600;font-style:normal;box-shadow:0 2px 10px rgba(0,0,0,.35)}';
}

/* Pré-venda: jogo cuja data de lançamento ainda não chegou (comparada com a data e hora de agora).
   Devolve o timestamp da estreia, ou null se não há data ou se o jogo já foi lançado. */
function estreiaFutura(array $g): ?int {
    $l = trim((string)($g['lancamento'] ?? ''));
    if ($l === '') return null;
    $ts = strtotime($l);
    return ($ts !== false && $ts > time()) ? $ts : null;
}

/* Etiqueta "Pré-venda" + data da estreia (vazio se o jogo já foi lançado). Use embaixo do preço ou do título. */
function estreiaHtml(array $g): string {
    $ts = estreiaFutura($g);
    if ($ts === null) return '';
    $data = date('d/m/Y', $ts) . (date('H:i', $ts) !== '00:00' ? ' às ' . date('H:i', $ts) : '');
    return '<div class="estreia"><b>Pré-venda</b><span>Estreia: ' . htmlspecialchars($data, ENT_QUOTES, 'UTF-8') . '</span></div>';
}

/* Ícone de relógio (pequeno) para o canto superior direito da capa de um jogo em pré-venda. Vazio se já foi lançado. */
function relogioPreVenda(array $g): string {
    $ts = estreiaFutura($g);
    if ($ts === null) return '';
    return '<span class="relogio" role="img" aria-label="Pré-venda" title="Pré-venda · estreia em ' . date('d/m/Y', $ts) . '">'
         . '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>'
         . '</span>';
}

/* CSS da etiqueta de pré-venda (use dentro de um <style>). */
function cssPreVenda(): string {
    return '.estreia{display:flex;flex-wrap:wrap;align-items:center;gap:4px 8px;margin:5px 0 0;font-size:13px;line-height:1.3;color:#4a5063}'
         . '.estreia b{background:#0070d1;color:#fff;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:2px 9px;border-radius:99px}'
         . '.capa .relogio,.mj-capa .relogio{position:absolute;right:8px;top:8px;z-index:3;width:22px;height:22px;display:grid;place-items:center;border-radius:50%;background:rgba(13,27,76,.88);color:#fff;box-shadow:0 1px 6px rgba(0,0,0,.4);font-style:normal}'
         . '.capa .relogio ~ .comprado{top:38px}';
}
