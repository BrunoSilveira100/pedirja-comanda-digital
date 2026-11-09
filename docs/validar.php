<?php
// docs/validar.php — validação automática do PedirJá (197 cenários).
// Só roda pelo terminal. NUNCA rode contra o banco real: o roteiro cria pedidos,
// fecha a conta da mesa 4 e exclui o produto 9 (Café expresso). Passo a passo em docs/VALIDACAO.md.
//
// Uso: php validar.php <url do sistema> <banco de validação> [url da cópia com banco fora]
//  ex: php validar.php http://127.0.0.1:8099/ restaurante_db_validacao http://127.0.0.1:8098/
if (PHP_SAPI !== "cli") { http_response_code(404); exit; }
$BASE = rtrim($argv[1] ?? "http://127.0.0.1:8099/", "/") . "/";
$BANCO = $argv[2] ?? "restaurante_db_validacao";
$BASE_FORA = isset($argv[3]) ? rtrim($argv[3], "/") . "/" : null;
if ($BANCO === "restaurante_db") {
    exit("Recusado: use um banco separado (ex.: restaurante_db_validacao). Veja docs/VALIDACAO.md.\n");
}
$PASTA_SESSAO = rtrim(str_replace("\\", "/", explode(";", ini_get("session.save_path"))[0] ?: sys_get_temp_dir()), "/");
$pdo = new PDO("mysql:host=localhost;dbname=$BANCO;charset=utf8mb4", "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$sess = [];   // ator => PHPSESSID
$csrf = [];   // ator => token
$linhas = [];
$ok = 0; $falhas = 0; $grupo = "";

function req(string $ator, string $metodo, string $caminho, $corpo = null, array $op = []): array
{
    global $BASE, $sess;
    $base = $op["base"] ?? $BASE;
    $ch = curl_init($base . $caminho);
    $h = [];
    if (!empty($sess[$ator])) $h[] = "Cookie: PHPSESSID=" . $sess[$ator];
    if (isset($op["csrf"])) $h[] = "X-CSRF-Token: " . $op["csrf"];
    if ($corpo !== null) {
        if (!empty($op["multipart"])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);   // array com CURLFile: envio de arquivo
        } elseif (!empty($op["form"])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($corpo));
        } else {
            $h[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($corpo) ? $corpo : json_encode($corpo));
        }
    }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true, CURLOPT_HTTPHEADER => $h, CURLOPT_FOLLOWLOCATION => false]);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $cab = substr($resp, 0, $hs);
    $corpoResp = substr($resp, $hs);
    if (preg_match_all('/^Set-Cookie: PHPSESSID=([^;]*)/mi', $cab, $m)) {
        $v = end($m[1]);
        $sess[$ator] = ($v === "" || $v === "deleted") ? null : $v;
    }
    preg_match('/^Location: (.*)$/mi', $cab, $loc);
    return ["status" => $status, "json" => json_decode($corpoResp, true), "raw" => $corpoResp,
            "cab" => $cab, "loc" => trim($loc[1] ?? "")];
}

function tok(string $ator): string
{
    global $csrf;
    $r = req($ator, "GET", "api/csrf.php");
    return $csrf[$ator] = $r["json"]["csrf"];
}

function grupo(string $g): void { global $grupo; $grupo = $g; }

function checar(string $cenario, string $rota, $esperado, $obtido, bool $passou = null, string $obs = ""): void
{
    global $linhas, $ok, $falhas, $grupo;
    $passou ??= ((string) $esperado === (string) $obtido);
    $passou ? $ok++ : $falhas++;
    $linhas[] = [$grupo, $cenario, $rota, (string) $esperado, (string) $obtido, $passou ? "OK" : "FALHOU", $obs];
}

function login(string $ator, string $email, string $senha): array
{
    global $csrf;
    $t = tok($ator);
    $r = req($ator, "POST", "api/login.php", ["email" => $email, "senha" => $senha], ["csrf" => $t]);
    if (!empty($r["json"]["csrf"])) $csrf[$ator] = $r["json"]["csrf"];
    return $r;
}

function q(string $sql, array $p = []) { global $pdo; $st = $pdo->prepare($sql); $st->execute($p); return $st; }

/* ===================== 1. AUTENTICAÇÃO E SESSÃO ===================== */
grupo("1. Autenticação e sessão");
$r = req("anon", "GET", "api/csrf.php");
checar("Token CSRF para visitante", "GET /api/csrf.php", 200, $r["status"]);
$csrf["anon"] = $r["json"]["csrf"] ?? "";
checar("Token tem 64 caracteres hex", "GET /api/csrf.php", "64", strlen($csrf["anon"]));

$r = req("anon", "GET", "api/me.php");
checar("Quem sou eu, sem login", "GET /api/me.php", 401, $r["status"]);
$r = req("anon", "POST", "api/login.php", ["email" => "admin@restaurante.com", "senha" => "Admin@123"]);
checar("Login sem token CSRF", "POST /api/login.php", 403, $r["status"]);
$r = req("anon", "POST", "api/login.php", ["email" => "", "senha" => ""], ["csrf" => $csrf["anon"]]);
checar("Login com campos vazios", "POST /api/login.php", 400, $r["status"]);
$r = req("anon", "POST", "api/login.php", "{json quebrado", ["csrf" => $csrf["anon"]]);
checar("Login com JSON inválido", "POST /api/login.php", 400, $r["status"]);
$r = req("anon", "GET", "api/login.php");
checar("Login com método errado (GET)", "GET /api/login.php", 405, $r["status"]);

$a = req("anon", "POST", "api/login.php", ["email" => "admin@restaurante.com", "senha" => "errada"], ["csrf" => $csrf["anon"]]);
$b = req("anon", "POST", "api/login.php", ["email" => "naoexiste@x.com", "senha" => "errada"], ["csrf" => $csrf["anon"]]);
checar("Senha errada", "POST /api/login.php", 401, $a["status"]);
checar("E-mail inexistente", "POST /api/login.php", 401, $b["status"]);
checar("Mensagem genérica: corpo idêntico nos dois casos", "POST /api/login.php", "iguais",
    $a["raw"] === $b["raw"] ? "iguais" : "diferentes", null, $a["raw"]);

