-- =====================================================================
--  Loja de Jogos (Lucas_Oliveira) - SCRIPT COMPLETO DO BANCO DE DADOS
--  MySQL / MariaDB (XAMPP) - PHP 8 + PDO
--
--  Como usar: phpMyAdmin > aba "Importar" > escolha este arquivo > Executar.
--  ATENÇÃO: o script RECRIA as tabelas (DROP TABLE IF EXISTS). Se você já
--  tem dados que quer manter, use sql/atualizar_banco_existente.sql.
--
--  Usuários de teste:
--    admin    -> admin@loja.com    / Admin@123
--    cliente  -> maria@email.com   / Cliente@123
--    cliente  -> joao@email.com    / Cliente@123
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lucas_oliveira
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lucas_oliveira;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS recuperacao_senha;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS games;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1) usuarios: quem faz login (administradores e clientes)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id        INT NOT NULL AUTO_INCREMENT,
  nome      VARCHAR(100) NOT NULL,
  email     VARCHAR(150) NOT NULL,
  senha     VARCHAR(255) NOT NULL,                       -- password_hash()
  tipo      ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  usuario   VARCHAR(40)  NULL,
  rua       VARCHAR(150) NULL,
  numero    VARCHAR(10)  NULL,
  cidade    VARCHAR(100) NULL,
  estado    CHAR(2)      NULL,
  pais      VARCHAR(60)  NOT NULL DEFAULT 'Brasil',
  cep       CHAR(8)      NULL,
  idade     TINYINT UNSIGNED NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) clientes: dados de contato de cada usuario do tipo cliente (1 para 1)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id              INT NOT NULL AUTO_INCREMENT,
  usuario_id      INT NOT NULL,
  telefone        VARCHAR(20) NOT NULL DEFAULT '',
  cpf             CHAR(11) NULL,
  data_nascimento DATE NULL,
  criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_usuario (usuario_id),
  UNIQUE KEY uq_clientes_cpf (cpf),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) categorias dos jogos
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id        INT NOT NULL AUTO_INCREMENT,
  nome      VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) games (produtos da loja)
-- ---------------------------------------------------------------------
CREATE TABLE games (
  id          INT NOT NULL AUTO_INCREMENT,
  titulo      VARCHAR(200) NOT NULL,
  descricao   TEXT NULL,
  preco       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estoque     INT NOT NULL DEFAULT 0,
  categoria_id INT NULL,
  imagem      VARCHAR(255) NULL,                         -- arquivo em admin/public/imagens
  trailer     VARCHAR(255) NULL,                         -- link do YouTube ou arquivo .mp4/.webm (opcional)
  -- campos opcionais exibidos na página de detalhes do jogo
  capa                   VARCHAR(255) NULL,
  desenvolvedora         VARCHAR(120) NULL,
  plataforma             VARCHAR(80)  NULL,
  edicao                 VARCHAR(60)  NULL,
  nota                   DECIMAL(3,2) NULL,
  avaliacoes             INT NULL,
  jogadores              VARCHAR(60)  NULL,
  classificacao          VARCHAR(4)   NULL,
  conteudo_classificacao VARCHAR(255) NULL,
  versao                 VARCHAR(80)  NULL,
  compras_jogo           TINYINT(1) NOT NULL DEFAULT 0,
  recursos               TEXT NULL,
  lancamento             DATETIME NULL,
  aviso_compra           VARCHAR(500) NULL,
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_games_categoria (categoria_id),
  KEY idx_games_titulo (titulo),
  CONSTRAINT fk_games_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) pedidos (um pedido = um jogo; titulo_jogo guarda o historico)
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id              INT NOT NULL AUTO_INCREMENT,
  usuario_id      INT NOT NULL,
  game_id         INT NULL,
  titulo_jogo     VARCHAR(200) NOT NULL,
  quantidade      INT NOT NULL,
  valor_total     DECIMAL(10,2) NOT NULL,
  forma_pagamento ENUM('cartao','pix','boleto') NOT NULL,
  status          ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelado_em    DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (usuario_id),
  KEY idx_pedidos_game (game_id),
  KEY idx_pedidos_status (status),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pedidos_game FOREIGN KEY (game_id)
    REFERENCES games (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) favoritos (lista de desejos)
-- ---------------------------------------------------------------------
CREATE TABLE favoritos (
  usuario_id INT NOT NULL,
  game_id    INT NOT NULL,
  criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, game_id),
  KEY idx_favoritos_game (game_id),
  CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favoritos_game FOREIGN KEY (game_id)
    REFERENCES games (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7) recuperacao_senha (links temporarios de redefinicao)
