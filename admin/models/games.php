<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/categorias.php';

class Game {
    // consulta base: devolve o jogo já com o nome da categoria (coluna "categoria")
    public const SQL_SELECT = 'SELECT g.*, c.nome AS categoria FROM games g LEFT JOIN categorias c ON c.id = g.categoria_id';

    private PDO $db;
    public function __construct() { $this->db = Database::conectar(); }

    public function listarGames(): array {
        return $this->db->query(self::SQL_SELECT . ' ORDER BY g.id DESC')->fetchAll();
    }
    public function buscarPorNome(string $nome): array {
        $st = $this->db->prepare(self::SQL_SELECT . ' WHERE g.titulo LIKE ? ORDER BY g.id DESC');
        $st->execute(['%' . $nome . '%']);
        return $st->fetchAll();
    }
    public function buscarPorId(int $id) {
        $st = $this->db->prepare(self::SQL_SELECT . ' WHERE g.id=?');
        $st->execute([$id]);
        return $st->fetch();
    }
    // nomes de todas as categorias (sugestões do formulário)
    public function categorias(): array {
        return (new Categoria())->listarNomes();
    }
    // somente categorias que têm jogos (filtros do catálogo)
    public static function categoriasComJogos(): array {
        return Database::conectar()->query(
            'SELECT DISTINCT c.nome FROM games g JOIN categorias c ON c.id = g.categoria_id ORDER BY c.nome'
        )->fetchAll(PDO::FETCH_COLUMN);
    }
    // unidades vendidas por jogo (pedidos não cancelados): [game_id => quantidade]
    public static function vendidosPorJogo(): array {
        $v = [];
        foreach (Database::conectar()->query("SELECT game_id, SUM(quantidade) AS q FROM pedidos WHERE status <> 'cancelado' AND game_id IS NOT NULL GROUP BY game_id") as $r)
            $v[(int)$r['game_id']] = (int)$r['q'];
        return $v;
    }
    // imagem e título do último jogo cadastrado que tem imagem (usado no fundo do login/registro)
    public static function ultimoComImagem() {
        return Database::conectar()->query("SELECT imagem, titulo FROM games WHERE imagem IS NOT NULL AND imagem <> '' ORDER BY id DESC LIMIT 1")->fetch() ?: null;
    }
    public function cadastrar(array $d): int {
        $cid = (new Categoria())->idPorNome($d['categoria'] ?? '');
        $this->db->prepare('INSERT INTO games (titulo, descricao, preco, estoque, categoria_id, imagem, trailer, desenvolvedora, lancamento, classificacao, jogadores, plataforma) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$d['titulo'], $d['descricao'], $d['preco'], $d['estoque'], $cid, $d['imagem'], $d['trailer'] !== '' ? $d['trailer'] : null,
                       self::ouNulo($d['desenvolvedora'] ?? null), self::ouNulo($d['lancamento'] ?? null), self::ouNulo($d['classificacao'] ?? null), self::ouNulo($d['jogadores'] ?? null), self::ouNulo($d['plataforma'] ?? null)]);
        return (int)$this->db->lastInsertId();
    }
    public function atualizar(int $id, array $d): void {
        $cid = (new Categoria())->idPorNome($d['categoria'] ?? '');
        $this->db->prepare('UPDATE games SET titulo=?, descricao=?, preco=?, estoque=?, categoria_id=?, imagem=?, trailer=?, desenvolvedora=?, lancamento=?, classificacao=?, jogadores=?, plataforma=? WHERE id=?')
            ->execute([$d['titulo'], $d['descricao'], $d['preco'], $d['estoque'], $cid, $d['imagem'], $d['trailer'] !== '' ? $d['trailer'] : null,
                       self::ouNulo($d['desenvolvedora'] ?? null), self::ouNulo($d['lancamento'] ?? null), self::ouNulo($d['classificacao'] ?? null), self::ouNulo($d['jogadores'] ?? null), self::ouNulo($d['plataforma'] ?? null), $id]);
    }
    // texto vazio vira NULL no banco (campos opcionais)
    private static function ouNulo($v) { return ($v === null || $v === '') ? null : $v; }
    public function deletar(int $id): void {
        $this->db->prepare('DELETE FROM games WHERE id=?')->execute([$id]);
    }
    public function imagemEmUso(string $nome): bool {
        $st = $this->db->prepare('SELECT COUNT(*) FROM games WHERE imagem=?');
        $st->execute([$nome]);
        return (int)$st->fetchColumn() > 0;
    }

    // Valida o formulário. Retorna [dados, erros].
    public static function validar(array $p): array {
        $e = [];
        $titulo = trim($p['titulo'] ?? '');
        if ($titulo === '' || mb_strlen($titulo) > 200) $e[] = 'Informe o título (até 200 caracteres).';
        $raw = trim($p['preco'] ?? '');
        if (strpos($raw, ',') !== false) $raw = str_replace(',', '.', str_replace('.', '', $raw));
        if (!is_numeric($raw) || (float)$raw < 0) $e[] = 'Preço inválido. Exemplo: 229,90';
        $estoque = filter_var($p['estoque'] ?? '', FILTER_VALIDATE_INT);
        if ($estoque === false || $estoque < 0) $e[] = 'Estoque deve ser um número inteiro maior ou igual a zero.';
        // trailer (opcional): link do YouTube ou arquivo de vídeo .mp4/.webm/.ogv
        $trailer = trim($p['trailer'] ?? '');
        if ($trailer !== '') {
            $yt = preg_match('~^https?://(www\\.|m\\.|music\\.)?(youtube(-nocookie)?\\.com|youtu\\.be)/~i', $trailer)
                  && preg_match('~(?:youtu\\.be/|youtube(?:-nocookie)?\\.com/(?:watch\\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~i', $trailer);
            $arq = preg_match('~^(https?://\\S+|[^\\s/\\\\]+)\\.(mp4|webm|ogv)(\\?\\S*)?$~i', $trailer);
            if (mb_strlen($trailer) > 255) $e[] = 'O link do trailer pode ter no máximo 255 caracteres.';
            elseif (!$yt && !$arq) $e[] = 'Trailer inválido. Cole um link do YouTube (ex.: https://www.youtube.com/watch?v=...) ou de um vídeo .mp4/.webm.';
        }
        // campos opcionais exibidos na página do jogo: desenvolvedora, lançamento, classificação e jogadores
        $dev = trim($p['desenvolvedora'] ?? '');
        if (mb_strlen($dev) > 120) $e[] = 'A desenvolvedora pode ter no máximo 120 caracteres.';
        $jog = trim($p['jogadores'] ?? '');
        if (mb_strlen($jog) > 60) $e[] = 'O campo jogadores pode ter no máximo 60 caracteres.';
        // plataformas: caixas marcadas (plataforma[]) + campo "outra"; guardadas separadas por vírgula (ex.: "PS5, PC")
        $marcadas = $p['plataforma'] ?? [];
        if (!is_array($marcadas)) $marcadas = [$marcadas];
        $listaPlat = [];
        foreach (array_merge($marcadas, preg_split('/[,;|]+/', (string)($p['plataforma_outra'] ?? ''))) as $x) {
            $x = trim((string)$x);
            if ($x !== '') $listaPlat[mb_strtolower($x)] = $x;     // chave em minúsculas: não repete a mesma plataforma
        }
        $plataforma = implode(', ', $listaPlat);
        if (mb_strlen($plataforma) > 80) $e[] = 'As plataformas passam de 80 caracteres no total. Desmarque alguma ou encurte o nome.';
        $clas = trim($p['classificacao'] ?? '');
        if (mb_strlen($clas) > 4) $e[] = 'Classificação inválida.';
        $lanc = trim($p['lancamento'] ?? ''); $lancDb = null;
        if ($lanc !== '') {
            foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $fmt) {
                $dt = DateTime::createFromFormat('!' . $fmt, $lanc);
                if ($dt && $dt->format($fmt) === $lanc) { $lancDb = $dt->format('Y-m-d H:i:s'); break; }
            }
            if ($lancDb === null) $e[] = 'Data de lançamento inválida.';
        }
        return [[
            'titulo'    => $titulo,
            'trailer'   => $trailer,
            'descricao' => trim($p['descricao'] ?? ''),
            'preco'     => is_numeric($raw) ? round((float)$raw, 2) : 0,
            'estoque'   => $estoque === false ? 0 : $estoque,
            'categoria' => trim($p['categoria'] ?? ''),
            'desenvolvedora' => $dev,
            'lancamento'     => $lancDb,
            'classificacao'  => $clas,
            'jogadores'      => $jog,
            'plataforma'     => $plataforma,
        ], $e];
    }

    // Salva a imagem enviada em public/imagens e devolve o nome do arquivo (ou null se não enviou).
    public static function salvarImagem(array $f): ?string {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Falha no envio da imagem.');
        if ($f['size'] > 3 * 1024 * 1024) throw new Exception('A imagem deve ter no máximo 3 MB.');
        $info = @getimagesize($f['tmp_name']);
        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
        if (!$ext) throw new Exception('Envie uma imagem JPG, PNG, WEBP ou GIF.');
        $dir = __DIR__ . '/../public/imagens/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $nome = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . $nome)) throw new Exception('Não foi possível salvar a imagem.');
        return $nome;
    }
    public static function removerImagem(?string $nome): void {
        if (!$nome) return;
        $arq = __DIR__ . '/../public/imagens/' . basename($nome);
        if (is_file($arq)) @unlink($arq);
    }
}
