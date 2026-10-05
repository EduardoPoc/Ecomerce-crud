-- Preenche imagens ausentes dos 18 produtos do catálogo sem substituir URLs já cadastradas.
USE loja;

UPDATE produto AS p
JOIN (
  SELECT mapping.id, mapping.imagem_url
  FROM (
    SELECT 1 AS id, '/uploads/livros/b40e18af590b2b6ef9825f2d87bff9e2.jpg' AS imagem_url
    UNION ALL SELECT 2, '/uploads/livros/55e1afc570d38702cb8fc92d00bc888b.jpg'
    UNION ALL SELECT 3, '/uploads/livros/c953f4d5edff1d2f1e0e8038e232c60b.jpg'
    UNION ALL SELECT 4, '/uploads/livros/27b0f2f11939c4707a6d93a8f196514b.jpg'
    UNION ALL SELECT 5, '/uploads/livros/ed95cce775fcd38470b26cb4560df61e.jpg'
    UNION ALL SELECT 6, '/uploads/livros/b0dde2b68a0c3f48ba95cfc4065d4a26.jpg'
    UNION ALL SELECT 7, '/uploads/livros/ef86003b2ac86dca9091727ca9b9f450.jpg'
    UNION ALL SELECT 8, '/uploads/livros/f487ce6d2dea8673d13df7b05b19835b.jpg'
    UNION ALL SELECT 9, '/uploads/livros/f3ed790e766da7ba80c4811b307819b5.jpg'
    UNION ALL SELECT 10, '/uploads/livros/6369cfcd22a9fcdea4f481595cbc2061.jpg'
    UNION ALL SELECT 11, '/uploads/livros/aec45e8b8ccc8329b8abb4f82771939c.jpg'
    UNION ALL SELECT 12, '/uploads/livros/30e6a757de9805b54c7c338ed5a95a48.jpg'
    UNION ALL SELECT 13, '/uploads/livros/219369f0d0190a08d67a361174edc1ad.jpg'
    UNION ALL SELECT 14, '/uploads/livros/a1e1fd665cdb1e979e4a1d791a0096db.jpg'
    UNION ALL SELECT 15, '/uploads/livros/435999bfff06eec9c6059d78d700ad4b.jpg'
    UNION ALL SELECT 16, '/uploads/livros/807c1925a914d9d0b2985d580af44125.jpg'
    UNION ALL SELECT 17, '/uploads/livros/9ff1408665badb85b2086cdb1496cab7.jpg'
    UNION ALL SELECT 18, '/uploads/livros/cbcae01ca0a1c88015cd4af3d0159aa4.jpg'
) AS mapping
) AS images ON images.id = p.id
SET p.imagem_url = images.imagem_url
WHERE p.imagem_url IS NULL OR p.imagem_url = '';
