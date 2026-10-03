SET NAMES utf8mb4;
USE loja;

-- =====================================================
-- SEED - E-commerce de livros
-- Execute em tabelas vazias (os IDs são explícitos).
-- Senha de todos os usuários: senha123 (hash bcrypt)
-- =====================================================

-- =========================
-- USUARIO
-- =========================
INSERT INTO usuario (id, nome, email, senha_hash, papel, criado_em) VALUES
(1, 'Administrador',    'admin@livraria.com',    '$2b$10$2tIJp.A1unjoR0gQAqh8FOEwxj/iWX1XJbfiIA16d9Ugk3gDgPAKi', 'ADMIN',   '2026-01-10 09:00:00'),
(2, 'Mariana Souza',    'mariana@email.com',     '$2b$10$2tIJp.A1unjoR0gQAqh8FOEwxj/iWX1XJbfiIA16d9Ugk3gDgPAKi', 'CLIENTE', '2026-02-03 14:22:10'),
(3, 'Carlos Oliveira',  'carlos@email.com',      '$2b$10$2tIJp.A1unjoR0gQAqh8FOEwxj/iWX1XJbfiIA16d9Ugk3gDgPAKi', 'CLIENTE', '2026-02-15 10:05:41'),
(4, 'Fernanda Lima',    'fernanda@email.com',    '$2b$10$2tIJp.A1unjoR0gQAqh8FOEwxj/iWX1XJbfiIA16d9Ugk3gDgPAKi', 'CLIENTE', '2026-03-01 18:47:03'),
(5, 'João Pereira',     'joao@email.com',        '$2b$10$2tIJp.A1unjoR0gQAqh8FOEwxj/iWX1XJbfiIA16d9Ugk3gDgPAKi', 'CLIENTE', '2026-03-20 08:31:55');

-- =========================
-- ENDERECO
-- =========================
INSERT INTO endereco (id, usuario_id, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, padrao, ativo) VALUES
(1, 2, 'Mariana Souza',   '01310100', 'Avenida Paulista',        '1578', 'Apto 42',  'Bela Vista',  'São Paulo',      'SP', TRUE,  TRUE),
(2, 2, 'Mariana Souza',   '04538132', 'Avenida Brigadeiro Faria Lima', '3477', 'Sala 12', 'Itaim Bibi', 'São Paulo',   'SP', FALSE, TRUE),
(3, 3, 'Carlos Oliveira', '80020290', 'Rua XV de Novembro',      '450',  NULL,       'Centro',      'Curitiba',       'PR', TRUE,  TRUE),
(4, 4, 'Fernanda Lima',   '30140071', 'Avenida Afonso Pena',     '1200', 'Bloco B',  'Centro',      'Belo Horizonte', 'MG', TRUE,  TRUE),
(5, 5, 'João Pereira',    '90010150', 'Rua dos Andradas',        '89',   'Casa',     'Centro Histórico', 'Porto Alegre', 'RS', TRUE, TRUE),
(6, 4, 'Fernanda Lima',   '22041001', 'Rua Barata Ribeiro',      '210',  'Apto 803', 'Copacabana',  'Rio de Janeiro', 'RJ', FALSE, FALSE);

-- =========================
-- CATEGORIA
-- =========================
INSERT INTO categoria (id, nome, slug) VALUES
(1, 'Ficção Científica',    'ficcao-cientifica'),
(2, 'Romance',              'romance'),
(3, 'Fantasia',             'fantasia'),
(4, 'Tecnologia',           'tecnologia'),
(5, 'Negócios e Finanças',  'negocios-financas'),
(6, 'Infantojuvenil',       'infantojuvenil');

-- =========================
-- PRODUTO
-- =========================
INSERT INTO produto (id, categoria_id, nome, descricao, preco, estoque, imagem_url, ativo, criado_em) VALUES
(1,  1, 'Duna',
     'Clássico de Frank Herbert sobre poder, religião e ecologia no planeta desértico Arrakis.',
     79.90, 25, NULL, TRUE, '2026-01-15 10:00:00'),
(2,  1, 'Solaris',
     'Ficção científica de Stanislaw Lem sobre memória, consciência e os limites do conhecimento humano.',
     59.90, 30, NULL, TRUE, '2026-01-15 10:05:00'),
(3,  1, 'Neuromancer',
     'Romance cyberpunk de William Gibson sobre inteligência artificial, tecnologia e identidade.',
     69.90, 22, NULL, TRUE, '2026-01-15 10:10:00'),

(4,  2, 'A Hora da Estrela',
     'Última obra publicada em vida por Clarice Lispector, a história da datilógrafa Macabéa.',
     29.90, 32, NULL, TRUE, '2026-01-16 09:00:00'),
(5,  2, 'Dom Casmurro',
     'Romance de Machado de Assis narrado por Bentinho, marcado pela dúvida sobre Capitu.',
     24.90, 60, NULL, TRUE, '2026-01-16 09:05:00'),
(6,  2, 'Torto Arado',
     'Romance de Itamar Vieira Junior sobre memória, terra, ancestralidade e resistência no sertão.',
     49.90, 45, NULL, TRUE, '2026-01-16 09:10:00'),

(7,  3, 'O Nome do Vento',
     'A formação de Kvothe em uma fantasia sobre música, magia, amizade e lendas.',
     64.90, 30, NULL, TRUE, '2026-01-17 11:00:00'),
