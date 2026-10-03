SET NAMES utf8mb4;
-- Executar uma vez em bancos existentes para garantir texto UTF-8 completo.
ALTER DATABASE loja CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE usuario CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE endereco CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE categoria CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE produto CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE pedido CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE item_pedido CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE carrinho CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
ALTER TABLE item_carrinho CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

-- Os dados do seed antigo foram gravados como UTF-8 interpretado duas vezes.
-- A conversão abaixo desfaz somente essa camada extra e preserva caracteres ASCII.
UPDATE usuario SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE HEX(nome) LIKE '%C383%' OR HEX(nome) LIKE '%C382%';
UPDATE endereco SET
  destinatario = CONVERT(CAST(CONVERT(destinatario USING latin1) AS BINARY) USING utf8mb4),
  logradouro = CONVERT(CAST(CONVERT(logradouro USING latin1) AS BINARY) USING utf8mb4),
  complemento = IF(complemento IS NULL, NULL, CONVERT(CAST(CONVERT(complemento USING latin1) AS BINARY) USING utf8mb4)),
  bairro = CONVERT(CAST(CONVERT(bairro USING latin1) AS BINARY) USING utf8mb4),
  cidade = CONVERT(CAST(CONVERT(cidade USING latin1) AS BINARY) USING utf8mb4)
WHERE HEX(destinatario) LIKE '%C383%' OR HEX(destinatario) LIKE '%C382%'
   OR HEX(logradouro) LIKE '%C383%' OR HEX(logradouro) LIKE '%C382%'
   OR HEX(complemento) LIKE '%C383%' OR HEX(complemento) LIKE '%C382%'
   OR HEX(bairro) LIKE '%C383%' OR HEX(bairro) LIKE '%C382%'
   OR HEX(cidade) LIKE '%C383%' OR HEX(cidade) LIKE '%C382%';
UPDATE categoria SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE HEX(nome) LIKE '%C383%' OR HEX(nome) LIKE '%C382%';
UPDATE produto SET
  nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4),
  descricao = IF(descricao IS NULL, NULL, CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4))
WHERE HEX(nome) LIKE '%C383%' OR HEX(nome) LIKE '%C382%'
   OR HEX(descricao) LIKE '%C383%' OR HEX(descricao) LIKE '%C382%';