$seeds = ["admin" => ["admin@restaurante.com", "Admin@123"], "cozinha" => ["cozinha@restaurante.com", "Cozinha@123"],
          "garcom" => ["garcom@restaurante.com", "Garcom@123"], "cliente" => ["cliente@restaurante.com", "Cliente@123"]];
foreach ($seeds as $ator => [$email, $senha]) {
    tok($ator);
    $antes = $sess[$ator];
    $r = login($ator, $email, $senha);
    checar("Login do $ator (senha do README)", "POST /api/login.php", 200, $r["status"]);
    checar("Perfil devolvido = $ator", "POST /api/login.php", $ator, $r["json"]["usuario"]["perfil"] ?? "-");
    checar("ID de sessão trocado no login ($ator)", "session_regenerate_id", "trocou",
        ($antes && $sess[$ator] && $antes !== $sess[$ator]) ? "trocou" : "igual");
    checar("Resposta não traz senha nem hash ($ator)", "POST /api/login.php", "ausente",
        (str_contains($r["raw"], "senha") || str_contains($r["raw"], '$2y$')) ? "presente" : "ausente");
    $r = req($ator, "GET", "api/me.php");
    checar("Sessão reconhecida ($ator)", "GET /api/me.php", 200, $r["status"]);
}

/* ===================== 2. CADASTRO E PERSISTÊNCIA NO BANCO ===================== */
grupo("2. Cadastro e o que fica salvo no banco");
$emailNovo = "validacao_" . time() . "@teste.com";
tok("novo");
$r = req("novo", "POST", "api/cadastro.php", ["nome" => "A", "email" => "x", "senha" => "1"], ["csrf" => $csrf["novo"]]);
checar("Cadastro com dados inválidos", "POST /api/cadastro.php", 400, $r["status"]);
$r = req("novo", "POST", "api/cadastro.php",
    ["nome" => "Cliente Validação", "email" => $emailNovo, "senha" => "Senha@123", "perfil" => "admin"], ["csrf" => $csrf["novo"]]);
checar("Cadastro válido (tentando forçar perfil admin)", "POST /api/cadastro.php", 201, $r["status"]);
$csrf["novo"] = $r["json"]["csrf"] ?? $csrf["novo"];
$u = q("SELECT * FROM usuarios WHERE email = ?", [$emailNovo])->fetch();
checar("Usuário gravado na tabela usuarios", "banco: usuarios", "1 linha", $u ? "1 linha" : "0 linhas");
checar("Senha gravada como hash bcrypt", "banco: usuarios.senha_hash", "\$2y\$...",
    $u && str_starts_with($u["senha_hash"], '$2y$') ? "\$2y\$..." : ($u["senha_hash"] ?? "-"));
checar("Senha digitada não aparece no banco", "banco: usuarios.senha_hash", "não aparece",
    $u && !str_contains($u["senha_hash"], "Senha@123") ? "não aparece" : "aparece");
checar("Perfil forçado pelo navegador é ignorado", "banco: usuarios.perfil", "cliente", $u["perfil"] ?? "-");
checar("Data de criação preenchida", "banco: usuarios.criado_em", "preenchida", !empty($u["criado_em"]) ? "preenchida" : "vazia");
$r = req("novo", "GET", "api/me.php");
checar("Cadastro já deixa logado", "GET /api/me.php", 200, $r["status"]);

tok("dup");
$r = req("dup", "POST", "api/cadastro.php", ["nome" => "Outro", "email" => $emailNovo, "senha" => "Senha@123"], ["csrf" => $csrf["dup"]]);
checar("Cadastro com e-mail repetido", "POST /api/cadastro.php", 409, $r["status"]);

// O que o login grava (ou não) no banco
$antesUsuario = q("SELECT * FROM usuarios WHERE email = ?", [$emailNovo])->fetch();
tok("falha");
req("falha", "POST", "api/login.php", ["email" => $emailNovo, "senha" => "errada"], ["csrf" => $csrf["falha"]]);
$nFalhas = (int) q("SELECT COUNT(*) FROM tentativas_login WHERE email = ?", [strtolower($emailNovo)])->fetchColumn();
checar("Login errado é registrado em tentativas_login", "banco: tentativas_login", "1", $nFalhas);
$r = login("relogin", $emailNovo, "Senha@123");
checar("Login do usuário recém-cadastrado", "POST /api/login.php", 200, $r["status"]);
$nFalhas = (int) q("SELECT COUNT(*) FROM tentativas_login WHERE email = ?", [strtolower($emailNovo)])->fetchColumn();
checar("Login certo apaga as tentativas erradas", "banco: tentativas_login", "0", $nFalhas);
$depoisUsuario = q("SELECT * FROM usuarios WHERE email = ?", [$emailNovo])->fetch();
checar("Login NÃO altera a linha do usuário (não há registro de último acesso)", "banco: usuarios", "inalterada",
    $antesUsuario === $depoisUsuario ? "inalterada" : "alterada", null, "Comportamento atual, ver ajuste sugerido A1");
$arq = "$PASTA_SESSAO/sess_" . $sess["relogin"];
$conteudo = is_file($arq) ? file_get_contents($arq) : "";
checar("Sessão do usuário logado fica em arquivo no servidor", "C:\\xampp\\tmp\\sess_<id>", "existe", $conteudo ? "existe" : "não existe",
    null, $conteudo ? "conteúdo: " . preg_replace('/csrf\|s:64:"[0-9a-f]+"/', 'csrf|s:64:"…"', $conteudo) : "");
checar("Arquivo de sessão guarda id e perfil, nunca a senha/hash", "C:\\xampp\\tmp\\sess_<id>", "sem senha",
    ($conteudo && str_contains($conteudo, "usuario_id") && !str_contains($conteudo, '$2y$') && !str_contains($conteudo, "Senha@123")) ? "sem senha" : "verificar");

