<?php
// includes/auth.php — autenticação (quem é você?) e autorização (você pode?)
//
// Páginas HTML: sem login → redireciona para login.php; sem perfil → 403.
// API (arquivos que definem MODO_API antes do require): respostas em JSON, 401 e 403.
// Todo arquivo que inclui este também ganha: cabeçalhos de segurança, sessão segura
// e verificação de CSRF em POST/PUT/PATCH/DELETE.

require_once __DIR__ . "/resposta.php";
require_once __DIR__ . "/seguranca.php";

if (defined("MODO_API")) {
    registrarTratadorDeErrosApi();
}

enviarCabecalhosSeguranca();
iniciarSessaoSegura();
verificarCsrf();

function usuarioLogado(): bool
{
    return !empty($_SESSION["usuario_id"]);
}

function exigirLogin(): void
{
    if (usuarioLogado()) {
        return;
    }
    if (defined("MODO_API")) {
        responderJson(401, ["erro" => "Não autenticado. Faça login."]);
    }
    header("Location: " . urlBase() . "paginas/login.php");
    exit;
}

/**
 * Exige um dos perfis informados. O admin (gerente/caixa) tem acesso a tudo,
 * conforme a especificação do PedirJá.
 */
function exigirPerfil(string ...$perfis): void
{
    exigirLogin();
    $perfil = $_SESSION["perfil"] ?? "";
    if ($perfil === "admin" || in_array($perfil, $perfis, true)) {
        return;
    }
    if (defined("MODO_API")) {
        responderJson(403, ["erro" => "Acesso negado. Perfil não autorizado."]);
    }
    http_response_code(403);
    require __DIR__ . "/../paginas/403.php";
    exit;
}

/**
 * Caminho base da aplicação (funciona em /pedirja/ ou na raiz, e dentro de /api/ e /paginas/).
 */
function urlBase(): string
{
    $dir = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
    $dir = preg_replace("#/(api|paginas)(/.*)?$#", "", $dir);
    return rtrim($dir, "/") . "/";
}
