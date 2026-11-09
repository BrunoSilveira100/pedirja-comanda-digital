<?php
// GET /api/garcom/pedidos.php — pedidos prontos para levar à mesa, mais antigos primeiro
// O front consulta de tempos em tempos (polling) para "notificar" o garçom.
// 200 | 401 | 403
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET");
exigirPerfil("garcom");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

responderJson(200, listarPedidos($pdo, ["status" => ["pronto"]], "p.criado_em ASC"));