/* ===================== 3. AUTORIZAÇÃO: MATRIZ DE ROTAS ===================== */
grupo("3. Matriz de acesso (401 / 403 / 200)");
$rotasGet = [
    "api/cardapio.php"          => ["cliente", "cozinha", "garcom", "admin"],
    "api/pedidos.php"           => ["cliente", "admin"],
    "api/cozinha/pedidos.php"   => ["cozinha", "admin"],
    "api/garcom/pedidos.php"    => ["garcom", "admin"],
    "api/admin/dashboard.php"   => ["admin"],
    "api/admin/pedidos.php"     => ["admin"],
    "api/admin/caixa.php"       => ["admin"],
    "api/admin/usuarios.php"    => ["admin"],
    "api/admin/produtos.php"    => ["admin"],
];
foreach ($rotasGet as $rota => $permitidos) {
    $r = req("anon", "GET", $rota);
    checar("Visitante", "GET /$rota", 401, $r["status"]);
    foreach (["cliente", "cozinha", "garcom", "admin"] as $ator) {
        $r = req($ator, "GET", $rota);
        $esp = in_array($ator, $permitidos, true) ? 200 : 403;
        checar(ucfirst($ator), "GET /$rota", $esp, $r["status"]);
    }
}
$r = req("cliente", "GET", "api/cliente/pagamento.php");
checar("Cliente sem mesa escaneada", "GET /api/cliente/pagamento.php", 400, $r["status"]);
foreach (["cozinha", "garcom"] as $ator) {
    $r = req($ator, "GET", "api/cliente/pagamento.php");
    checar(ucfirst($ator), "GET /api/cliente/pagamento.php", 403, $r["status"]);
}
$r = req("anon", "GET", "api/cliente/pagamento.php");
checar("Visitante", "GET /api/cliente/pagamento.php", 401, $r["status"]);
$r = req("cliente", "PUT", "api/cozinha/status.php", ["pedido_id" => 1, "status" => "em_preparo"], ["csrf" => $csrf["cliente"]]);
checar("Cliente tentando mudar status da cozinha", "PUT /api/cozinha/status.php", 403, $r["status"]);
$r = req("cozinha", "PUT", "api/garcom/entregar.php", ["pedido_id" => 1], ["csrf" => $csrf["cozinha"]]);
checar("Cozinha tentando confirmar entrega", "PUT /api/garcom/entregar.php", 403, $r["status"]);
$r = req("garcom", "POST", "api/admin/caixa.php", ["mesa_id" => 4], ["csrf" => $csrf["garcom"]]);
checar("Garçom tentando fechar conta", "POST /api/admin/caixa.php", 403, $r["status"]);
$r = req("cliente", "DELETE", "api/admin/produtos.php?id=1", null, ["csrf" => $csrf["cliente"]]);
checar("Cliente tentando excluir produto pela API", "DELETE /api/admin/produtos.php", 403, $r["status"]);
$r = req("cliente", "DELETE", "api/cardapio.php", null, ["csrf" => $csrf["cliente"]]);
checar("Método não permitido (com token válido)", "DELETE /api/cardapio.php", 405, $r["status"]);
$r = req("cliente", "DELETE", "api/cardapio.php");
checar("Método de escrita sem token: CSRF barra antes", "DELETE /api/cardapio.php", 403, $r["status"]);

/* ===================== 4. FLUXO COMPLETO DO PEDIDO ===================== */
grupo("4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa)");
$r = req("cliente", "GET", "mesa.php?t=token-falso");
checar("QR Code inválido", "GET /mesa.php?t=token-falso", 404, $r["status"]);
$r = req("cliente", "GET", "mesa.php?t=mesa-04-d0a6b1");
checar("QR Code da mesa 4", "GET /mesa.php?t=mesa-04-d0a6b1", 302, $r["status"], null, "Location: " . $r["loc"]);
$r = req("cliente", "GET", "api/me.php");
checar("Mesa guardada na sessão", "GET /api/me.php", 4, $r["json"]["mesa"] ?? "-");

$r = req("novo", "POST", "api/pedidos.php", ["itens" => [["produto_id" => 1, "quantidade" => 1]]], ["csrf" => $csrf["novo"]]);
checar("Pedido sem ter escaneado a mesa", "POST /api/pedidos.php", 400, $r["status"]);
$casos = [
    ["Pedido sem itens", ["itens" => []], 400],
    ["Quantidade zero", ["itens" => [["produto_id" => 1, "quantidade" => 0]]], 400],
    ["Quantidade acima de 50", ["itens" => [["produto_id" => 1, "quantidade" => 51]]], 400],
    ["Produto inexistente", ["itens" => [["produto_id" => 9999, "quantidade" => 1]]], 404],
    ["Produto indisponível (Pudim)", ["itens" => [["produto_id" => 10, "quantidade" => 1]]], 409],
    ["Observação com mais de 255 caracteres", ["itens" => [["produto_id" => 1, "quantidade" => 1, "observacao" => str_repeat("a", 256)]]], 400],
];
foreach ($casos as [$nome, $corpo, $esp]) {
    $r = req("cliente", "POST", "api/pedidos.php", $corpo, ["csrf" => $csrf["cliente"]]);
    checar($nome, "POST /api/pedidos.php", $esp, $r["status"], null, $r["json"]["erro"] ?? "");
}
$r = req("cliente", "POST", "api/pedidos.php", ["itens" => [
    ["produto_id" => 1, "quantidade" => 2, "observacao" => "sem cebola", "preco_unitario" => 0.01],
    ["produto_id" => 7, "quantidade" => 1, "preco" => 0.01]], "total" => 0.01], ["csrf" => $csrf["cliente"]]);
checar("Pedido válido (navegador manda preço falso 0,01)", "POST /api/pedidos.php", 201, $r["status"]);
$p1 = $r["json"]["id"] ?? 0;
$linha = q("SELECT total, status, mesa_id FROM pedidos WHERE id = ?", [$p1])->fetch();
checar("Total calculado no servidor: 2×24,90 + 6,00", "banco: pedidos.total", "55.80", $linha["total"] ?? "-");
checar("Pedido nasce como recebido na mesa 4", "banco: pedidos", "recebido/4", ($linha["status"] ?? "") . "/" . ($linha["mesa_id"] ?? ""));
$nItens = (int) q("SELECT COUNT(*) FROM pedido_itens WHERE pedido_id = ?", [$p1])->fetchColumn();
checar("Itens gravados na mesma transação", "banco: pedido_itens", 2, $nItens);
checar("Mesa passa a ocupada", "banco: mesas.status", "ocupada", q("SELECT status FROM mesas WHERE id = 4")->fetchColumn());
checar("Resposta traz cabeçalho Location do recurso criado", "POST /api/pedidos.php", "presente", $r["loc"] ? "presente" : "ausente");

