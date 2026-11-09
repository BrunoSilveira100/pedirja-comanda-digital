<?php
// includes/produtos.php — acesso à tabela produtos (cardápio)

function listarProdutos(PDO $pdo, bool $somenteDisponiveis = false): array
{
    $sql = "SELECT id, nome, descricao, preco, categoria, disponivel, imagem FROM produtos";
    if ($somenteDisponiveis) {
        $sql .= " WHERE disponivel = TRUE";
    }
    $sql .= " ORDER BY categoria, nome";
    return array_map("normalizarProduto", $pdo->query($sql)->fetchAll());
}

/** Converte os tipos vindos do banco (strings) para o JSON da API. */
function normalizarProduto(array $p): array
{
    $p["id"] = (int) $p["id"];
    $p["preco"] = (float) $p["preco"];
    $p["disponivel"] = (bool) $p["disponivel"];
    $p["imagem"] = imagemValida($p["imagem"] ?? null);
    return $p;
}

/**
 * Só aceita fotos da pasta assets/img/produtos (nome simples, jpg/png/webp).
 * Qualquer outro valor vira null e a tela mostra o ícone padrão.
 */
function imagemValida(?string $caminho): ?string
{
    if ($caminho === null || !preg_match('#^assets/img/produtos/[a-z0-9-]+\.(jpg|jpeg|png|webp)$#', $caminho)) {
        return null;
    }
    return $caminho;
}

function buscarProduto(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare("SELECT id, nome, descricao, preco, categoria, disponivel, imagem FROM produtos WHERE id = :id");
    $st->execute([":id" => $id]);
    $p = $st->fetch();
    return $p ?: null;
}

/**
 * Valida os campos de produto. Com $parcial = true (PUT), só confere os campos enviados.
 * Devolve somente os campos válidos, prontos para gravar.
 */
function validarProduto(array $d, bool $parcial = false): array
{
    $saida = [];
    if (!$parcial || array_key_exists("nome", $d)) {
        $nome = trim((string) ($d["nome"] ?? ""));
        if ($nome === "" || mb_strlen($nome) > 120) {
            throw new ErroDeRegra(400, "Nome obrigatório, até 120 caracteres.");
        }
        $saida["nome"] = $nome;
    }
    if (!$parcial || array_key_exists("categoria", $d)) {
        $cat = trim((string) ($d["categoria"] ?? ""));
        if ($cat === "" || mb_strlen($cat) > 50) {
            throw new ErroDeRegra(400, "Categoria obrigatória, até 50 caracteres.");
        }
        $saida["categoria"] = $cat;
    }
    if (!$parcial || array_key_exists("preco", $d)) {
        $preco = filter_var($d["preco"] ?? null, FILTER_VALIDATE_FLOAT);
        if ($preco === false || $preco <= 0 || $preco > 99999999) {
            throw new ErroDeRegra(400, "Preço deve ser um número maior que zero.");
        }
        $saida["preco"] = round($preco, 2);
    }
    if (array_key_exists("descricao", $d)) {
        $saida["descricao"] = trim((string) $d["descricao"]) ?: null;
    }
    if (array_key_exists("disponivel", $d)) {
        $disp = filter_var($d["disponivel"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($disp === null) {
            throw new ErroDeRegra(400, "\"disponivel\" deve ser true ou false.");
        }
        $saida["disponivel"] = $disp ? 1 : 0;
    }
    return $saida;
}

function inserirProduto(PDO $pdo, array $p): int
{
    $st = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, categoria, disponivel)
                         VALUES (:nome, :descricao, :preco, :categoria, :disponivel)");
    $st->execute([
        ":nome" => $p["nome"],
        ":descricao" => $p["descricao"] ?? null,
        ":preco" => $p["preco"],
        ":categoria" => $p["categoria"],
        ":disponivel" => $p["disponivel"] ?? 1,
    ]);
    return (int) $pdo->lastInsertId();
}

/** Atualiza só os campos informados (nomes já validados por validarProduto). */
function atualizarProduto(PDO $pdo, int $id, array $campos): void
{
    if (!$campos) {
        throw new ErroDeRegra(400, "Nenhum campo para atualizar.");
    }
    $sets = [];
    $params = [":id" => $id];
    foreach ($campos as $campo => $valor) {
        $sets[] = "$campo = :$campo";
        $params[":$campo"] = $valor;
    }
    $pdo->prepare("UPDATE produtos SET " . implode(", ", $sets) . " WHERE id = :id")->execute($params);
}

/**
 * Exclui um produto. Devolve false se ele já aparece em pedidos
 * (a chave estrangeira impede apagar o histórico).
 */
function excluirProduto(PDO $pdo, int $id): bool
{
    try {
        $st = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $st->execute([":id" => $id]);
        return $st->rowCount() > 0;
    } catch (PDOException $e) {
        if ($e->getCode() === "23000") {
            return false;
        }
        throw $e;
    }
}
