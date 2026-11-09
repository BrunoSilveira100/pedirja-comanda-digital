-- Migração: fotos do cardápio (08/11/2026)
-- Para quem já tem o banco restaurante_db criado e NÃO quer rodar o banco.sql de novo
-- (o banco.sql apaga e recria tudo). Esta migração só adiciona a coluna e preenche as fotos;
-- não apaga usuários, pedidos nem produtos.
--
-- phpMyAdmin → restaurante_db → aba SQL → colar e executar.
-- Pode rodar mais de uma vez: a coluna só é criada se ainda não existir.

USE restaurante_db;

ALTER TABLE produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) NULL;

UPDATE produtos SET imagem = 'assets/img/produtos/x-burguer.jpg'         WHERE nome = 'X-Burguer'           AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/x-bacon.jpg'           WHERE nome = 'X-Bacon'             AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/misto-quente.jpg'      WHERE nome = 'Misto quente'        AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/pao-de-queijo.jpg'     WHERE nome = 'Pão de queijo (6un)' AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/coxinha.jpg'           WHERE nome = 'Coxinha'             AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/batata-frita.jpg'      WHERE nome = 'Batata frita'        AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/refrigerante-lata.jpg' WHERE nome = 'Refrigerante lata'   AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/suco-natural.jpg'      WHERE nome = 'Suco natural'        AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/cafe-expresso.jpg'     WHERE nome = 'Café expresso'       AND imagem IS NULL;
UPDATE produtos SET imagem = 'assets/img/produtos/pudim.jpg'             WHERE nome = 'Pudim'               AND imagem IS NULL;
