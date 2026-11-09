<?php
// POST /api/logout.php — 200 e sessão destruída
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/usuarios.php";

exigirMetodo("POST");
exigirLogin();

encerrarSessao();
responderJson(200, ["mensagem" => "Sessão encerrada."]);
