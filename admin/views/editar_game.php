<?php
require_once '../config/auth.php';
require_once '_layout.php';
require_once '../models/games.php';
exigirAdmin();

$game = new Game();
$id = (int)($_GET['id'] ?? 0);
$g = $game->buscarPorId($id);
if (!$g) { flash('Jogo não encontrado.'); header('Location: gerenciar_games.php'); exit; }

$erros = [];
$tsLanc = !empty($g['lancamento']) ? strtotime((string)$g['lancamento']) : false;   // DATETIME do banco -> campo datetime-local
$v = [
    'titulo' => $g['titulo'], 'descricao' => $g['descricao'],
    'preco' => number_format((float)$g['preco'], 2, ',', ''),
    'estoque' => $g['estoque'], 'categoria' => $g['categoria'], 'trailer' => (string)($g['trailer'] ?? ''),
    'desenvolvedora' => (string)($g['desenvolvedora'] ?? ''),
    'lancamento'     => $tsLanc !== false ? date('Y-m-d\TH:i', $tsLanc) : '',
    'classificacao'  => (string)($g['classificacao'] ?? ''),
    'jogadores'      => (string)($g['jogadores'] ?? ''),
    'plataforma'     => (string)($g['plataforma'] ?? ''),
    'plataforma_outra' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    } else {
        $v = array_merge($v, array_intersect_key($_POST, $v));
        [$d, $erros] = Game::validar($_POST);
        if (!$erros) {
            try {
                $nova = Game::salvarImagem($_FILES['imagem'] ?? []);
                $d['imagem'] = $nova ?? $g['imagem'];
                $game->atualizar($id, $d);
                if ($nova && !empty($g['imagem']) && !$game->imagemEmUso($g['imagem'])) Game::removerImagem($g['imagem']);
                flash('Jogo atualizado com sucesso.');
                header('Location: gerenciar_games.php'); exit;
            } catch (Exception $e) { $erros[] = $e->getMessage(); }
        }
    }
}
$categorias = $game->categorias();
$imagemAtual = $g['imagem'];
$botao = 'Salvar alterações';
pagina_topo('Editar jogo', 'jogos');
?>
<h2>Editar jogo #<?= (int)$g['id'] ?></h2>

<style>
/* ===== Caixa de edição: grade de 12 colunas ===== */
.form-horizontal {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}
.form-horizontal form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 24px 24px;
    align-items: end;               /* todos os campos alinhados pela base */
    width: 100%;
    max-width: none;
    padding: 28px 32px;
    box-sizing: border-box;
}
.form-horizontal form > * { grid-column: 1 / -1; }   /* o que não for campo ocupa a linha toda */

/* cada campo: rótulo em cima, controle embaixo, preenchendo a caixa */
.form-horizontal .fg {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 0;
    min-width: 0;
}
.form-horizontal .fg label {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
}
.form-horizontal .fg input,
.form-horizontal .fg select,
.form-horizontal .fg textarea {
    width: 100%;
    box-sizing: border-box;
    margin: 0;
    padding: 10px 14px;
    border-radius: 8px;
}
.form-horizontal .fg input:not([type=file]),
.form-horizontal .fg select { height: 44px; }
.form-horizontal .fg textarea { height: 110px; min-height: 0; resize: vertical; }

/* largura de cada caixa (soma 12 por linha) */
.form-horizontal .c-titulo    { grid-column: span 4; }   /* linha 1: 4 + 3 + 3 + 2 */
.form-horizontal .c-categoria { grid-column: span 3; }
.form-horizontal .c-preco     { grid-column: span 3; }
.form-horizontal .c-estoque   { grid-column: span 2; }
.form-horizontal .c-trailer   { grid-column: 1 / -1; }
.form-horizontal .c-imagem    { grid-column: 1 / -1; }   /* linha 3: inteira */
.form-horizontal .c-descricao { grid-column: 1 / -1; }   /* linha 2: inteira */

