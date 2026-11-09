<?php
// GET /api/csrf.php — devolve o token CSRF da sessão atual (e cria a sessão, se preciso).
// Use antes de qualquer POST/PUT/PATCH/DELETE e envie no cabeçalho X-CSRF-Token.
// Outro site não consegue ler esta resposta (mesma origem), então não consegue forjar o token.
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";

exigirMetodo("GET");
header("Cache-Control: no-store");
responderJson(200, ["csrf" => csrfToken()]);
