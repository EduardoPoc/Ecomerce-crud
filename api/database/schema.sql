CREATE DATABASE IF NOT EXISTS loja
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE loja;

-- =========================
-- USUARIO
-- =========================
CREATE TABLE usuario (
  id          BIGINT       NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(150) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  senha_hash  VARCHAR(255) NOT NULL,
  papel       VARCHAR(20)  NOT NULL DEFAULT 'CLIENTE',
  criado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuario_email (email),
  CONSTRAINT ck_usuario_papel CHECK (papel IN ('CLIENTE', 'ADMIN'))
) ENGINE=InnoDB;

-- =========================
-- ENDERECO
-- =========================
CREATE TABLE endereco (
  id            BIGINT       NOT NULL AUTO_INCREMENT,
  usuario_id    BIGINT       NOT NULL,
  destinatario  VARCHAR(150) NOT NULL,
  cep           CHAR(8)      NOT NULL,
  logradouro    VARCHAR(200) NOT NULL,
  numero        VARCHAR(20)  NOT NULL,
  complemento   VARCHAR(100) NULL,
  bairro        VARCHAR(100) NOT NULL,
  cidade        VARCHAR(100) NOT NULL,
  uf            CHAR(2)      NOT NULL,
  padrao        BOOLEAN      NOT NULL DEFAULT FALSE,
  ativo         BOOLEAN      NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  KEY idx_endereco_usuario (usuario_id),
  CONSTRAINT fk_endereco_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuario (id)
) ENGINE=InnoDB;

-- =========================
-- CATEGORIA
-- =========================
CREATE TABLE categoria (
  id    BIGINT       NOT NULL AUTO_INCREMENT,
  nome  VARCHAR(100) NOT NULL,
  slug  VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_categoria_slug (slug)
) ENGINE=InnoDB;

-- =========================
-- PRODUTO
-- =========================
CREATE TABLE produto (
  id           BIGINT        NOT NULL AUTO_INCREMENT,
  categoria_id BIGINT        NOT NULL,
  nome         VARCHAR(200)  NOT NULL,
  descricao    TEXT          NULL,
  preco        DECIMAL(10,2) NOT NULL,
  estoque      INT           NOT NULL DEFAULT 0,
  imagem_url   VARCHAR(500)  NULL,
  ativo        BOOLEAN       NOT NULL DEFAULT TRUE,
  criado_em    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produto_categoria (categoria_id),
  CONSTRAINT fk_produto_categoria
    FOREIGN KEY (categoria_id) REFERENCES categoria (id)
) ENGINE=InnoDB;

-- =========================
-- PEDIDO
-- =========================
CREATE TABLE pedido (
  id           BIGINT        NOT NULL AUTO_INCREMENT,
  usuario_id   BIGINT        NOT NULL,
  endereco_id  BIGINT        NOT NULL,
  status       VARCHAR(30)   NOT NULL DEFAULT 'AGUARDANDO_PAGAMENTO',
  total        DECIMAL(10,2) NOT NULL,
  criado_em    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pago_em      TIMESTAMP     NULL,
  PRIMARY KEY (id),
  KEY idx_pedido_usuario (usuario_id),
  KEY idx_pedido_endereco (endereco_id),
  CONSTRAINT fk_pedido_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuario (id),
  CONSTRAINT fk_pedido_endereco
    FOREIGN KEY (endereco_id) REFERENCES endereco (id),
  CONSTRAINT ck_pedido_status CHECK (status IN
    ('AGUARDANDO_PAGAMENTO','PAGO','ENVIADO','ENTREGUE','CANCELADO'))
) ENGINE=InnoDB;

-- =========================
-- ITEM_PEDIDO
-- =========================
CREATE TABLE item_pedido (
  id             BIGINT        NOT NULL AUTO_INCREMENT,
  pedido_id      BIGINT        NOT NULL,
  produto_id     BIGINT        NOT NULL,
  quantidade     INT           NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL COMMENT 'preço no momento da compra',
  PRIMARY KEY (id),
  KEY idx_item_pedido_pedido (pedido_id),
  KEY idx_item_pedido_produto (produto_id),
  CONSTRAINT fk_item_pedido_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedido (id),
  CONSTRAINT fk_item_pedido_produto
    FOREIGN KEY (produto_id) REFERENCES produto (id),
  CONSTRAINT ck_item_pedido_qtd CHECK (quantidade > 0)
) ENGINE=InnoDB;

-- =========================
-- CARRINHO
-- =========================
CREATE TABLE carrinho (
  id          BIGINT    NOT NULL AUTO_INCREMENT,
  usuario_id  BIGINT    NULL COMMENT 'nulo para visitante',
  token       CHAR(36)  NOT NULL COMMENT 'identifica visitante',
  criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_carrinho_token (token),
  KEY idx_carrinho_usuario (usuario_id),
  CONSTRAINT fk_carrinho_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuario (id)
) ENGINE=InnoDB;

-- =========================
-- ITEM_CARRINHO
-- =========================
CREATE TABLE item_carrinho (
  id          BIGINT NOT NULL AUTO_INCREMENT,
  carrinho_id BIGINT NOT NULL,
  produto_id  BIGINT NOT NULL,
  quantidade  INT    NOT NULL,
  PRIMARY KEY (id),
  KEY idx_item_carrinho_carrinho (carrinho_id),
  KEY idx_item_carrinho_produto (produto_id),
  CONSTRAINT fk_item_carrinho_carrinho
    FOREIGN KEY (carrinho_id) REFERENCES carrinho (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_carrinho_produto
    FOREIGN KEY (produto_id) REFERENCES produto (id),
  CONSTRAINT ck_item_carrinho_qtd CHECK (quantidade > 0)
) ENGINE=InnoDB;