$antes = (int) q("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$corpoDup = ["itens" => [["produto_id" => 3, "quantidade" => 1]]];
$r1 = req("cliente", "POST", "api/pedidos.php", $corpoDup, ["csrf" => $csrf["cliente"]]);
$r2 = req("cliente", "POST", "api/pedidos.php", $corpoDup, ["csrf" => $csrf["cliente"]]);
$depois = (int) q("SELECT COUNT(*) FROM pedidos")->fetchColumn();
checar("Envio repetido (2 POST iguais seguidos) no servidor", "POST /api/pedidos.php", "1 pedido(s)", ($depois - $antes) . " pedido(s)",
    null, "Segundo envio: " . $r2["status"] . " " . ($r2["json"]["erro"] ?? ""));
$p2 = $r1["json"]["id"] ?? 0; $p3 = $r2["json"]["id"] ?? 0;

$r = req("cozinha", "GET", "api/cozinha/pedidos.php");
checar("Fila da cozinha mostra os pedidos novos", "GET /api/cozinha/pedidos.php", "contém",
    in_array($p1, array_column($r["json"] ?? [], "id")) ? "contém" : "não contém");
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $p1, "status" => "pronto"], ["csrf" => $csrf["cozinha"]]);
checar("Pular etapa (recebido → pronto)", "PUT /api/cozinha/status.php", 409, $r["status"]);
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $p1, "status" => "entregue"], ["csrf" => $csrf["cozinha"]]);
checar("Status que a cozinha não usa", "PUT /api/cozinha/status.php", 400, $r["status"]);
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => 999999, "status" => "em_preparo"], ["csrf" => $csrf["cozinha"]]);
checar("Pedido inexistente", "PUT /api/cozinha/status.php", 404, $r["status"]);
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => "abc", "status" => "em_preparo"], ["csrf" => $csrf["cozinha"]]);
checar("pedido_id inválido", "PUT /api/cozinha/status.php", 400, $r["status"]);
$r = req("cozinha", "POST", "api/cozinha/status.php", ["pedido_id" => $p1, "status" => "em_preparo"], ["csrf" => $csrf["cozinha"]]);
checar("Método errado (POST em vez de PUT)", "POST /api/cozinha/status.php", 405, $r["status"]);
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $p1, "status" => "em_preparo"], ["csrf" => $csrf["cozinha"]]);
checar("recebido → em_preparo", "PUT /api/cozinha/status.php", 200, $r["status"]);
$r = req("garcom", "PUT", "api/garcom/entregar.php", ["pedido_id" => $p1], ["csrf" => $csrf["garcom"]]);
checar("Garçom entregar antes de ficar pronto", "PUT /api/garcom/entregar.php", 409, $r["status"]);
$r = req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $p1, "status" => "pronto"], ["csrf" => $csrf["cozinha"]]);
checar("em_preparo → pronto", "PUT /api/cozinha/status.php", 200, $r["status"]);
$r = req("garcom", "GET", "api/garcom/pedidos.php");
checar("Garçom vê o pedido pronto", "GET /api/garcom/pedidos.php", "contém",
    in_array($p1, array_column($r["json"] ?? [], "id")) ? "contém" : "não contém");
$r = req("garcom", "PUT", "api/garcom/entregar.php", ["pedido_id" => $p1], ["csrf" => $csrf["garcom"]]);
checar("pronto → entregue", "PUT /api/garcom/entregar.php", 200, $r["status"]);
$r = req("garcom", "PUT", "api/garcom/entregar.php", ["pedido_id" => $p1], ["csrf" => $csrf["garcom"]]);
checar("Entregar duas vezes", "PUT /api/garcom/entregar.php", 409, $r["status"]);

$r = req("cliente", "POST", "api/cliente/pagamento.php", [], ["csrf" => $csrf["cliente"]]);
checar("Pedir a conta com pedidos ainda na cozinha", "POST /api/cliente/pagamento.php", 409, $r["status"], null, $r["json"]["erro"] ?? "");
foreach (array_filter([$p2, $p3]) as $pid) {
    req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $pid, "status" => "em_preparo"], ["csrf" => $csrf["cozinha"]]);
    req("cozinha", "PUT", "api/cozinha/status.php", ["pedido_id" => $pid, "status" => "pronto"], ["csrf" => $csrf["cozinha"]]);
    req("garcom", "PUT", "api/garcom/entregar.php", ["pedido_id" => $pid], ["csrf" => $csrf["garcom"]]);
}
$r = req("cliente", "GET", "api/cliente/pagamento.php");
checar("Conta em aberto da mesa", "GET /api/cliente/pagamento.php", 200, $r["status"], null, "total R$ " . ($r["json"]["total"] ?? "-"));
$r = req("cliente", "POST", "api/cliente/pagamento.php", [], ["csrf" => $csrf["cliente"]]);
checar("Pedir a conta (tudo entregue)", "POST /api/cliente/pagamento.php", 200, $r["status"]);
checar("Mesa vai para aguardando_pagamento", "banco: mesas.status", "aguardando_pagamento", q("SELECT status FROM mesas WHERE id = 4")->fetchColumn());
$r = req("cliente", "POST", "api/cliente/pagamento.php", [], ["csrf" => $csrf["cliente"]]);
checar("Pedir a conta de novo", "POST /api/cliente/pagamento.php", 409, $r["status"]);
$r = req("cliente", "POST", "api/pedidos.php", ["itens" => [["produto_id" => 1, "quantidade" => 1]]], ["csrf" => $csrf["cliente"]]);
checar("Novo pedido depois de pedir a conta", "POST /api/pedidos.php", 409, $r["status"]);

