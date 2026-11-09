-- PedirJá — Comanda digital (Linguagem de Programação VIII)
-- Rodar no phpMyAdmin (aba SQL) ou: mysql -u root < banco/banco.sql

DROP DATABASE IF EXISTS restaurante_db;
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurante_db;

-- 1. Usuários (Aula 12: senha_hash VARCHAR(255), perfil para autorização)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('cliente', 'cozinha', 'garcom', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Mesas / QR Codes
CREATE TABLE mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL UNIQUE,
    qr_code_token VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('livre', 'ocupada', 'aguardando_pagamento') NOT NULL DEFAULT 'livre'
) ENGINE=InnoDB;

-- 3. Cardápio / Produtos (entidade principal do tema)
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    disponivel BOOLEAN NOT NULL DEFAULT TRUE,
    imagem VARCHAR(255) NULL   -- caminho da foto em assets/img/produtos (NULL = sem foto)
) ENGINE=InnoDB;

-- 4. Pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    mesa_id INT NOT NULL,
    status ENUM('recebido', 'em_preparo', 'pronto', 'entregue', 'solicitado_pagamento', 'finalizado')
        NOT NULL DEFAULT 'recebido',
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (mesa_id) REFERENCES mesas(id)
) ENGINE=InnoDB;

-- 5. Itens do pedido (preco_unitario guarda o preço do momento da compra)
CREATE TABLE pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    observacao VARCHAR(255),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- 6. Tentativas de login que falharam (limite contra força bruta)
CREATE TABLE tentativas_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(150) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_email (ip, email, criado_em),
    INDEX idx_ip (ip, criado_em)
) ENGINE=InnoDB;

-- Usuários de teste (hashes reais gerados com password_hash; senhas no README)
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Administrador Gerente', 'admin@restaurante.com',   '$2y$10$i0mFjl9lOdxYljY.GVp7r.j22a9AU3MJ4tg/wMFAGZKzoSug9yZ9G', 'admin'),
('Cozinheiro Chefe',      'cozinha@restaurante.com', '$2y$10$qDWXV5YWzN/BB1zO1nJ28.vAXUKV43qgz24T1M5cRcPEjNXk4ZdBK', 'cozinha'),
('Garçom Silva',          'garcom@restaurante.com',  '$2y$10$RCqx6o1iC3k1T.OFo7xSzOxQ4lJTfOynndTf9VpyCSb/u672xd6Gy', 'garcom'),
('Cliente Teste',         'cliente@restaurante.com', '$2y$10$W8CBDr2qFTToIE.DIZace.L5uUY2E2OBCaPHIGi2Ymw440PwdfjQW', 'cliente');

INSERT INTO mesas (numero, qr_code_token) VALUES
(1, 'mesa-01-a7f3c9'),
(2, 'mesa-02-b81d4e'),
(3, 'mesa-03-c92e5f'),
(4, 'mesa-04-d0a6b1');

-- Fotos dos produtos em assets/img/produtos/
INSERT INTO produtos (nome, descricao, preco, categoria, disponivel, imagem) VALUES
('X-Burguer',          'Pão, hambúrguer 150g, queijo e salada',  24.90, 'Lanches',     TRUE,  'assets/img/produtos/x-burguer.jpg'),
('X-Bacon',            'Pão, hambúrguer 150g, queijo e bacon',   28.90, 'Lanches',     TRUE,  'assets/img/produtos/x-bacon.jpg'),
('Misto quente',       'Pão de forma, presunto e queijo',        12.00, 'Lanches',     TRUE,  'assets/img/produtos/misto-quente.jpg'),
('Pão de queijo (6un)','Porção com 6 unidades',                  10.00, 'Padaria',     TRUE,  'assets/img/produtos/pao-de-queijo.jpg'),
('Coxinha',            'Coxinha de frango com catupiry',          8.50, 'Salgados',    TRUE,  'assets/img/produtos/coxinha.jpg'),
('Batata frita',       'Porção média',                           18.00, 'Porções',     TRUE,  'assets/img/produtos/batata-frita.jpg'),
('Refrigerante lata',  '350 ml',                                  6.00, 'Bebidas',     TRUE,  'assets/img/produtos/refrigerante-lata.jpg'),
('Suco natural',       'Laranja, 400 ml',                         9.00, 'Bebidas',     TRUE,  'assets/img/produtos/suco-natural.jpg'),
('Café expresso',      'Xícara 60 ml',                            5.00, 'Bebidas',     TRUE,  'assets/img/produtos/cafe-expresso.jpg'),
('Pudim',              'Fatia de pudim de leite',                 9.50, 'Sobremesas',  FALSE, 'assets/img/produtos/pudim.jpg');
