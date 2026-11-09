<?php
// includes/resposta.php — utilitários de resposta e entrada

/** Exceção com status HTTP, para as regras de negócio responderem 400/403/404/409. */
class ErroDeRegra extends RuntimeException
{
    public function __construct(public int $status, string $mensagem)
    {
        parent::__construct($mensagem);
    }
}

/**
 * Na API, qualquer exceção vira JSON: ErroDeRegra com o status dela,
 * o resto como 500 genérico (o detalhe vai só para o log, não para o usuário).
 */
function registrarTratadorDeErrosApi(): void
{
    set_exception_handler(function (Throwable $e) {
        if ($e instanceof ErroDeRegra) {
            responderJson($e->status, ["erro" => $e->getMessage()]);
        }
        error_log("[PedirJá] " . $e);
        responderJson(500, ["erro" => "Erro interno. Tente novamente mais tarde."]);
    });
}

function responderJson(int $status, $dados): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lê o corpo da requisição: JSON (fetch/Postman) ou formulário.
 * PUT/PATCH não preenchem $_POST, por isso o php://input.
 */
function lerEntrada(): array
{
    $tipo = $_SERVER["CONTENT_TYPE"] ?? "";
    if (stripos($tipo, "application/json") !== false) {
        $dados = json_decode(file_get_contents("php://input"), true);
        if (!is_array($dados)) {
            responderJson(400, ["erro" => "JSON inválido."]);
        }
        return $dados;
    }
    return $_POST;
}

/** Lê um id inteiro positivo do corpo ou da query string; 400 se inválido. */
function exigirId(array $dados, string $campo): int
{
    $id = filter_var($dados[$campo] ?? $_GET[$campo] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if (!$id) {
        throw new ErroDeRegra(400, "Informe \"$campo\" válido.");
    }
    return $id;
}

function exigirMetodo(string ...$metodos): void
{
    if (!in_array($_SERVER["REQUEST_METHOD"], $metodos, true)) {
        header("Allow: " . implode(", ", $metodos));
        responderJson(405, ["erro" => "Método não permitido."]);
    }
}

/** Escapa texto para HTML (evita XSS ao exibir dados). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? "", ENT_QUOTES, "UTF-8");
}