$r = req("admin", "GET", "api/admin/caixa.php");
checar("Caixa lista a mesa 4", "GET /api/admin/caixa.php", "contém", in_array(4, array_column($r["json"] ?? [], "mesa")) ? "contém" : "não contém");
$r = req("admin", "POST", "api/admin/caixa.php", ["mesa_id" => "x"], ["csrf" => $csrf["admin"]]);
checar("mesa_id inválido", "POST /api/admin/caixa.php", 400, $r["status"]);
$r = req("admin", "POST", "api/admin/caixa.php", ["mesa_id" => 999], ["csrf" => $csrf["admin"]]);
checar("Mesa inexistente", "POST /api/admin/caixa.php", 404, $r["status"]);
$r = req("admin", "POST", "api/admin/caixa.php", ["mesa_id" => 2], ["csrf" => $csrf["admin"]]);
checar("Fechar mesa que não pediu a conta", "POST /api/admin/caixa.php", 409, $r["status"]);
$r = req("admin", "POST", "api/admin/caixa.php", ["mesa_id" => 4], ["csrf" => $csrf["admin"]]);
checar("Fechar a conta da mesa 4", "POST /api/admin/caixa.php", 200, $r["status"], null, "total R$ " . ($r["json"]["total"] ?? "-"));
checar("Mesa volta a livre", "banco: mesas.status", "livre", q("SELECT status FROM mesas WHERE id = 4")->fetchColumn());
checar("Pedidos da mesa ficam finalizado", "banco: pedidos.status", "0 em aberto",
    q("SELECT COUNT(*) FROM pedidos WHERE mesa_id = 4 AND status <> 'finalizado'")->fetchColumn() . " em aberto");
$r = req("admin", "GET", "api/admin/dashboard.php");
$curdate = (string) q("SELECT CURDATE()")->fetchColumn();
checar("Dashboard: \"hoje\" igual à data do MySQL (fuso)", "GET /api/admin/dashboard.php", "200 / $curdate",
    $r["status"] . " / " . ($r["json"]["data"] ?? "-"), null, "faturamento=" . ($r["json"]["faturamento_hoje"] ?? "-"));
$r = req("admin", "GET", "api/admin/pedidos.php?status=xyz");
checar("Filtro de status inválido", "GET /api/admin/pedidos.php?status=xyz", 400, $r["status"]);
$r = req("admin", "GET", "api/admin/pedidos.php?status=finalizado");
checar("Filtro de status válido", "GET /api/admin/pedidos.php?status=finalizado", 200, $r["status"]);

/* ===================== 5. ADMIN: PRODUTOS E USUÁRIOS ===================== */
grupo("5. Admin: cardápio e usuários");
$t = $csrf["admin"];
$r = req("admin", "POST", "api/admin/produtos.php", ["nome" => "", "preco" => -1, "categoria" => ""], ["csrf" => $t]);
checar("Produto inválido", "POST /api/admin/produtos.php", 400, $r["status"]);
$r = req("admin", "POST", "api/admin/produtos.php", ["nome" => "Produto Validação", "preco" => 7.5, "categoria" => "Teste"], ["csrf" => $t]);
checar("Criar produto", "POST /api/admin/produtos.php", 201, $r["status"]);
$pid = $r["json"]["id"] ?? 0;
$r = req("admin", "PUT", "api/admin/produtos.php", ["id" => $pid, "preco" => 8, "disponivel" => false], ["csrf" => $t]);
checar("Editar produto", "PUT /api/admin/produtos.php", 200, $r["status"]);
checar("Edição gravada no banco", "banco: produtos", "8.00/0", implode("/", q("SELECT preco, disponivel FROM produtos WHERE id = ?", [$pid])->fetch(PDO::FETCH_NUM)));
$r = req("admin", "PUT", "api/admin/produtos.php", ["id" => 999999, "preco" => 8], ["csrf" => $t]);
checar("Editar produto inexistente", "PUT /api/admin/produtos.php", 404, $r["status"]);
$r = req("admin", "DELETE", "api/admin/produtos.php?id=1", null, ["csrf" => $t]);
checar("Excluir produto que já está em pedidos", "DELETE /api/admin/produtos.php?id=1", 409, $r["status"]);
$r = req("admin", "DELETE", "api/admin/produtos.php?id=$pid", null, ["csrf" => $t]);
checar("Excluir produto sem pedidos", "DELETE /api/admin/produtos.php?id=$pid", 200, $r["status"]);

$r = req("admin", "POST", "api/admin/usuarios.php", ["nome" => "Fulano", "email" => "f@f.com", "senha" => "123456", "perfil" => "chefe"], ["csrf" => $t]);
checar("Criar usuário com perfil inválido", "POST /api/admin/usuarios.php", 400, $r["status"]);
$r = req("admin", "POST", "api/admin/usuarios.php", ["nome" => "Fulano", "email" => "cozinha@restaurante.com", "senha" => "123456", "perfil" => "cozinha"], ["csrf" => $t]);
checar("Criar usuário com e-mail repetido", "POST /api/admin/usuarios.php", 409, $r["status"]);
$emailEquipe = "equipe_" . time() . "@teste.com";
$r = req("admin", "POST", "api/admin/usuarios.php", ["nome" => "Garçom Novo", "email" => $emailEquipe, "senha" => "Garcom@456", "perfil" => "garcom"], ["csrf" => $t]);
checar("Admin cria conta de garçom", "POST /api/admin/usuarios.php", 201, $r["status"]);
$uid = $r["json"]["id"] ?? 0;
checar("Senha do usuário criado pelo admin também é hash", "banco: usuarios.senha_hash", "\$2y\$...",
    str_starts_with((string) q("SELECT senha_hash FROM usuarios WHERE id = ?", [$uid])->fetchColumn(), '$2y$') ? "\$2y\$..." : "texto");
