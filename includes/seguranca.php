<?php
// includes/seguranca.php — boas práticas da Aula 15: cabeçalhos HTTP, CSRF e sessão
//
// XSS:  tudo que vem do usuário é escapado na saída (e() no PHP, textContent no JS)
//       e a Content-Security-Policy só aceita scripts do próprio servidor.
// CSRF: toda requisição que altera dados (POST, PUT, PATCH, DELETE) precisa enviar
//       o token da sessão, no campo "csrf" do formulário ou no cabeçalho X-CSRF-Token.

const METODOS_SEGUROS = ["GET", "HEAD", "OPTIONS"];
const INATIVIDADE_MAXIMA = 2 * 60 * 60;   // 2 horas sem uso encerram a sessão

ini_set("display_errors", "0");   // erro detalhado vai para o log, nunca para a tela
ini_set("log_errors", "1");
date_default_timezone_set("America/Sao_Paulo");   // o XAMPP vem com Europe/Berlin; o restaurante é no Brasil

function enviarCabecalhosSeguranca(): void
{
    if (headers_sent()) {
        return;
    }
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
         . "img-src 'self' data:; connect-src 'self'; manifest-src 'self'; worker-src 'self'; "
         . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
    header("X-Content-Type-Options: nosniff");         // o navegador não "adivinha" o tipo do arquivo
    header("X-Frame-Options: DENY");                    // ninguém coloca o sistema dentro de um iframe
    header("Referrer-Policy: same-origin");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    header_remove("X-Powered-By");                      // não anuncia a versão do PHP
}

function iniciarSessaoSegura(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
    ini_set("session.use_strict_mode", "1");   // não aceita ID de sessão inventado
    ini_set("session.use_only_cookies", "1");  // ID nunca na URL
    session_set_cookie_params([
        "httponly" => true,     // JavaScript não lê o cookie de sessão (protege contra roubo via XSS)
        "secure" => $https,     // com HTTPS, o cookie só trafega criptografado
        "samesite" => "Lax",    // outro site não envia o cookie em POST (camada extra contra CSRF)
        "path" => "/",
    ]);
    session_start();

    // Encerra sessões paradas há muito tempo
    $agora = time();
    if (isset($_SESSION["ultimo_acesso"]) && $agora - $_SESSION["ultimo_acesso"] > INATIVIDADE_MAXIMA) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION["ultimo_acesso"] = $agora;
}

/* ---------------- CSRF ---------------- */

function csrfToken(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

/** Campo escondido para formulários HTML. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") . '">';
}

/** Troca o token (depois do login e do logout, quando o "dono" da sessão muda). */
function renovarCsrf(): void
{
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function verificarCsrf(): void
{
    if (in_array($_SERVER["REQUEST_METHOD"], METODOS_SEGUROS, true)) {
        return;
    }
    $enviado = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? $_POST["csrf"] ?? "";
    $esperado = $_SESSION["csrf"] ?? "";
    // hash_equals compara em tempo constante (não vaza o token pela demora da comparação)
    if ($esperado !== "" && is_string($enviado) && hash_equals($esperado, $enviado)) {
        return;
    }
    falharCsrf();
}

function falharCsrf(): void
{
    if (defined("MODO_API")) {
        responderJson(403, ["erro" => "Token CSRF ausente ou inválido. Recarregue a página e tente de novo."]);
    }
    http_response_code(403);
    require_once __DIR__ . "/layout.php";
    cabecalho("Página expirada");
    echo '<div class="vazio entra" style="max-width:520px;margin:40px auto">' . svg("alerta")
       . '<strong>Esta página expirou</strong><p>Por segurança, o formulário não foi enviado. Volte, recarregue a página e tente de novo.</p>'
       . '<p class="mt"><a class="btn" href="painel.php">Voltar</a></p></div>';
    rodape();
    exit;
}