/* trailer à esquerda e, à direita, desenvolvedora / lançamento / classificação / jogadores */
.form-horizontal .linha-trailer {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}
.form-horizontal .linha-trailer .c-trailer { grid-column: auto; }
.form-horizontal .extras-trailer {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}
.form-horizontal .extras-trailer .fg { grid-column: auto; }
.form-horizontal .extras-trailer .fg select { height: 44px; }
.form-horizontal .extras-trailer .c-desenvolvedora { grid-column: 1 / -1; }   /* nome da empresa ocupa a linha toda */
.form-horizontal .extras-trailer .c-plataforma { grid-column: 1 / -1; }       /* plataformas ocupam a linha toda */
.form-horizontal .c-plataforma input[name=plataforma_outra] { margin-top: 2px; }

/* imagem ao lado do campo de arquivo */
.form-horizontal .imagem-linha {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px 20px;
}
.form-horizontal .imagem-atual {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 0 0 auto;
}
.form-horizontal .imagem-atual .rotulo-atual {
    font-size: 13px;
    font-weight: 600;
}
.form-horizontal .imagem-atual img {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 10px;
}
/* botão "Escolher arquivo" menor */
.form-horizontal .imagem-linha input[type=file] {
    flex: 1 1 420px;          /* largura maior: "Nenhum arquivo escolhido" fica visível */
    width: auto;
    max-width: 560px;
    min-width: 340px;
    padding: 4px 8px;
    font-size: 12px;
    text-overflow: clip;
    white-space: nowrap;
}
.form-horizontal .imagem-linha input[type=file]::file-selector-button {
    padding: 3px 10px;
    margin-right: 10px;
    font-size: 12px;
    border-radius: 6px;
    cursor: pointer;
}

/* botão */
.form-horizontal .acoes-form { grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 4px; }

