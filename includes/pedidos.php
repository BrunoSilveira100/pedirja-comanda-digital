<?php
// includes/pedidos.php — regras de pedidos, itens, mesas e caixa

/**
 * Ciclo de vida do pedido: de qual status para qual, e quem pode fazer a mudança.
 * recebido → em_preparo → pronto → entregue → solicitado_pagamento → finalizado
 */
const TRANSICOES = [
    "em_preparo"           => ["de" => "recebido",             "perfil" => "cozinha"],
    "pronto"               => ["de" => "em_preparo",           "perfil" => "cozinha"],
    "entregue"             => ["de" => "pronto",               "perfil" => "garcom"],
    "solicitado_pagamento" => ["de" => "entregue",             "perfil" => "cliente"],
    "finalizado"           => ["de" => "solicitado_pagamento", "perfil" => "admin"],
];

const STATUS_PEDIDO = ["recebido", "em_preparo", "pronto", "entregue", "solicitado_pagamento", "finalizado"];

const MAX_ITENS_POR_PEDIDO = 30;
const MAX_QUANTIDADE_POR_ITEM = 50;

/**
 * Valida a lista de itens enviada pelo cliente e devolve itens normalizados.
 * Formato: [{ "produto_id": 1, "quantidade": 2, "observacao": "sem cebola" }]
 */