(8,  3, 'A Bússola de Ouro',
     'Uma aventura fantástica sobre mundos paralelos, coragem e a descoberta de grandes segredos.',
     49.90, 35, NULL, TRUE, '2026-01-17 11:05:00'),
(9,  3, 'Stardust',
     'Uma jornada encantada além do muro da aldeia, escrita por Neil Gaiman.',
     59.90, 22, NULL, TRUE, '2026-01-17 11:10:00'),

(10, 4, 'Código Limpo',
     'Habilidades práticas do Agile Software Craftsmanship, por Robert C. Martin.',
     89.90, 15, NULL, TRUE, '2026-01-18 15:00:00'),
(11, 4, 'O Programador Pragmático',
     'Da journeyman ao mestre: dicas e práticas para se tornar um desenvolvedor melhor.',
     99.90, 12, NULL, TRUE, '2026-01-18 15:05:00'),
(12, 4, 'Entendendo Algoritmos',
     'Guia ilustrado para programadores e curiosos, por Aditya Bhargava.',
     74.90, 20, NULL, TRUE, '2026-01-18 15:10:00'),

(13, 5, 'Pai Rico, Pai Pobre',
     'Reflexões populares sobre educação financeira, mentalidade e construção de patrimônio.',
     44.90, 50, NULL, TRUE, '2026-01-19 10:00:00'),
(14, 5, 'O Homem Mais Rico da Babilônia',
     'Lições atemporais sobre economia e enriquecimento, de George S. Clason.',
     29.90, 55, NULL, TRUE, '2026-01-19 10:05:00'),
(15, 5, 'Hábitos Atômicos',
     'Um método fácil e comprovado de criar bons hábitos e se livrar dos maus, de James Clear.',
     59.90, 38, NULL, TRUE, '2026-01-19 10:10:00'),

(16, 6, 'O Pequeno Príncipe',
     'A fábula de Antoine de Saint-Exupéry sobre amizade, amor e o essencial invisível aos olhos.',
     22.90, 70, NULL, TRUE, '2026-01-20 09:00:00'),
(17, 6, 'Matilda',
     'Uma menina brilhante descobre sua força em uma história sobre leitura, coragem e imaginação.',
     42.90, 48, NULL, TRUE, '2026-01-20 09:05:00'),
(18, 6, 'Coraline',
     'Uma aventura sombria e fascinante sobre curiosidade, coragem e a importância de reconhecer o real.',
     34.90, 20, NULL, TRUE, '2026-01-20 09:10:00');

-- =========================
-- PEDIDO
-- (totais = soma de quantidade * preco_unitario dos itens)
-- =========================
INSERT INTO pedido (id, usuario_id, endereco_id, status, total, criado_em, pago_em) VALUES
(1, 2, 1, 'ENTREGUE',             154.70, '2026-02-10 11:20:00', '2026-02-10 11:25:30'),
(2, 3, 3, 'ENVIADO',              189.80, '2026-03-05 16:40:12', '2026-03-05 16:45:00'),
(3, 4, 4, 'PAGO',                 104.80, '2026-03-28 09:15:47', '2026-03-28 09:18:02'),
(4, 2, 2, 'AGUARDANDO_PAGAMENTO',  88.70, '2026-04-02 20:05:33', NULL),
(5, 5, 5, 'CANCELADO',             59.90, '2026-04-03 13:50:00', NULL),
(6, 3, 3, 'ENTREGUE',              82.70, '2026-02-20 19:30:21', '2026-02-20 19:33:10');

-- =========================
-- ITEM_PEDIDO
-- (pedido 1: Duna foi comprado a R$ 74,90, preço da época)
-- =========================
INSERT INTO item_pedido (id, pedido_id, produto_id, quantidade, preco_unitario) VALUES
(1,  1, 1,  1, 74.90),
(2,  1, 3,  2, 39.90),

(3,  2, 10, 1, 89.90),
(4,  2, 11, 1, 99.90),

(5,  3, 15, 1, 59.90),
(6,  3, 13, 1, 44.90),

(7,  4, 17, 1, 42.90),
(8,  4, 16, 2, 22.90),

(9,  5, 9,  1, 59.90),

(10, 6, 4,  1, 24.90),
(11, 6, 6,  1, 27.90),
(12, 6, 5,  1, 29.90);

-- =========================
-- CARRINHO
-- =========================
INSERT INTO carrinho (id, usuario_id, token, criado_em) VALUES
(1, 2,    '6f1c2a9e-3b7d-4c55-9a10-1d2e3f4a5b6c', '2026-04-05 10:12:00'),
(2, 4,    'a3d8e7b2-91c4-4f6a-8b3e-7c5d2e1f0a9b', '2026-04-06 21:03:45'),
(3, NULL, 'c9b4f1e0-5a2d-4e8c-b7a3-3f6d9e2c1b8a', '2026-04-07 15:44:10');

-- =========================
-- ITEM_CARRINHO
-- =========================
INSERT INTO item_carrinho (id, carrinho_id, produto_id, quantidade) VALUES
(1, 1, 7,  1),
(2, 1, 8,  1),
(3, 2, 12, 1),
(4, 2, 14, 2),
(5, 3, 2,  1),
(6, 3, 3,  1);