$r = req("admin", "PUT", "api/admin/usuarios.php", ["id" => $uid, "perfil" => "cozinha"], ["csrf" => $t]);
checar("Mudar perfil", "PUT /api/admin/usuarios.php", 200, $r["status"]);
$r = req("admin", "PUT", "api/admin/usuarios.php", ["id" => 1, "perfil" => "cliente"], ["csrf" => $t]);
checar("Admin rebaixar a si mesmo", "PUT /api/admin/usuarios.php", 409, $r["status"]);
$r = req("admin", "DELETE", "api/admin/usuarios.php?id=1", null, ["csrf" => $t]);
checar("Admin excluir a si mesmo", "DELETE /api/admin/usuarios.php?id=1", 409, $r["status"]);
$r = req("admin", "DELETE", "api/admin/usuarios.php?id=4", null, ["csrf" => $t]);
checar("Excluir cliente que tem pedidos", "DELETE /api/admin/usuarios.php?id=4", 409, $r["status"]);
$r = req("admin", "DELETE", "api/admin/usuarios.php?id=999999", null, ["csrf" => $t]);
checar("Excluir usuário inexistente", "DELETE /api/admin/usuarios.php?id=999999", 404, $r["status"]);
$r = req("admin", "DELETE", "api/admin/usuarios.php?id=$uid", null, ["csrf" => $t]);
checar("Excluir usuário sem pedidos", "DELETE /api/admin/usuarios.php?id=$uid", 200, $r["status"]);
$r = req("admin", "GET", "api/admin/usuarios.php");
checar("Listagem de usuários não expõe hash", "GET /api/admin/usuarios.php", "sem hash", str_contains($r["raw"], '$2y$') ? "com hash" : "sem hash");

/* ===================== 6. PÁGINAS HTML (EXERCÍCIO 1) ===================== */
grupo("6. Páginas protegidas (Exercício 1)");
foreach (["painel.php", "produtos.php", "cliente.php", "cozinha.php", "garcom.php", "admin.php"] as $pg) {
    $r = req("anon", "GET", $pg);
    checar("Sem login vai para o login", "GET /$pg", "302 → login.php", $r["status"] . " → " . basename($r["loc"]));
}
$r = req("cozinha", "GET", "cliente.php");
checar("Cozinha abrindo a tela do cliente", "GET /cliente.php", 403, $r["status"]);
$r = req("cliente", "GET", "admin.php");
checar("Cliente abrindo a tela do admin", "GET /admin.php", 403, $r["status"]);
$r = req("admin", "GET", "cozinha.php");
checar("Admin abre qualquer tela", "GET /cozinha.php", 200, $r["status"]);
$r = req("cliente", "GET", "painel.php");
checar("Painel leva o cliente para a tela dele", "GET /painel.php", "cliente.php", basename($r["loc"]));
$r = req("cliente", "GET", "produtos.php");
checar("Listagem do tema para qualquer logado", "GET /produtos.php", 200, $r["status"]);

$r = req("cliente", "POST", "produto_excluir.php", ["id" => 2, "csrf" => $csrf["cliente"]], ["form" => true]);
checar("Cliente excluindo produto (com token válido)", "POST /produto_excluir.php", 403, $r["status"]);
checar("O 403 é de perfil, não de token", "POST /produto_excluir.php", "Acesso negado",
    str_contains($r["raw"], "Acesso negado") ? "Acesso negado" : (str_contains($r["raw"], "expirou") ? "Página expirou (CSRF)" : "outro"));
$r = req("admin", "GET", "produto_excluir.php");
checar("Excluir por GET", "GET /produto_excluir.php", 405, $r["status"]);
$r = req("admin", "POST", "produto_excluir.php", ["id" => 1, "csrf" => $csrf["admin"]], ["form" => true]);
checar("Admin exclui produto em uso", "POST /produto_excluir.php", "302 → em_uso", $r["status"] . " → " . (parse_url($r["loc"], PHP_URL_QUERY) ? explode("=", parse_url($r["loc"], PHP_URL_QUERY))[1] : "-"));
$r = req("admin", "POST", "produto_excluir.php", ["id" => 9, "csrf" => $csrf["admin"]], ["form" => true]);
checar("Admin exclui produto livre (Café expresso)", "POST /produto_excluir.php", "302 → excluido", $r["status"] . " → " . (parse_url($r["loc"], PHP_URL_QUERY) ? explode("=", parse_url($r["loc"], PHP_URL_QUERY))[1] : "-"));

/* ===================== 7. SEGURANÇA ===================== */
grupo("7. Segurança (Aula 15)");
$r = req("cliente", "GET", "api/me.php");
foreach (["Content-Security-Policy", "X-Content-Type-Options: nosniff", "X-Frame-Options: DENY"] as $hdr) {
    checar("Cabeçalho $hdr", "GET /api/me.php", "presente", stripos($r["cab"], $hdr) !== false ? "presente" : "ausente");
}
checar("Sem X-Powered-By", "GET /api/me.php", "ausente", stripos($r["cab"], "X-Powered-By") !== false ? "presente" : "ausente");
$r = req("visitante_novo", "GET", "login.php");
checar("Cookie de sessão HttpOnly e SameSite", "GET /login.php", "HttpOnly+SameSite",
    (stripos($r["cab"], "HttpOnly") !== false && stripos($r["cab"], "SameSite=Lax") !== false) ? "HttpOnly+SameSite" : "faltando");
$r = req("cliente", "PUT", "api/cozinha/status.php", ["pedido_id" => 1, "status" => "em_preparo"], ["csrf" => str_repeat("a", 64)]);
checar("Token CSRF falso", "PUT /api/cozinha/status.php", 403, $r["status"]);

$xssEmail = "xss_" . time() . "@teste.com";
tok("xss");
$r = req("xss", "POST", "api/cadastro.php", ["nome" => "<script>alert(1)</script>", "email" => $xssEmail, "senha" => "Senha@123"], ["csrf" => $csrf["xss"]]);
$r = req("xss", "GET", "cliente.php");
checar("XSS no nome: página escapa o texto", "GET /cliente.php", "escapado",
    str_contains($r["raw"], "<script>alert(1)</script>") ? "NÃO escapado" : (str_contains($r["raw"], "&lt;script&gt;") ? "escapado" : "nome não exibido"));

