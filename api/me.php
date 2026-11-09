<?php
// GET /api/me.php — quem está logado (o front usa para decidir a tela)
// 200 com usuário e mesa | 401 sem sessão
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";

exigirMetodo("GET");
exigirLogin();

responderJson(200, [
    "usuario" => [
        "id" => $_SESSION["usuario_id"],
        "nome" => $_SESSION["nome"] ?? "",
        "perfil" => $_SESSION["perfil"] ?? "",
    ],
    "mesa" => $_SESSION["mesa_numero"] ?? null,
]);
