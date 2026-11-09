<?php
// GET /api/cardapio.php — produtos disponíveis | 401 sem login
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";

exigirMetodo("GET");
exigirLogin();

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/produtos.php";

responderJson(200, listarProdutos($pdo, true));