$emailBF = "bruteforce_" . time() . "@teste.com";
tok("bf");
$st = [];
for ($i = 1; $i <= 6; $i++) {
    $r = req("bf", "POST", "api/login.php", ["email" => $emailBF, "senha" => "x$i"], ["csrf" => $csrf["bf"]]);
    $st[] = $r["status"];
}
checar("Força bruta: 5 erros e a 6ª tentativa bloqueia", "POST /api/login.php", "401×5, 429", implode(",", $st) === "401,401,401,401,401,429" ? "401×5, 429" : implode(",", $st));
checar("429 envia Retry-After", "POST /api/login.php", "presente", stripos($r["cab"], "Retry-After") !== false ? "presente" : "ausente");

// Banco fora (cópia com conexao.php apontando para banco inexistente)
if ($BASE_FORA) {
    $csrfFora = req("fora", "GET", "api/csrf.php", null, ["base" => $BASE_FORA])["json"]["csrf"] ?? "";
    $r = req("fora", "POST", "api/login.php", ["email" => "admin@restaurante.com", "senha" => "Admin@123"], ["csrf" => $csrfFora, "base" => $BASE_FORA]);
    checar("Banco fora do ar", "POST /api/login.php", 500, $r["status"]);
    checar("Erro 500 sem detalhe técnico", "POST /api/login.php", "genérico",
        (str_contains($r["raw"], "SQLSTATE") || str_contains($r["raw"], "PDO")) ? "vaza detalhe" : "genérico", null, $r["raw"]);
}

/* ===================== 8. LOGOUT ===================== */
grupo("8. Logout");
$r = req("garcom", "GET", "logout.php");
checar("Logout por GET só mostra confirmação", "GET /logout.php", 200, $r["status"]);
$r = req("garcom", "GET", "api/me.php");
checar("Continua logado depois do GET", "GET /api/me.php", 200, $r["status"]);
$r = req("garcom", "POST", "api/logout.php");
checar("Logout sem token", "POST /api/logout.php", 403, $r["status"]);
$sessAntiga = $sess["garcom"];
$r = req("garcom", "POST", "api/logout.php", null, ["csrf" => $csrf["garcom"]]);
checar("Logout com token", "POST /api/logout.php", 200, $r["status"]);
checar("Arquivo da sessão apagado no servidor", "C:\\xampp\\tmp\\sess_<id>", "apagado", is_file("$PASTA_SESSAO/sess_$sessAntiga") ? "existe" : "apagado");
$sess["garcom"] = $sessAntiga;
$r = req("garcom", "GET", "api/me.php");
checar("Reusar o cookie antigo depois do logout", "GET /api/me.php", 401, $r["status"]);
$r = req("garcom", "GET", "garcom.php");
checar("Voltar para a página protegida depois do logout", "GET /garcom.php", "302 → login.php", $r["status"] . " → " . basename($r["loc"]));

/* ===================== 9. FOTOS DO CARDÁPIO ===================== */
grupo("9. Fotos do cardápio (admin)");
$r = req("cliente", "GET", "api/cardapio.php");
$comFoto = count(array_filter($r["json"] ?? [], fn($p) => !empty($p["imagem"])));
checar("Cardápio do cliente traz a foto dos produtos", "GET /api/cardapio.php", "com foto", $comFoto > 0 ? "com foto" : "sem foto", null, "$comFoto produto(s) com foto");
$xb = array_values(array_filter($r["json"] ?? [], fn($p) => $p["id"] === 1))[0]["imagem"] ?? "";
$r = req("anon", "GET", $xb);
checar("Arquivo da foto abre pelo servidor", "GET /$xb", 200, $r["status"]);

$fotoBoa = new CURLFile(__DIR__ . "/../assets/img/produtos/coxinha.jpg", "image/jpeg", "foto.jpg");
$falso = tempnam(sys_get_temp_dir(), "pj");
file_put_contents($falso, 'GIF89a<?php echo "invasao"; ?>');
$grande = tempnam(sys_get_temp_dir(), "pj");
file_put_contents($grande, random_bytes(3200000));
$up = fn(string $ator, array $campos, ?string $token) =>
    req($ator, "POST", "api/admin/produto_imagem.php", $campos, ["multipart" => true] + ($token ? ["csrf" => $token] : []));

$r = $up("cliente", ["id" => 1, "imagem" => $fotoBoa], $csrf["cliente"]);
checar("Cliente tentando enviar foto", "POST /api/admin/produto_imagem.php", 403, $r["status"]);
$r = $up("admin", ["id" => 1, "imagem" => $fotoBoa], null);
checar("Envio de foto sem token CSRF", "POST /api/admin/produto_imagem.php", 403, $r["status"]);
$r = $up("admin", ["id" => 1], $csrf["admin"]);
checar("Envio sem arquivo", "POST /api/admin/produto_imagem.php", 400, $r["status"]);
$r = $up("admin", ["id" => 999999, "imagem" => $fotoBoa], $csrf["admin"]);
checar("Foto para produto inexistente", "POST /api/admin/produto_imagem.php", 404, $r["status"]);
$r = $up("admin", ["id" => 1, "imagem" => new CURLFile($falso, "image/jpeg", "foto.jpg")], $csrf["admin"]);
checar("Arquivo falso (.jpg com código PHP)", "POST /api/admin/produto_imagem.php", 400, $r["status"], null, $r["json"]["erro"] ?? "");
$r = $up("admin", ["id" => 1, "imagem" => new CURLFile($grande, "image/jpeg", "grande.jpg")], $csrf["admin"]);
checar("Foto maior que 3 MB", "POST /api/admin/produto_imagem.php", 400, $r["status"]);
$r = $up("admin", ["id" => 1, "imagem" => $fotoBoa], $csrf["admin"]);
$foto1 = $r["json"]["imagem"] ?? "";
checar("Admin troca a foto do X-Burguer", "POST /api/admin/produto_imagem.php", 200, $r["status"], null, $foto1);
checar("Nome da foto gerado pelo servidor", "banco: produtos.imagem", "p1-xxxxxxxx.jpg",
    preg_match('#^assets/img/produtos/p1-[a-f0-9]{8}\.jpg$#', $foto1) ? "p1-xxxxxxxx.jpg" : $foto1);
