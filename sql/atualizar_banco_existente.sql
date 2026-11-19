-- =====================================================================
--  ATUALIZAR UM BANCO JÁ EXISTENTE (mantém seus dados atuais)
--  Use somente se o banco "lucas_oliveira" já tem usuarios/games/pedidos.
--  Faça um backup antes (phpMyAdmin > Exportar). Rode UMA vez.
--  Se preferir começar do zero com dados de teste, use sql/script.sql.
-- =====================================================================
USE lucas_oliveira;

-- 1) padroniza o tipo dos ids e o motor (necessário para criar as chaves estrangeiras)
ALTER TABLE usuarios ENGINE=InnoDB;
ALTER TABLE games    ENGINE=InnoDB;
ALTER TABLE pedidos  ENGINE=InnoDB;
ALTER TABLE usuarios MODIFY id INT NOT NULL AUTO_INCREMENT;
ALTER TABLE games    MODIFY id INT NOT NULL AUTO_INCREMENT;

-- 2) colunas de endereço que antes eram criadas pelo PHP (conta.php)
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS usuario VARCHAR(40)  NULL,
  ADD COLUMN IF NOT EXISTS rua     VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS numero  VARCHAR(10)  NULL,
  ADD COLUMN IF NOT EXISTS cidade  VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS estado  CHAR(2)      NULL,
  ADD COLUMN IF NOT EXISTS pais    VARCHAR(60)  NOT NULL DEFAULT 'Brasil',
  ADD COLUMN IF NOT EXISTS cep     CHAR(8)      NULL,
  ADD COLUMN IF NOT EXISTS idade   TINYINT UNSIGNED NULL;
ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_email (email);   -- falha se houver e-mails repetidos: corrija-os antes

-- 3) categorias (a partir do texto que já existia em games.categoria)
CREATE TABLE IF NOT EXISTS categorias (
  id        INT NOT NULL AUTO_INCREMENT,
  nome      VARCHAR(100) NOT NULL,
  descricao VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categorias (nome)
  SELECT DISTINCT TRIM(categoria) FROM games WHERE categoria IS NOT NULL AND TRIM(categoria) <> '';

ALTER TABLE games ADD COLUMN categoria_id INT NULL;
UPDATE games g JOIN categorias c ON c.nome = TRIM(g.categoria) SET g.categoria_id = c.id;
ALTER TABLE games DROP COLUMN categoria;
ALTER TABLE games ADD KEY idx_games_categoria (categoria_id);

-- trailer do jogo (link do YouTube ou arquivo .mp4/.webm); ignora se a coluna já existir
ALTER TABLE games ADD COLUMN IF NOT EXISTS trailer VARCHAR(255) NULL AFTER imagem;
ALTER TABLE games ADD CONSTRAINT fk_games_categoria FOREIGN KEY (categoria_id)
  REFERENCES categorias (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4) clientes (um registro para cada usuario do tipo cliente)
CREATE TABLE IF NOT EXISTS clientes (
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
INSERT IGNORE INTO clientes (usuario_id, telefone)
  SELECT id, '' FROM usuarios WHERE tipo = 'cliente';

-- 5) pedidos: chaves estrangeiras (remove antes os registros órfãos)
ALTER TABLE pedidos MODIFY game_id INT NULL;
UPDATE pedidos SET game_id = NULL WHERE game_id IS NOT NULL AND game_id NOT IN (SELECT id FROM games);
DELETE FROM pedidos WHERE usuario_id NOT IN (SELECT id FROM usuarios);
ALTER TABLE pedidos ADD KEY idx_pedidos_game (game_id);
ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
  REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_game FOREIGN KEY (game_id)
  REFERENCES games (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- 6) favoritos
CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id INT NOT NULL,
  game_id    INT NOT NULL,
  criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, game_id),
  KEY idx_favoritos_game (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELETE FROM favoritos WHERE usuario_id NOT IN (SELECT id FROM usuarios) OR game_id NOT IN (SELECT id FROM games);
ALTER TABLE favoritos ADD CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id)
  REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE favoritos ADD CONSTRAINT fk_favoritos_game FOREIGN KEY (game_id)
  REFERENCES games (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- 7) recuperacao_senha
CREATE TABLE IF NOT EXISTS recuperacao_senha (
  id         INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira_em  DATETIME NOT NULL,
  criado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rec_token (token_hash),
  KEY idx_rec_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DELETE FROM recuperacao_senha WHERE usuario_id NOT IN (SELECT id FROM usuarios);
ALTER TABLE recuperacao_senha ADD CONSTRAINT fk_rec_usuario FOREIGN KEY (usuario_id)
  REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE;
