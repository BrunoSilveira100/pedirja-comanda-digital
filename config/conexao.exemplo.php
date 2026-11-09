<?php
// config/conexao.exemplo.php
// Copie este arquivo para config/conexao.php e ajuste as credenciais.
// O conexao.php real fica fora do Git (.gitignore).

$DB_HOST = "localhost";
$DB_NOME = "restaurante_db";
$DB_USUARIO = "root";
$DB_SENHA = "";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NOME;charset=utf8mb4",
        $DB_USUARIO,
        $DB_SENHA,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Falha na conexão: " . $e->getMessage());
    http_response_code(500);
    if (defined("MODO_API")) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["erro" => "Erro interno. Tente novamente mais tarde."]);
    } else {
        echo "Erro interno. Tente novamente mais tarde.";
    }
    exit;
}
