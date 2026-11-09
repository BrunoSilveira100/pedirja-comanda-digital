<?php
// GET /api/admin/dashboard.php — visão geral do dia para o gerente/caixa
// 200 | 401 | 403
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";

// "Hoje" vem do MySQL, o mesmo relógio que grava pedidos.criado_em. O PHP do XAMPP vem com
// fuso Europe/Berlin (4 h à frente): com date() a visão geral zerava a partir das 20h.
$hoje = (string) $pdo->query("SELECT CURDATE()")->fetchColumn();

$st = $pdo->prepare("SELECT status, COUNT(*) qtd FROM pedidos WHERE DATE(criado_em) = :d GROUP BY status");
$st->execute([":d" => $hoje]);
$porStatus = array_map("intval", array_column($st->fetchAll(), "qtd", "status"));

$st = $pdo->prepare("SELECT COUNT(*) qtd, COALESCE(SUM(total), 0) soma
                       FROM pedidos WHERE status = 'finalizado' AND DATE(criado_em) = :d");
$st->execute([":d" => $hoje]);
$fin = $st->fetch();
$faturamento = round((float) $fin["soma"], 2);
$qtdFinalizados = (int) $fin["qtd"];

$st = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM pedidos
                      WHERE status <> 'finalizado' AND DATE(criado_em) = :d");
$st->execute([":d" => $hoje]);
$emAberto = round((float) $st->fetchColumn(), 2);

$mesas = array_map("intval", array_column(
    $pdo->query("SELECT status, COUNT(*) qtd FROM mesas GROUP BY status")->fetchAll(), "qtd", "status"));

$usuarios = array_map("intval", array_column(
    $pdo->query("SELECT perfil, COUNT(*) qtd FROM usuarios GROUP BY perfil")->fetchAll(), "qtd", "perfil"));

$st = $pdo->prepare("SELECT pr.id, pr.nome, SUM(i.quantidade) quantidade
                       FROM pedido_itens i
                       JOIN pedidos p ON p.id = i.pedido_id
                       JOIN produtos pr ON pr.id = i.produto_id
                      WHERE DATE(p.criado_em) = :d
                   GROUP BY pr.id, pr.nome
                   ORDER BY quantidade DESC
                      LIMIT 5");
$st->execute([":d" => $hoje]);
$maisVendidos = array_map(fn($r) => [
    "produto_id" => (int) $r["id"], "nome" => $r["nome"], "quantidade" => (int) $r["quantidade"],
], $st->fetchAll());

responderJson(200, [
    "data" => $hoje,
    "pedidos_por_status" => $porStatus,
    "faturamento_hoje" => $faturamento,
    "pedidos_finalizados_hoje" => $qtdFinalizados,
    "ticket_medio" => $qtdFinalizados ? round($faturamento / $qtdFinalizados, 2) : 0,
    "valor_em_aberto" => $emAberto,
    "mesas_por_status" => $mesas,
    "usuarios_por_perfil" => $usuarios,
    "mais_vendidos_hoje" => $maisVendidos,
]);