-- ---------------------------------------------------------------------
CREATE TABLE recuperacao_senha (
  id         INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em  DATETIME NOT NULL,
  criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rec_token (token_hash),
  KEY idx_rec_usuario (usuario_id),
  CONSTRAINT fk_rec_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DADOS INICIAIS PARA TESTE
-- =====================================================================
INSERT INTO usuarios (id, nome, email, senha, tipo, cidade, estado) VALUES
  (1, 'Administrador',  'admin@loja.com',  '$2y$10$/heM2hPBosZG628ggW3eD.UWPqUJ0qe/C5thV.McSFWHkPkqENo0S',   'admin',   'Montes Claros', 'MG'),
  (2, 'Maria Souza',    'maria@email.com', '$2y$10$gG42KTuA9LPAtvBJhjHWSubKgisqNBMCG2Uw3jBrCSEUs2yPxVgvO', 'cliente', 'Belo Horizonte', 'MG'),
  (3, 'João Pereira',   'joao@email.com',  '$2y$10$gG42KTuA9LPAtvBJhjHWSubKgisqNBMCG2Uw3jBrCSEUs2yPxVgvO', 'cliente', 'Montes Claros', 'MG');

INSERT INTO clientes (usuario_id, telefone, cpf, data_nascimento) VALUES
  (2, '31988887777', NULL, '1995-04-12'),
  (3, '38999991111', NULL, '1990-09-30');

INSERT INTO categorias (id, nome, descricao) VALUES
  (1, 'Ação e Aventura', 'Combate, exploração e narrativa intensa'),
  (2, 'RPG',             'Jogos de interpretação de papéis e evolução de personagem'),
  (3, 'Souls-like',      'Desafio alto, combate preciso e mundos sombrios'),
  (4, 'Terror',          'Sobrevivência e suspense'),
  (5, 'Corrida',         'Simuladores e jogos de velocidade');

INSERT INTO games (id, titulo, descricao, preco, estoque, categoria_id, imagem) VALUES
  (1,  'Elden Ring',                 'Mundo aberto sombrio criado por FromSoftware e George R. R. Martin.', 199.90, 15, 3, 'Elden Ring.jpeg'),
  (2,  'God of War',                 'Kratos e Atreus em uma jornada pelos reinos nórdicos.',                149.90, 12, 1, 'God Of War.jpg'),
  (3,  'Bloodborne',                 'Caçada em Yharnam: combate rápido e atmosfera gótica.',                119.90,  8, 3, 'Bloodborne.jpg'),
  (4,  'Dark Souls III',             'O capítulo final da lendária série de desafios.',                      129.90, 10, 3, 'Dark Souls III.jpg'),
  (5,  'Horizon Zero Dawn',          'Aloy enfrenta máquinas gigantes em um mundo pós-apocalíptico.',         99.90, 20, 1, 'Horizon Zero Dawn.jpg'),
  (6,  'Horizon Forbidden West',     'Aloy segue para o Oeste Proibido atrás de uma nova ameaça.',           179.90,  9, 1, 'Horizon Forbiden West.jpg'),
  (7,  'Gran Turismo 7',             'O simulador de corrida mais completo do PlayStation.',                 189.90,  6, 5, 'Gran Turismo 7.jpg'),
  (8,  'Resident Evil Requiem',      'O retorno do terror de sobrevivência mais famoso dos games.',          249.90, 14, 4, 'Resident Evil Requiem.jpg'),
  (9,  'Black Myth: Wukong',         'RPG de ação inspirado na lenda chinesa do Rei Macaco.',                229.90,  5, 2, 'Black Myth Wukong.jpeg'),
  (10, 'The Last of Us Part II',     'Vingança e redenção em um mundo devastado.',                           139.90,  0, 1, 'The Last of Us Part II.jpg'),
  (11, 'Hollow Knight: Silksong',    'Metroidvania de ação e exploração em Pharloom.',                        59.90, 30, 1, 'Hollow Knight  Silksong.jpg'),
  (12, 'Lies of P',                  'Releitura sombria de Pinóquio em estilo Souls-like.',                  159.90,  7, 3, 'Lies Of P..jpg');

-- pedidos de exemplo (o estoque acima já considera as unidades vendidas)
INSERT INTO pedidos (usuario_id, game_id, titulo_jogo, quantidade, valor_total, forma_pagamento, status, criado_em, cancelado_em) VALUES
  (2, 1,  'Elden Ring',            1, 199.90, 'cartao', 'pago',      DATE_SUB(NOW(), INTERVAL 40 DAY), NULL),
  (2, 2,  'God of War',            1, 149.90, 'pix',    'pago',      DATE_SUB(NOW(), INTERVAL 33 DAY), NULL),
  (3, 5,  'Horizon Zero Dawn',     2, 199.80, 'cartao', 'pago',      DATE_SUB(NOW(), INTERVAL 21 DAY), NULL),
  (3, 8,  'Resident Evil Requiem', 1, 249.90, 'cartao', 'pago',      DATE_SUB(NOW(), INTERVAL 12 DAY), NULL),
  (2, 9,  'Black Myth: Wukong',    1, 229.90, 'boleto', 'pendente',  DATE_SUB(NOW(), INTERVAL 3 DAY),  NULL),
  (3, 3,  'Bloodborne',            1, 119.90, 'pix',    'cancelado', DATE_SUB(NOW(), INTERVAL 8 DAY),  DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (2, 11, 'Hollow Knight: Silksong', 3, 179.70, 'pix', 'pago',      DATE_SUB(NOW(), INTERVAL 2 DAY),  NULL);

INSERT INTO favoritos (usuario_id, game_id) VALUES (2, 6), (2, 12), (3, 7);