/* telas médias e pequenas */
@media (max-width: 1100px) {
    .form-horizontal .c-titulo { grid-column: 1 / -1; }
    .form-horizontal .c-categoria,
    .form-horizontal .c-preco,
    .form-horizontal .c-estoque { grid-column: span 4; }
    .form-horizontal .linha-trailer { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 560px) {
    .form-horizontal form { padding: 18px; gap: 16px; }
    .form-horizontal .fg { grid-column: 1 / -1 !important; }
    .form-horizontal .extras-trailer { grid-template-columns: minmax(0, 1fr); }
    .form-horizontal .imagem-linha input[type=file] { min-width: 0; width: 100%; max-width: 100%; flex-basis: 100%; }
}

/* ===== Rodapé menor ===== */
footer, .footer, .rodape {
    padding: 8px 16px !important;
    margin-top: 12px !important;
    min-height: 0 !important;
    font-size: 12px;
    line-height: 1.3;
}
footer > *, .footer > *, .rodape > * { margin-top: 0 !important; margin-bottom: 0 !important; }
</style>

<div class="form-horizontal">
    <?php include '_form_game.php'; ?>
</div>

<script>
(function () {
    var form = document.querySelector('.form-horizontal form');
    if (!form) return;

    var nomes = ['titulo', 'categoria', 'preco', 'estoque', 'descricao', 'trailer', 'desenvolvedora', 'plataforma', 'lancamento', 'classificacao', 'jogadores', 'imagem'];
    var ctrl = {};
    nomes.forEach(function (n) { ctrl[n] = form.querySelector('[name="' + n + '"]'); });
    ctrl.plataforma = form.querySelector('[name="plataforma[]"]');   // grupo de caixas de seleção

    // 0) Remove o texto "Imagem (JPG, PNG, WEBP ou GIF, até 3 MB) — deixe vazio para manter a atual"
    var padroes = [
        /^\s*Imagem atual\s*:?\s*$/i,
        /Imagem\s*\(JPG[^)]*\)/gi,
        /[—–-]\s*deixe vazio para manter a atual\.?/gi,
        /deixe vazio para manter a atual\.?/gi
    ];
    var tw0 = document.createTreeWalker(form, NodeFilter.SHOW_TEXT);
    var t0, textos = [];
    while ((t0 = tw0.nextNode())) textos.push(t0);
    textos.forEach(function (t) {
        var v = t.nodeValue;
        padroes.forEach(function (re) { v = v.replace(re, ''); });
        if (v !== t.nodeValue) {
            t.nodeValue = v;
            var el = t.parentElement;
            while (el && el !== form && el.children.length === 0 && !el.textContent.trim()) {
                var pai = el.parentElement; el.remove(); el = pai;
            }
        }
    });

    // 1) "Imagem atual" acima da capa, e a capa ao lado do campo de arquivo
    var file = ctrl.imagem;
    if (file) {
        var img = form.querySelector('img');
        var linha = document.createElement('div');
        linha.className = 'imagem-linha';
        file.parentNode.insertBefore(linha, file);
        if (img) {
            var atual = document.createElement('div');
            atual.className = 'imagem-atual';
            var rot = document.createElement('span');
            rot.className = 'rotulo-atual';
            rot.textContent = 'Imagem atual';
            atual.appendChild(rot);
            atual.appendChild(img);
            linha.appendChild(atual);
        }
        linha.appendChild(file);
        if (!file.getAttribute('aria-label')) file.setAttribute('aria-label', 'Escolher nova imagem');
    }

    // 2) Descobre a "caixa" de cada campo (rótulo + controle)
    function caixa(n) {
        var el = ctrl[n];
        if (!el) return null;
        var w = el;
        while (w.parentElement && w.parentElement !== form) {
            var p = w.parentElement;
            var outros = nomes.some(function (m) { return m !== n && ctrl[m] && p.contains(ctrl[m]); });
            if (outros) break;
            w = p;
        }
        if (w === el && el.parentElement === form) {          // campo solto: cria a caixa
            var d = document.createElement('div');
            var rot = el.previousElementSibling;
            form.insertBefore(d, rot && rot.tagName === 'LABEL' ? rot : el);
            if (rot && rot.tagName === 'LABEL') d.appendChild(rot);
            d.appendChild(el);
            w = d;
        }
        return w;
    }

    // 3) Botão de salvar
    var btn = form.querySelector('button[type=submit], input[type=submit], button:not([type])');
    var btnBox = null;
    if (btn) {
        btnBox = btn;
        while (btnBox.parentElement && btnBox.parentElement !== form) btnBox = btnBox.parentElement;
        btnBox.classList.add('acoes-form');
    }

    // 4) Aplica classes e reordena: título, categoria, preço, estoque, descrição, imagem, botão
    nomes.forEach(function (n) {
        var w = caixa(n);
        if (!w) return;
        w.classList.add('fg', 'c-' + n);
        form.insertBefore(w, btnBox || null);
    });

    // 4b) Trailer à esquerda; desenvolvedora, lançamento, classificação e jogadores à direita
    var cTrailer = form.querySelector('.c-trailer');
    var extras = ['desenvolvedora', 'plataforma', 'lancamento', 'classificacao', 'jogadores']
        .map(function (n) { return form.querySelector('.c-' + n); })
        .filter(Boolean);
    if (cTrailer && extras.length) {
        var linhaT = document.createElement('div'); linhaT.className = 'linha-trailer';
        var dirT = document.createElement('div');   dirT.className = 'extras-trailer';
        form.insertBefore(linhaT, cTrailer);
        linhaT.appendChild(cTrailer);
        linhaT.appendChild(dirT);
        extras.forEach(function (e) { dirT.appendChild(e); });
    }

    // 5) Remove "Pague com cartão, Pix ou boleto" do rodapé
    var re = /Pague com cart[ãa]o,?\s*Pix\s*ou\s*boleto\.?/gi;
    var tw = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var n, lista = [];
    while ((n = tw.nextNode())) { if (re.test(n.nodeValue)) lista.push(n); re.lastIndex = 0; }
    lista.forEach(function (t) {
        t.nodeValue = t.nodeValue.replace(re, '');
        var el = t.parentElement;
        while (el && el !== document.body && el.children.length === 0 && !el.textContent.trim()) {
            var pai = el.parentElement; el.remove(); el = pai;
        }
    });
})();
</script>
<?php pagina_fim();