checar("Foto nova abre pelo servidor", "GET /$foto1", 200, req("anon", "GET", $foto1)["status"]);
checar("Foto original do cardápio não é apagada", "GET /$xb", 200, req("anon", "GET", $xb)["status"]);
$r = $up("admin", ["id" => 1, "imagem" => $fotoBoa], $csrf["admin"]);
$foto2 = $r["json"]["imagem"] ?? "";
checar("Trocar de novo apaga a foto enviada antes", "GET /$foto1", 404, req("anon", "GET", $foto1)["status"]);
$r = $up("admin", ["id" => 1, "remover" => "1"], $csrf["admin"]);
checar("Remover a foto", "POST /api/admin/produto_imagem.php", "200 / NULL", $r["status"] . " / " .
    (is_array($r["json"]) && array_key_exists("imagem", $r["json"]) ? var_export($r["json"]["imagem"], true) : "campo ausente"));
checar("Arquivo removido do servidor", "GET /$foto2", 404, req("anon", "GET", $foto2)["status"]);
q("UPDATE produtos SET imagem = '../../config/conexao.php' WHERE id = 2");
$r = req("cliente", "GET", "api/cardapio.php");
$x2 = array_values(array_filter($r["json"] ?? [], fn($p) => $p["id"] === 2))[0] ?? [];
checar("Caminho de imagem malicioso no banco é ignorado", "GET /api/cardapio.php", "NULL",
    array_key_exists("imagem", $x2) ? var_export($x2["imagem"], true) : "campo ausente");
q("UPDATE produtos SET imagem = 'assets/img/produtos/x-bacon.jpg' WHERE id = 2");
q("UPDATE produtos SET imagem = 'assets/img/produtos/x-burguer.jpg' WHERE id = 1");
@unlink($falso); @unlink($grande);

/* ===================== 10. QR CODES DAS MESAS ===================== */
grupo("10. QR Codes das mesas");
$r = req("anon", "GET", "qrcodes.php");
checar("Sem login vai para o login", "GET /qrcodes.php", "302 → login.php", $r["status"] . " → " . basename($r["loc"]));
$r = req("cliente", "GET", "qrcodes.php");
checar("Cliente abrindo os QR Codes", "GET /qrcodes.php", 403, $r["status"]);
$r = req("admin", "GET", "qrcodes.php");
checar("Admin abre os QR Codes", "GET /qrcodes.php", 200, $r["status"]);
preg_match('/data-endereco="([^"]*)"/', $r["raw"], $m);
$end = $m[1] ?? "";
checar("Endereço da rede descoberto sozinho", "qrcodes.php", "preenchido", $end !== "" ? "preenchido" : "vazio", null, $end);
checar("Endereço não é a rede virtual do WSL/Hyper-V", "qrcodes.php", "não é 172.16–31",
    preg_match('#//172\.(1[6-9]|2\d|3[01])\.#', $end) ? "é virtual" : "não é 172.16–31");
$nMesas = (int) q("SELECT COUNT(*) FROM mesas")->fetchColumn();
checar("Um QR Code por mesa do banco", "qrcodes.php", $nMesas, substr_count($r["raw"], "data-token="));
checar("Scripts com versão no endereço (sem cache velho)", "qrcodes.php", "?v=", str_contains($r["raw"], "qrcodes.js?v=") ? "?v=" : "sem versão");
checar("Biblioteca de QR Code servida localmente", "GET /assets/vendor/qrcode.min.js", 200, req("anon", "GET", "assets/vendor/qrcode.min.js")["status"]);

/* ===================== 11. PEDIDO REPETIDO ===================== */
grupo("11. Pedido repetido (proteção no servidor)");
$corpoA = ["itens" => [["produto_id" => 6, "quantidade" => 1], ["produto_id" => 5, "quantidade" => 2, "observacao" => "bem quente"]]];
$corpoB = ["itens" => [["produto_id" => 5, "quantidade" => 2, "observacao" => "bem quente"], ["produto_id" => 6, "quantidade" => 1]]];
$r = req("cliente", "POST", "api/pedidos.php", $corpoA, ["csrf" => $csrf["cliente"]]);
checar("Primeiro envio", "POST /api/pedidos.php", 201, $r["status"]);
$r = req("cliente", "POST", "api/pedidos.php", $corpoB, ["csrf" => $csrf["cliente"]]);
checar("Mesmo pedido de novo (itens em outra ordem)", "POST /api/pedidos.php", 409, $r["status"], null, $r["json"]["erro"] ?? "");
$r = req("cliente", "POST", "api/pedidos.php", ["itens" => [["produto_id" => 6, "quantidade" => 2]]], ["csrf" => $csrf["cliente"]]);
checar("Pedido diferente logo em seguida é aceito", "POST /api/pedidos.php", 201, $r["status"]);

/* ===================== saída ===================== */
$md = "| # | Grupo | Cenário | Rota | Esperado | Obtido | Resultado |\n|---|---|---|---|---|---|---|\n";
$detalhes = "";
foreach ($linhas as $i => [$g, $c, $rt, $e, $o, $res, $obs]) {
    $md .= "| " . ($i + 1) . " | $g | $c | `$rt` | $e | $o | " . ($res === "OK" ? "✅" : "❌") . " |\n";
    if ($obs !== "") $detalhes .= "- **#" . ($i + 1) . " $c:** " . str_replace("\n", " ", $obs) . "\n";
}
$resumo = "Total: " . ($ok + $falhas) . " cenários · OK: $ok · Falharam: $falhas";
file_put_contents(__DIR__ . "/resultados.md", "$resumo\n\n$md\n\nObservações:\n$detalhes");
echo "$resumo\n\n";
foreach ($linhas as $i => $l) if ($l[5] !== "OK") echo "FALHOU #" . ($i + 1) . ": {$l[1]} [{$l[2]}] esperado={$l[3]} obtido={$l[4]} {$l[6]}\n";
echo "\n$detalhes";
