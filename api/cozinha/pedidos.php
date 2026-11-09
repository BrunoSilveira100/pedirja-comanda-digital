<?php
// GET /api/cozinha/pedidos.php — fila da cozinha (recebido e em_preparo), mais antigos primeiro
// 200 | 401 sem login | 403 perfil diferente de cozinha/admin
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET");
exigirPerfil("cozinha");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

responderJson(200, listarPedidos($pdo, ["status" => ["recebido", "em_preparo"]], "p.criado_em ASC"));