function validarItens($itens): array
{
    if (!is_array($itens) || count($itens) === 0) {
        throw new ErroDeRegra(400, "Informe pelo menos um item.");
    }
    if (count($itens) > MAX_ITENS_POR_PEDIDO) {
        throw new ErroDeRegra(400, "Máximo de " . MAX_ITENS_POR_PEDIDO . " itens por pedido.");
    }
    $normalizados = [];
    foreach ($itens as $i => $item) {
        $n = $i + 1;
        $produtoId = filter_var($item["produto_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $qtd = filter_var($item["quantidade"] ?? null, FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => MAX_QUANTIDADE_POR_ITEM]]);
        $obs = trim((string) ($item["observacao"] ?? ""));

        if (!$produtoId) {
            throw new ErroDeRegra(400, "Item $n: produto_id inválido.");
        }
        if (!$qtd) {
            throw new ErroDeRegra(400, "Item $n: quantidade deve ser de 1 a " . MAX_QUANTIDADE_POR_ITEM . ".");
        }
        if (mb_strlen($obs) > 255) {
            throw new ErroDeRegra(400, "Item $n: observação com mais de 255 caracteres.");
        }
        $normalizados[] = ["produto_id" => $produtoId, "quantidade" => $qtd, "observacao" => $obs ?: null];
    }
    return $normalizados;
}

const JANELA_PEDIDO_REPETIDO = 10;   // segundos

/** "Assinatura" dos itens: mesma lista em qualquer ordem gera o mesmo texto. */
function assinaturaItens(array $itens): string
{
    $partes = array_map(fn($i) => (int) $i["produto_id"] . "|" . (int) $i["quantidade"] . "|" . ($i["observacao"] ?? ""), $itens);
    sort($partes);
    return implode("\n", $partes);
}

/**
 * Envio repetido (duplo clique, rede lenta, Postman): o mesmo cliente, na mesma mesa, mandou
 * exatamente os mesmos itens há menos de JANELA_PEDIDO_REPETIDO segundos.
 */
function pedidoRepetido(PDO $pdo, int $clienteId, int $mesaId, array $itens): bool
{
    $st = $pdo->prepare("SELECT id FROM pedidos
                          WHERE cliente_id = :c AND mesa_id = :m
                            AND criado_em >= NOW() - INTERVAL " . JANELA_PEDIDO_REPETIDO . " SECOND");
    $st->execute([":c" => $clienteId, ":m" => $mesaId]);
    $recentes = $st->fetchAll(PDO::FETCH_COLUMN);
    if (!$recentes) {
        return false;
    }
    $novo = assinaturaItens($itens);
    $itensSt = $pdo->prepare("SELECT produto_id, quantidade, observacao FROM pedido_itens WHERE pedido_id = :p");
    foreach ($recentes as $pedidoId) {
        $itensSt->execute([":p" => $pedidoId]);
        if (assinaturaItens($itensSt->fetchAll()) === $novo) {
            return true;
        }
    }
    return false;
}

/**
 * Cria o pedido numa transação. O preço vem do banco, nunca do navegador.
 */
function criarPedido(PDO $pdo, int $clienteId, int $mesaId, array $itens): int
{
    $pdo->beginTransaction();
    try {
        // Trava a mesa para não aceitar pedido enquanto o caixa fecha a conta
        $st = $pdo->prepare("SELECT status FROM mesas WHERE id = :id FOR UPDATE");
        $st->execute([":id" => $mesaId]);
        $statusMesa = $st->fetchColumn();
        if ($statusMesa === false) {
            throw new ErroDeRegra(404, "Mesa não encontrada.");
        }
        if ($statusMesa === "aguardando_pagamento") {
            throw new ErroDeRegra(409, "A conta desta mesa já foi pedida. Chame um atendente.");
        }
        // A mesa já está travada: um segundo envio simultâneo espera aqui e vê o primeiro
        if (pedidoRepetido($pdo, $clienteId, $mesaId, $itens)) {
            throw new ErroDeRegra(409, "Este mesmo pedido acabou de ser enviado. Confira em \"Meus pedidos\".");
        }

        $ids = array_unique(array_column($itens, "produto_id"));
        $marcadores = implode(",", array_fill(0, count($ids), "?"));
        $st = $pdo->prepare("SELECT id, nome, preco, disponivel FROM produtos WHERE id IN ($marcadores)");
        $st->execute(array_values($ids));
        $produtos = [];
        foreach ($st->fetchAll() as $p) {
            $produtos[(int) $p["id"]] = $p;
        }

        $total = 0.0;
        foreach ($itens as &$item) {
            $p = $produtos[$item["produto_id"]] ?? null;
            if (!$p) {
                throw new ErroDeRegra(404, "Produto {$item["produto_id"]} não existe.");
            }
            if (!$p["disponivel"]) {
                throw new ErroDeRegra(409, "\"{$p["nome"]}\" está indisponível no momento.");
            }
            $item["preco_unitario"] = (float) $p["preco"];
            $total += $item["preco_unitario"] * $item["quantidade"];
        }
        unset($item);

        $st = $pdo->prepare("INSERT INTO pedidos (cliente_id, mesa_id, total) VALUES (:c, :m, :t)");
        $st->execute([":c" => $clienteId, ":m" => $mesaId, ":t" => round($total, 2)]);
        $pedidoId = (int) $pdo->lastInsertId();

        $st = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, observacao)
                             VALUES (:p, :prod, :q, :preco, :obs)");
        foreach ($itens as $item) {
            $st->execute([
                ":p" => $pedidoId,
                ":prod" => $item["produto_id"],
                ":q" => $item["quantidade"],
                ":preco" => $item["preco_unitario"],
                ":obs" => $item["observacao"],
            ]);
        }

        $pdo->prepare("UPDATE mesas SET status = 'ocupada' WHERE id = :id")->execute([":id" => $mesaId]);

        $pdo->commit();
        return $pedidoId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Busca pedidos com mesa, cliente e itens. Filtros opcionais:
 * status (array), cliente_id, mesa_id, desde (data Y-m-d).
 */
function listarPedidos(PDO $pdo, array $filtros = [], string $ordem = "p.criado_em ASC"): array
{
    $where = [];
    $params = [];
    if (!empty($filtros["status"])) {
        $marc = [];
        foreach (array_values($filtros["status"]) as $i => $s) {
            $marc[] = ":s$i";
            $params[":s$i"] = $s;
        }
        $where[] = "p.status IN (" . implode(",", $marc) . ")";
    }
    if (!empty($filtros["cliente_id"])) {
        $where[] = "p.cliente_id = :cliente";
        $params[":cliente"] = $filtros["cliente_id"];
    }
    if (!empty($filtros["mesa_id"])) {
        $where[] = "p.mesa_id = :mesa";
        $params[":mesa"] = $filtros["mesa_id"];
    }
    if (!empty($filtros["id"])) {
        $where[] = "p.id = :id";
        $params[":id"] = $filtros["id"];
    }
    if (!empty($filtros["desde"])) {
        $where[] = "p.criado_em >= :desde";
        $params[":desde"] = $filtros["desde"];
    }

    $sql = "SELECT p.id, p.status, p.total, p.criado_em, m.id AS mesa_id, m.numero AS mesa,
                   u.id AS cliente_id, u.nome AS cliente
              FROM pedidos p
              JOIN mesas m ON m.id = p.mesa_id
              JOIN usuarios u ON u.id = p.cliente_id"
         . ($where ? " WHERE " . implode(" AND ", $where) : "")
         . " ORDER BY $ordem"
         . (!empty($filtros["limite"]) ? " LIMIT " . (int) $filtros["limite"] : "");
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $pedidos = $st->fetchAll();

    return anexarItens($pdo, $pedidos);
}

function anexarItens(PDO $pdo, array $pedidos): array
{
    if (!$pedidos) {
        return [];
    }
    $ids = array_column($pedidos, "id");
    $marc = implode(",", array_fill(0, count($ids), "?"));
    $st = $pdo->prepare("SELECT i.pedido_id, i.produto_id, pr.nome, i.quantidade, i.preco_unitario, i.observacao
                           FROM pedido_itens i JOIN produtos pr ON pr.id = i.produto_id
                          WHERE i.pedido_id IN ($marc) ORDER BY i.id");
    $st->execute($ids);
    $porPedido = [];
    foreach ($st->fetchAll() as $i) {
        $porPedido[$i["pedido_id"]][] = [
            "produto_id" => (int) $i["produto_id"],
            "nome" => $i["nome"],
            "quantidade" => (int) $i["quantidade"],
            "preco_unitario" => (float) $i["preco_unitario"],
            "observacao" => $i["observacao"],
        ];
    }
    foreach ($pedidos as &$p) {
        $p["id"] = (int) $p["id"];
        $p["total"] = (float) $p["total"];
        $p["mesa_id"] = (int) $p["mesa_id"];
        $p["mesa"] = (int) $p["mesa"];
        $p["cliente_id"] = (int) $p["cliente_id"];
        $p["itens"] = $porPedido[$p["id"]] ?? [];
    }
    return $pedidos;
}

function buscarPedidoCompleto(PDO $pdo, int $id): ?array
{
    $lista = listarPedidos($pdo, ["id" => $id]);
    return $lista[0] ?? null;
}

/**
 * Muda o status de um pedido respeitando o ciclo de vida.
 * O UPDATE só acontece se o pedido ainda estiver no status esperado,
 * então duas pessoas clicando ao mesmo tempo não pulam etapas.
 */
function mudarStatusPedido(PDO $pdo, int $pedidoId, string $novo, string $perfilUsuario): array
{
    if (!isset(TRANSICOES[$novo])) {
        throw new ErroDeRegra(400, "Status inválido.");
    }
    $regra = TRANSICOES[$novo];
    if ($perfilUsuario !== "admin" && $perfilUsuario !== $regra["perfil"]) {
        throw new ErroDeRegra(403, "Seu perfil não pode mudar o pedido para \"$novo\".");
    }

    $st = $pdo->prepare("UPDATE pedidos SET status = :novo WHERE id = :id AND status = :de");
    $st->execute([":novo" => $novo, ":id" => $pedidoId, ":de" => $regra["de"]]);

    if ($st->rowCount() === 0) {
        $atual = $pdo->prepare("SELECT status FROM pedidos WHERE id = :id");
        $atual->execute([":id" => $pedidoId]);
        $status = $atual->fetchColumn();
        if ($status === false) {
            throw new ErroDeRegra(404, "Pedido não encontrado.");
        }
        throw new ErroDeRegra(409, "O pedido está \"$status\" e não pode ir para \"$novo\".");
    }
    return buscarPedidoCompleto($pdo, $pedidoId);
}

/**
 * Cliente pede a conta da mesa. Todos os pedidos em aberto da mesa
 * precisam ter sido entregues.
 */
function solicitarPagamento(PDO $pdo, int $mesaId): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT status FROM mesas WHERE id = :id FOR UPDATE");
        $st->execute([":id" => $mesaId]);
        $statusMesa = $st->fetchColumn();
        if ($statusMesa === "aguardando_pagamento") {
            throw new ErroDeRegra(409, "A conta desta mesa já foi solicitada.");
        }

        $st = $pdo->prepare("SELECT status, COUNT(*) AS qtd FROM pedidos
                              WHERE mesa_id = :m AND status <> 'finalizado' GROUP BY status");
        $st->execute([":m" => $mesaId]);
        $contagem = array_column($st->fetchAll(), "qtd", "status");

        if (!$contagem) {
            throw new ErroDeRegra(409, "Não há pedidos em aberto nesta mesa.");
        }
        $emAndamento = array_sum(array_intersect_key($contagem, array_flip(["recebido", "em_preparo", "pronto"])));
        if ($emAndamento > 0) {
            throw new ErroDeRegra(409, "Ainda há $emAndamento pedido(s) sendo preparados ou entregues.");
        }

        $pdo->prepare("UPDATE pedidos SET status = 'solicitado_pagamento'
                        WHERE mesa_id = :m AND status = 'entregue'")->execute([":m" => $mesaId]);
        $pdo->prepare("UPDATE mesas SET status = 'aguardando_pagamento' WHERE id = :m")->execute([":m" => $mesaId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return contaDaMesa($pdo, $mesaId);
}

/** Pedidos ainda não finalizados da mesa (a conta em aberto) e o total. */
function contaDaMesa(PDO $pdo, int $mesaId): array
{
    $st = $pdo->prepare("SELECT id, numero, status FROM mesas WHERE id = :id");
    $st->execute([":id" => $mesaId]);
    $mesa = $st->fetch();
    if (!$mesa) {
        throw new ErroDeRegra(404, "Mesa não encontrada.");
    }
    $abertos = array_values(array_diff(STATUS_PEDIDO, ["finalizado"]));
    $pedidos = listarPedidos($pdo, ["mesa_id" => $mesaId, "status" => $abertos]);
    return [
        "mesa_id" => (int) $mesa["id"],
        "mesa" => (int) $mesa["numero"],
        "status_mesa" => $mesa["status"],
        "pedidos" => $pedidos,
        "total" => round(array_sum(array_column($pedidos, "total")), 2),
    ];
}

/** Caixa fecha a conta: pedidos → finalizado, mesa → livre. */
function fecharConta(PDO $pdo, int $mesaId): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT status FROM mesas WHERE id = :id FOR UPDATE");
        $st->execute([":id" => $mesaId]);
        $statusMesa = $st->fetchColumn();
        if ($statusMesa === false) {
            throw new ErroDeRegra(404, "Mesa não encontrada.");
        }
        if ($statusMesa !== "aguardando_pagamento") {
            throw new ErroDeRegra(409, "Esta mesa não está aguardando pagamento.");
        }
        $conta = contaDaMesa($pdo, $mesaId);

        $pdo->prepare("UPDATE pedidos SET status = 'finalizado'
                        WHERE mesa_id = :m AND status = 'solicitado_pagamento'")->execute([":m" => $mesaId]);
        $pdo->prepare("UPDATE mesas SET status = 'livre' WHERE id = :m")->execute([":m" => $mesaId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    $conta["status_mesa"] = "livre";
    foreach ($conta["pedidos"] as &$p) {
        $p["status"] = "finalizado";
    }
    return $conta;
}
