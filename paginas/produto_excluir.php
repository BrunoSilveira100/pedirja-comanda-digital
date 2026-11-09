<?php
// Ação restrita: somente admin. Outros perfis recebem 403.
// Esconder o botão não protege — a verificação é feita aqui, no servidor.
require_once __DIR__ . "/../includes/auth.php";
exigirPerfil("admin");

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/produtos.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Use o botão Excluir da listagem.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if (!$id || !buscarProduto($pdo, $id)) {
    header("Location: produtos.php?msg=nao_encontrado");
    exit;
}

$msg = excluirProduto($pdo, $id) ? "excluido" : "em_uso";
header("Location: produtos.php?msg=$msg");
exit;
