<?php
// teste/index.php — Página de testes e validações da API (somente desenvolvimento).
// Só abre a partir da própria máquina (localhost). Remova esta pasta antes de publicar.
$local = in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true);
if (!$local) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Testes da API · PedirJá</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    .conteudo { max-width: 1200px; }
    .layout { display: grid; grid-template-columns: 280px 1fr; gap: 16px; align-items: start; }
    @media (max-width: 860px) { .layout { grid-template-columns: 1fr; } }
    .lateral .cartao { padding: 16px; }
    .lateral h2, .cartao h2 { font-size: 1rem; margin: 0 0 10px; }
    .sessao-atual { font-size: .9rem; background: var(--fundo); border-radius: 8px; padding: 10px; margin-bottom: 10px; }
    .sessao-atual strong { display: block; }
    .botoes { display: flex; flex-wrap: wrap; gap: 6px; }
    .botoes .botao { padding: 6px 10px; font-size: .85rem; }
    .abas { display: flex; gap: 4px; border-bottom: 1px solid var(--borda); margin-bottom: 16px; }
    .aba { background: none; border: 0; padding: 10px 14px; font: inherit; font-weight: 600; color: var(--suave);
           cursor: pointer; border-bottom: 3px solid transparent; }
    .aba[aria-selected="true"] { color: var(--primaria-escura); border-bottom-color: var(--primaria); }
    .painel[hidden] { display: none; }
    .resumo { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; }
    .contador { padding: 6px 12px; border-radius: 999px; font-weight: 700; font-size: .9rem; background: var(--fundo); }
    .contador.ok { background: #e3f6e8; color: #17603a; }
    .contador.falha { background: var(--erro-fundo); color: var(--erro-texto); }
    .resultados td { font-size: .9rem; padding: 8px 6px; }
    .resultados tr.secao td { background: var(--fundo); font-weight: 700; color: var(--suave); font-size: .8rem;
                              text-transform: uppercase; letter-spacing: .04em; }
    .resultados tr.linha { cursor: pointer; }
    .resultados tr.linha:hover { background: #fbf8f4; }
    .selo { display: inline-block; min-width: 44px; text-align: center; padding: 2px 8px; border-radius: 6px;
            font-weight: 700; font-size: .8rem; }
    .selo.ok { background: #e3f6e8; color: #17603a; }
    .selo.falha { background: var(--erro-fundo); color: var(--erro-texto); }
    .selo.rodando { background: var(--aviso-fundo); color: var(--aviso-texto); }
    .metodo { font-family: ui-monospace, Consolas, monospace; font-size: .8rem; font-weight: 700; }
    .url { font-family: ui-monospace, Consolas, monospace; font-size: .8rem; color: var(--suave); word-break: break-all; }
    tr.detalhe td { background: #fbf8f4; }
    pre { margin: 0; padding: 12px; background: #1f1b17; color: #f3ede6; border-radius: 8px; overflow: auto;
          font-size: .8rem; max-height: 420px; white-space: pre-wrap; word-break: break-word; }
    .motivo { color: var(--erro-texto); font-size: .85rem; margin-bottom: 6px; }
    .form-req { display: grid; grid-template-columns: 110px 1fr auto; gap: 8px; margin-bottom: 8px; }
    @media (max-width: 600px) { .form-req { grid-template-columns: 1fr; } }
    select, textarea { font: inherit; padding: 8px 10px; border: 1px solid var(--borda); border-radius: 8px; width: 100%; background: #fff; }
    textarea { font-family: ui-monospace, Consolas, monospace; font-size: .85rem; min-height: 140px; }
    .catalogo { display: grid; gap: 6px; max-height: 520px; overflow: auto; }
    .item-cat { text-align: left; background: #fff; border: 1px solid var(--borda); border-radius: 8px; padding: 8px 10px;
                cursor: pointer; font: inherit; }
    .item-cat:hover, .item-cat.ativo { border-color: var(--primaria); }
    .item-cat small { display: block; color: var(--suave); }
    .grade-manual { display: grid; grid-template-columns: 300px 1fr; gap: 16px; }
    @media (max-width: 860px) { .grade-manual { grid-template-columns: 1fr; } }
    .status-resp { font-weight: 700; margin: 10px 0 6px; }
    .dica { color: var(--suave); font-size: .85rem; }
</style>
</head>
<body>
<header class="topo">
    <a class="marca" href="../painel.php">PedirJá</a>
    <nav><span class="quem">Página de testes · só em localhost</span></nav>
</header>

<main class="conteudo">
<div class="layout">

    <!-- Lateral: sessão atual, login rápido e mesa -->
    <aside class="lateral">
        <section class="cartao">
            <h2>Sessão deste navegador</h2>
            <div class="sessao-atual" id="sessao">Verificando…</div>
            <div class="botoes" id="logins"></div>
            <div class="botoes" style="margin-top:8px">
                <button class="botao botao-sec" type="button" id="btn-logout">Sair</button>
            </div>
        </section>
        <section class="cartao">
            <h2>Mesa (QR Code)</h2>
            <div class="botoes" id="mesas"></div>
            <p class="dica">Simula escanear o QR da mesa: grava a mesa na sessão.</p>
        </section>
        <section class="cartao">
            <h2>Atenção</h2>
            <p class="dica">Esta página usa a mesma sessão do navegador. O roteiro automático faz login e logout
                várias vezes e cria pedidos reais no banco (os usuários e produtos de teste são apagados no final).</p>
        </section>
    </aside>

    <section>
        <div class="abas" role="tablist">
            <button class="aba" role="tab" aria-selected="true" data-aba="roteiro">Roteiro automático</button>
            <button class="aba" role="tab" aria-selected="false" data-aba="manual">Requisição manual</button>
        </div>

        <!-- Roteiro automático -->
        <div class="painel" id="painel-roteiro">
            <section class="cartao">
                <div class="resumo">
                    <button class="botao" type="button" id="btn-rodar">Rodar todos os testes</button>
                    <span class="contador" id="c-total">0 testes</span>
                    <span class="contador ok" id="c-ok">0 passaram</span>
                    <span class="contador falha" id="c-falha">0 falharam</span>
                    <span class="dica" id="c-tempo"></span>
                    <label class="dica" style="margin-left:auto;display:flex;gap:6px;align-items:center;font-weight:400">
                        <input type="checkbox" id="so-falhas" style="width:auto;margin:0"> só falhas
                    </label>
                </div>
                <p class="dica" id="roteiro-vazio">Clique em <strong>Rodar todos os testes</strong> para executar o fluxo
                    completo: autenticação, cliente, cozinha, garçom, conta e admin. Clique numa linha para ver a resposta.</p>
                <div class="tabela-rolagem">
                    <table class="resultados">
                        <thead><tr><th>#</th><th>Cenário</th><th>Requisição</th><th>Esperado</th><th>Obtido</th><th></th></tr></thead>
                        <tbody id="tbody-resultados"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Requisição manual -->
        <div class="painel" id="painel-manual" hidden>
            <div class="grade-manual">
                <section class="cartao">
                    <h2>Endpoints</h2>
                    <div class="catalogo" id="catalogo"></div>
                </section>
                <section class="cartao">
                    <h2 id="manual-titulo">Requisição</h2>
                    <p class="dica" id="manual-desc">Escolha um endpoint ao lado ou monte a requisição.</p>
                    <div class="form-req">
                        <select id="m-metodo" aria-label="Método">
                            <option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option>
                        </select>
                        <input id="m-url" aria-label="URL" value="api/me.php" style="margin:0">
                        <button class="botao" type="button" id="btn-enviar">Enviar</button>
                    </div>
                    <label for="m-corpo" class="dica">Corpo (JSON)</label>
                    <textarea id="m-corpo" spellcheck="false"></textarea>
                    <div class="status-resp" id="m-status"></div>
                    <pre id="m-resposta">—</pre>
                </section>
            </div>
        </div>
    </section>
</div>
</main>

<script>
"use strict";
const BASE = "../";   // raiz do projeto em relação a /teste/

// Contas do banco.sql (as mesmas do README)
const CONTAS = {
    cliente: { email: "cliente@restaurante.com", senha: "Cliente@123" },
    cozinha: { email: "cozinha@restaurante.com", senha: "Cozinha@123" },
    garcom:  { email: "garcom@restaurante.com",  senha: "Garcom@123" },
    admin:   { email: "admin@restaurante.com",   senha: "Admin@123" },
};
const MESAS = { 1: "mesa-01-a7f3c9", 2: "mesa-02-b81d4e", 3: "mesa-03-c92e5f", 4: "mesa-04-d0a6b1" };
const MESA_TESTE = 4;

/* ---------- HTTP ---------- */

// Busca o token CSRF da sessão atual (muda a cada login/logout)
async function tokenCsrf() {
    const r = await fetch(BASE + "api/csrf.php", { credentials: "same-origin" });
    return (await r.json()).csrf;
}

// op: { semToken, token, form } — para testar a proteção CSRF e formulários HTML
async function chamar(metodo, url, corpo, op = {}) {
    const req = { method: metodo, credentials: "same-origin", headers: {} };
    if (metodo !== "GET" && !op.semToken) {
        req.headers["X-CSRF-Token"] = op.token ?? await tokenCsrf();
    }
    if (op.form) {
        req.headers["Content-Type"] = "application/x-www-form-urlencoded";
        req.body = new URLSearchParams(corpo || {}).toString();
    } else if (corpo !== undefined && corpo !== null && corpo !== "") {
        req.headers["Content-Type"] = "application/json";
        req.body = typeof corpo === "string" ? corpo : JSON.stringify(corpo);
    }
    const inicio = performance.now();
    const res = await fetch(BASE + url, req);
    const texto = await res.text();
    let json = null;
    try { json = JSON.parse(texto); } catch (_) { /* resposta HTML */ }
    return { status: res.status, json, texto, url: res.url, redirected: res.redirected, headers: res.headers,
             ms: Math.round(performance.now() - inicio) };
}

function formatar(r) {
    return r.json !== null ? JSON.stringify(r.json, null, 2) : r.texto.slice(0, 3000);
}

/* ---------- Sessão (lateral) ---------- */

async function atualizarSessao() {
    const el = document.getElementById("sessao");
    const r = await chamar("GET", "api/me.php");
    if (r.status === 200) {
        const u = r.json.usuario;
        el.innerHTML = "";
        const s = document.createElement("strong");
        s.textContent = `${u.nome} (${u.perfil})`;
        el.append(s, document.createTextNode(r.json.mesa ? `Mesa ${r.json.mesa}` : "Sem mesa na sessão"));
    } else {
        el.textContent = "Ninguém logado (401)";
    }
}

for (const perfil of Object.keys(CONTAS)) {
    const b = document.createElement("button");
    b.className = "botao";
    b.type = "button";
    b.textContent = perfil;
    b.title = `Entrar como ${CONTAS[perfil].email}`;
    b.onclick = async () => { await chamar("POST", "api/login.php", CONTAS[perfil]); atualizarSessao(); };
    document.getElementById("logins").append(b);
}
for (const [num, token] of Object.entries(MESAS)) {
    const b = document.createElement("button");
    b.className = "botao botao-sec";
    b.type = "button";
    b.textContent = `Mesa ${num}`;
    b.onclick = async () => { await chamar("GET", `mesa.php?t=${token}`); atualizarSessao(); };
    document.getElementById("mesas").append(b);
}
document.getElementById("btn-logout").onclick = async () => { await chamar("POST", "api/logout.php"); atualizarSessao(); };

/* ---------- Abas ---------- */

document.querySelectorAll(".aba").forEach(aba => aba.addEventListener("click", () => {
    document.querySelectorAll(".aba").forEach(a => a.setAttribute("aria-selected", a === aba));
    document.getElementById("painel-roteiro").hidden = aba.dataset.aba !== "roteiro";
    document.getElementById("painel-manual").hidden = aba.dataset.aba !== "manual";
}));

/* ---------- Roteiro automático ---------- */
// Cada passo: { s: seção } ou { nome, m: método, u: url (ou função), b: corpo (ou função),
//   espera: status ou [status...], v: (json, ctx, r) => mensagem de erro | null, salvar: (json, ctx) => void }
// ctx guarda ids criados durante o roteiro (pedido, produtos, usuários).

const sufixo = () => Date.now().toString(36);
const XSS = "<script>alert(1)<\/script>";   // "<\/" evita fechar o <script> desta página
const login = perfil => ({ nome: `login ${perfil}`, m: "POST", u: "api/login.php", b: CONTAS[perfil], espera: 200,
    salvar: (j, c) => { c.ids[perfil] = j.usuario.id; } });
const logout = () => ({ nome: "logout", m: "POST", u: "api/logout.php", espera: [200, 401] });
const mesa = (n, espera = 200) => ({ nome: `QR Code da mesa ${n}`, m: "GET", u: `mesa.php?t=${MESAS[n]}`, espera });
const contem = (lista, id) => Array.isArray(lista) && lista.some(p => p.id === id);

const ROTEIRO = [
    { s: "Preparação" },
    logout(),
    login("admin"),
    { nome: "libera a mesa de teste se ficou presa de outra execução", m: "POST", u: "api/admin/caixa.php",
      b: { mesa_id: MESA_TESTE }, espera: [200, 409] },
    { nome: "cria produto indisponível de teste", m: "POST", u: "api/admin/produtos.php",
      b: () => ({ nome: "Teste indisponível " + sufixo(), preco: 1, categoria: "Teste", disponivel: false }),
      espera: 201, salvar: (j, c) => { c.prodIndisp = j.id; } },
    logout(),

    { s: "Autenticação" },
    { nome: "quem sou eu sem login", m: "GET", u: "api/me.php", espera: 401 },
    { nome: "cardápio sem login", m: "GET", u: "api/cardapio.php", espera: 401 },
    { nome: "página protegida sem login volta para o login", m: "GET", u: "painel.php", espera: 200,
      v: (j, c, r) => r.url.endsWith("/login.php") ? null : "não redirecionou para login.php" },
    { nome: "login sem campos", m: "POST", u: "api/login.php", b: {}, espera: 400 },
    { nome: "login com JSON quebrado", m: "POST", u: "api/login.php", b: "{oops", espera: 400 },
    { nome: "login com método GET", m: "GET", u: "api/login.php", espera: 405 },
    { nome: "login com senha errada (mensagem genérica)", m: "POST", u: "api/login.php",
      b: { email: CONTAS.admin.email, senha: "errada" }, espera: 401,
      v: j => j.erro === "E-mail ou senha inválidos." ? null : "mensagem não é genérica" },
    { nome: "login com e-mail inexistente (mesma mensagem)", m: "POST", u: "api/login.php",
      b: { email: "ninguem@teste.com", senha: "errada" }, espera: 401,
      v: j => j.erro === "E-mail ou senha inválidos." ? null : "mensagem revela se o e-mail existe" },
    { nome: "cadastro com dados inválidos", m: "POST", u: "api/cadastro.php",
      b: { nome: "X", email: "nada", senha: "1" }, espera: 400 },
    { nome: "cadastro com e-mail já usado", m: "POST", u: "api/cadastro.php",
      b: { nome: "Outro Cliente", email: CONTAS.cliente.email, senha: "123456" }, espera: 409 },
    { nome: "cadastro novo com nome malicioso (já entra logado)", m: "POST", u: "api/cadastro.php",
      b: () => ({ nome: XSS, email: `auto_${sufixo()}@teste.com`, senha: "123456", perfil: "admin" }),
      espera: 201, salvar: (j, c) => { c.cadastrado = j.usuario.id; },
      v: j => j.usuario.perfil === "cliente" ? null : "cadastro público aceitou perfil diferente de cliente" },
    { nome: "quem sou eu depois do cadastro", m: "GET", u: "api/me.php", espera: 200,
      v: j => j.usuario.perfil === "cliente" ? null : "perfil deveria ser cliente" },
    { nome: "XSS: nome com <script> aparece escapado na página", m: "GET", u: "cliente.php", espera: 200,
      v: (j, c, r) => r.texto.includes("<script>alert") ? "o <script> foi para a página sem escape"
          : !r.texto.includes("&lt;script&gt;") ? "nome escapado não encontrado" : null },
    { nome: "logout por GET não desloga (só mostra confirmação)", m: "GET", u: "logout.php", espera: 200 },
    { nome: "continua logado depois do GET", m: "GET", u: "api/me.php", espera: 200 },
    logout(),
    { nome: "quem sou eu depois do logout", m: "GET", u: "api/me.php", espera: 401 },

    { s: "Segurança (Aula 15)" },
    { nome: "token CSRF da sessão", m: "GET", u: "api/csrf.php", espera: 200,
      v: j => /^[0-9a-f]{64}$/.test(j.csrf || "") ? null : "token com formato inesperado" },
    { nome: "login pela API sem token CSRF", m: "POST", u: "api/login.php", b: CONTAS.admin, op: { semToken: true }, espera: 403 },
    { nome: "login pela API com token falso", m: "POST", u: "api/login.php", b: CONTAS.admin, op: { token: "0".repeat(64) }, espera: 403 },
    { nome: "formulário de login sem token CSRF", m: "POST", u: "login.php", b: CONTAS.admin, op: { semToken: true, form: true }, espera: 403,
      v: (j, c, r) => r.texto.includes("expirou") ? null : "não mostrou a página de formulário expirado" },
    { nome: "cabeçalhos de segurança nas páginas", m: "GET", u: "login.php", espera: 200,
      v: (j, c, r) => {
          const csp = r.headers.get("Content-Security-Policy") || "";
          if (!csp.includes("script-src 'self'")) return "Content-Security-Policy ausente";
          if (r.headers.get("X-Frame-Options") !== "DENY") return "X-Frame-Options ausente";
          if (r.headers.get("X-Content-Type-Options") !== "nosniff") return "X-Content-Type-Options ausente";
          if (r.headers.get("X-Powered-By")) return "X-Powered-By expõe a versão do PHP";
          return null;
      } },
    { nome: "cabeçalhos de segurança na API", m: "GET", u: "api/me.php", espera: 401,
      v: (j, c, r) => r.headers.get("X-Content-Type-Options") === "nosniff" ? null : "nosniff ausente" },
    ...Array.from({ length: 5 }, (_, i) => ({
        nome: `força bruta: senha errada ${i + 1}/5`, m: "POST", u: "api/login.php",
        b: () => ({ email: ctx.emailBruto ??= `bruto_${sufixo()}@teste.com`, senha: "errada" + i }), espera: 401 })),
    { nome: "força bruta: 6ª tentativa é bloqueada", m: "POST", u: "api/login.php",
      b: () => ({ email: ctx.emailBruto, senha: "outra" }), espera: 429,
      v: (j, c, r) => r.headers.get("Retry-After") ? null : "sem cabeçalho Retry-After" },
    { nome: "força bruta: outro e-mail do mesmo IP continua liberado", m: "POST", u: "api/login.php",
      b: { email: CONTAS.cliente.email, senha: "errada" }, espera: 401 },

    { s: "Cliente: pedido" },
    login("cliente"),
    { nome: "pedido sem mesa na sessão", m: "POST", u: "api/pedidos.php",
      b: { itens: [{ produto_id: 1, quantidade: 1 }] }, espera: 400 },
    { nome: "QR Code inválido", m: "GET", u: "mesa.php?t=token-falso", espera: 404 },
    mesa(MESA_TESTE),
    { nome: "mesa gravada na sessão", m: "GET", u: "api/me.php", espera: 200,
      v: j => j.mesa === MESA_TESTE ? null : `mesa na sessão: ${j.mesa}` },
    { nome: "cardápio só com produtos disponíveis", m: "GET", u: "api/cardapio.php", espera: 200,
      salvar: (j, c) => { c.prod = j[0]; },
      v: (j, c) => !j.length ? "cardápio vazio"
          : j.some(p => !p.disponivel) ? "veio produto indisponível"
          : j.some(p => p.id === c.prodIndisp) ? "produto de teste indisponível apareceu" : null },
    { nome: "pedido sem itens", m: "POST", u: "api/pedidos.php", b: { itens: [] }, espera: 400 },
    { nome: "pedido com quantidade 0", m: "POST", u: "api/pedidos.php",
      b: () => ({ itens: [{ produto_id: ctx.prod.id, quantidade: 0 }] }), espera: 400 },
    { nome: "pedido com produto inexistente", m: "POST", u: "api/pedidos.php",
      b: { itens: [{ produto_id: 999999, quantidade: 1 }] }, espera: 404 },
    { nome: "pedido com produto indisponível", m: "POST", u: "api/pedidos.php",
      b: () => ({ itens: [{ produto_id: ctx.prodIndisp, quantidade: 1 }] }), espera: 409 },
    { nome: "pedido válido; preço enviado pelo navegador é ignorado", m: "POST", u: "api/pedidos.php",
      b: () => ({ itens: [{ produto_id: ctx.prod.id, quantidade: 2, observacao: "teste automático", preco_unitario: 0.01 }],
                  total: 0.01 }),
      espera: 201, salvar: (j, c) => { c.pedido = j.id; },
      v: (j, c) => {
          const esperado = Math.round(c.prod.preco * 2 * 100) / 100;
          return j.total === esperado ? null : `total ${j.total}, esperado ${esperado}`;
      } },
    { nome: "cliente acompanha os pedidos", m: "GET", u: "api/pedidos.php", espera: 200,
      v: (j, c) => contem(j, c.pedido) ? null : "pedido criado não aparece" },
    { nome: "cliente tenta ver a fila da cozinha", m: "GET", u: "api/cozinha/pedidos.php", espera: 403 },
    { nome: "cliente tenta excluir produto pela página", m: "POST", u: "produto_excluir.php", b: { id: 1 }, op: { form: true }, espera: 403 },
    { nome: "pedido sem token CSRF é recusado", m: "POST", u: "api/pedidos.php", op: { semToken: true },
      b: () => ({ itens: [{ produto_id: ctx.prod.id, quantidade: 1 }] }), espera: 403 },
    { nome: "cliente vê a conta da mesa", m: "GET", u: "api/cliente/pagamento.php", espera: 200 },
    { nome: "pedir a conta com pedido ainda na cozinha", m: "POST", u: "api/cliente/pagamento.php", espera: 409 },
    logout(),

    { s: "Cozinha" },
    login("cozinha"),
    { nome: "fila da cozinha tem o pedido", m: "GET", u: "api/cozinha/pedidos.php", espera: 200,
      v: (j, c) => contem(j, c.pedido) ? null : "pedido não está na fila" },
    { nome: "pular etapa (recebido → pronto)", m: "PUT", u: "api/cozinha/status.php",
      b: () => ({ pedido_id: ctx.pedido, status: "pronto" }), espera: 409 },
    { nome: "status que não é da cozinha", m: "PUT", u: "api/cozinha/status.php",
      b: () => ({ pedido_id: ctx.pedido, status: "entregue" }), espera: 400 },
    { nome: "pedido inexistente", m: "PUT", u: "api/cozinha/status.php",
      b: { pedido_id: 999999, status: "em_preparo" }, espera: 404 },
    { nome: "método errado (POST)", m: "POST", u: "api/cozinha/status.php",
      b: () => ({ pedido_id: ctx.pedido, status: "em_preparo" }), espera: 405 },
    { nome: "recebido → em_preparo", m: "PUT", u: "api/cozinha/status.php",
      b: () => ({ pedido_id: ctx.pedido, status: "em_preparo" }), espera: 200,
      v: j => j.status === "em_preparo" ? null : `status ${j.status}` },
    { nome: "em_preparo → pronto", m: "PUT", u: "api/cozinha/status.php",
      b: () => ({ pedido_id: ctx.pedido, status: "pronto" }), espera: 200,
      v: j => j.status === "pronto" ? null : `status ${j.status}` },
    { nome: "cozinha tenta confirmar entrega", m: "PUT", u: "api/garcom/entregar.php",
      b: () => ({ pedido_id: ctx.pedido }), espera: 403 },
    logout(),

    { s: "Garçom" },
    login("garcom"),
    { nome: "pedido aparece nos prontos", m: "GET", u: "api/garcom/pedidos.php", espera: 200,
      v: (j, c) => contem(j, c.pedido) ? null : "pedido não está nos prontos" },
    { nome: "confirma entrega (pronto → entregue)", m: "PUT", u: "api/garcom/entregar.php",
      b: () => ({ pedido_id: ctx.pedido }), espera: 200,
      v: j => j.status === "entregue" ? null : `status ${j.status}` },
    { nome: "entregar de novo", m: "PUT", u: "api/garcom/entregar.php",
      b: () => ({ pedido_id: ctx.pedido }), espera: 409 },
    { nome: "garçom tenta abrir o caixa", m: "GET", u: "api/admin/caixa.php", espera: 403 },
    logout(),

    { s: "Cliente: conta" },
    login("cliente"),
    mesa(MESA_TESTE),
    { nome: "pede a conta", m: "POST", u: "api/cliente/pagamento.php", espera: 200,
      v: j => j.status_mesa === "aguardando_pagamento" ? null : `mesa ficou ${j.status_mesa}` },
    { nome: "pede a conta de novo", m: "POST", u: "api/cliente/pagamento.php", espera: 409 },
    { nome: "novo pedido com a conta já pedida", m: "POST", u: "api/pedidos.php",
      b: () => ({ itens: [{ produto_id: ctx.prod.id, quantidade: 1 }] }), espera: 409 },
    logout(),

    { s: "Admin: caixa e relatórios" },
    login("admin"),
    { nome: "caixa lista a mesa aguardando", m: "GET", u: "api/admin/caixa.php", espera: 200,
      v: j => j.some(c => c.mesa === MESA_TESTE) ? null : "mesa de teste não está no caixa" },
    { nome: "fechar mesa inexistente", m: "POST", u: "api/admin/caixa.php", b: { mesa_id: 999999 }, espera: 404 },
    { nome: "fecha a conta da mesa", m: "POST", u: "api/admin/caixa.php", b: { mesa_id: MESA_TESTE }, espera: 200,
      v: (j, c) => j.status_mesa !== "livre" ? "mesa não ficou livre"
          : !contem(j.pedidos, c.pedido) ? "pedido não estava na conta"
          : j.pedidos.some(p => p.status !== "finalizado") ? "há pedido não finalizado" : null },
    { nome: "fechar a mesma conta de novo", m: "POST", u: "api/admin/caixa.php", b: { mesa_id: MESA_TESTE }, espera: 409 },
    { nome: "dashboard do dia", m: "GET", u: "api/admin/dashboard.php", espera: 200,
      v: j => typeof j.faturamento_hoje === "number" ? null : "faturamento ausente" },
    { nome: "filtro de status inválido", m: "GET", u: "api/admin/pedidos.php?status=xyz", espera: 400 },
    { nome: "pedidos finalizados incluem o do teste", m: "GET", u: "api/admin/pedidos.php?status=finalizado", espera: 200,
      v: (j, c) => contem(j, c.pedido) ? null : "pedido não aparece como finalizado" },

    { s: "Admin: usuários" },
    { nome: "criar usuário com perfil inválido", m: "POST", u: "api/admin/usuarios.php",
      b: { nome: "Fulano", email: "fulano@teste.com", senha: "123456", perfil: "chefe" }, espera: 400 },
    { nome: "criar garçom", m: "POST", u: "api/admin/usuarios.php",
      b: () => (ctx.emailEquipe = `garcom_${sufixo()}@teste.com`,
               { nome: "Garçom Teste", email: ctx.emailEquipe, senha: "123456", perfil: "garcom" }),
      espera: 201, salvar: (j, c) => { c.equipe = j.id; } },
    { nome: "criar com e-mail repetido", m: "POST", u: "api/admin/usuarios.php",
      b: () => ({ nome: "Garçom Teste", email: ctx.emailEquipe, senha: "123456", perfil: "garcom" }), espera: 409 },
    { nome: "mudar perfil para cozinha", m: "PUT", u: "api/admin/usuarios.php",
      b: () => ({ id: ctx.equipe, perfil: "cozinha" }), espera: 200,
      v: j => j.perfil === "cozinha" ? null : `perfil ${j.perfil}` },
    { nome: "admin tenta tirar o próprio perfil", m: "PUT", u: "api/admin/usuarios.php",
      b: () => ({ id: ctx.ids.admin, perfil: "cliente" }), espera: 409 },
    { nome: "admin tenta se excluir", m: "DELETE", u: () => `api/admin/usuarios.php?id=${ctx.ids.admin}`, espera: 409 },
    { nome: "excluir cliente que tem pedidos", m: "DELETE", u: () => `api/admin/usuarios.php?id=${ctx.ids.cliente}`, espera: 409 },
    { nome: "excluir usuário de teste", m: "DELETE", u: () => `api/admin/usuarios.php?id=${ctx.equipe}`, espera: 200 },
    { nome: "excluir de novo", m: "DELETE", u: () => `api/admin/usuarios.php?id=${ctx.equipe}`, espera: 404 },
    { nome: "excluir cliente cadastrado no teste", m: "DELETE", u: () => `api/admin/usuarios.php?id=${ctx.cadastrado}`, espera: 200 },
    { nome: "lista de usuários não expõe senha", m: "GET", u: "api/admin/usuarios.php", espera: 200,
      v: (j, c, r) => r.texto.includes("senha") ? "a resposta contém dados de senha" : null },

    { s: "Admin: cardápio" },
    { nome: "produto com preço negativo", m: "POST", u: "api/admin/produtos.php",
      b: { nome: "Inválido", preco: -1, categoria: "Teste" }, espera: 400 },
    { nome: "alterar produto inexistente", m: "PUT", u: "api/admin/produtos.php",
      b: { id: 999999, preco: 10 }, espera: 404 },
    { nome: "excluir produto que já está em pedido", m: "DELETE", u: () => `api/admin/produtos.php?id=${ctx.prod.id}`, espera: 409 },
    { nome: "excluir produto de teste", m: "DELETE", u: () => `api/admin/produtos.php?id=${ctx.prodIndisp}`, espera: 200 },
    { nome: "excluir de novo", m: "DELETE", u: () => `api/admin/produtos.php?id=${ctx.prodIndisp}`, espera: 404 },
    logout(),
];

let ctx;
const tbody = document.getElementById("tbody-resultados");

function linhaSecao(titulo) {
    const tr = document.createElement("tr");
    tr.className = "secao";
    const td = document.createElement("td");
    td.colSpan = 6;
    td.textContent = titulo;
    tr.append(td);
    tbody.append(tr);
}

function celula(tr, texto, classe) {
    const td = document.createElement("td");
    if (classe) td.className = classe;
    td.textContent = texto;
    tr.append(td);
    return td;
}

function linhaPasso(n, passo, metodo, url) {
    const tr = document.createElement("tr");
    tr.className = "linha";
    celula(tr, n);
    celula(tr, passo.nome);
    const req = celula(tr, "");
    const m = document.createElement("span"); m.className = "metodo"; m.textContent = metodo + " ";
    const u = document.createElement("span"); u.className = "url"; u.textContent = url;
    req.append(m, u);
    celula(tr, [].concat(passo.espera).join(" ou "));
    const obtido = celula(tr, "…");
    const selo = document.createElement("span"); selo.className = "selo rodando"; selo.textContent = "…";
    celula(tr, "").append(selo);
    tbody.append(tr);
    return { tr, obtido, selo };
}

function anexarDetalhe(tr, motivo, r, corpo) {
    const det = document.createElement("tr");
    det.className = "detalhe";
    det.hidden = true;
    const td = document.createElement("td");
    td.colSpan = 6;
    if (motivo) {
        const p = document.createElement("div"); p.className = "motivo"; p.textContent = "Falhou: " + motivo; td.append(p);
    }
    const pre = document.createElement("pre");
    pre.textContent = (corpo ? "Corpo enviado:\n" + corpo + "\n\n" : "")
        + (r ? `Resposta (${r.status}, ${r.ms} ms):\n` + formatar(r) : "Sem resposta");
    td.append(pre);
    det.append(td);
    tr.after(det);
    tr.onclick = () => { det.hidden = !det.hidden; };
}

async function rodarRoteiro() {
    const btn = document.getElementById("btn-rodar");
    btn.disabled = true;
    btn.textContent = "Rodando…";
    tbody.innerHTML = "";
    document.getElementById("roteiro-vazio").hidden = true;
    ctx = { ids: {} };
    let total = 0, ok = 0, falha = 0, n = 0;
    const inicio = performance.now();
    const placar = () => {
        document.getElementById("c-total").textContent = `${total} testes`;
        document.getElementById("c-ok").textContent = `${ok} passaram`;
        document.getElementById("c-falha").textContent = `${falha} falharam`;
    };

    for (const passo of ROTEIRO) {
        if (passo.s) { linhaSecao(passo.s); continue; }
        n++;
        let url, corpo, r = null, motivo = null;
        try {
            url = typeof passo.u === "function" ? passo.u() : passo.u;
            const b = typeof passo.b === "function" ? passo.b() : passo.b;
            corpo = b === undefined ? undefined : (typeof b === "string" ? b : JSON.stringify(b));
        } catch (e) {
            url = url || "(não montada)";
            motivo = "um passo anterior não gerou os dados necessários";
        }
        const linha = linhaPasso(n, passo, passo.m, url);
        if (!motivo) {
            try {
                r = await chamar(passo.m, url, passo.op?.form ? (typeof passo.b === "function" ? passo.b() : passo.b) : corpo, passo.op);
                linha.obtido.textContent = r.status;
                const esperados = [].concat(passo.espera);
                if (!esperados.includes(r.status)) {
                    motivo = `status ${r.status}, esperado ${esperados.join(" ou ")}`;
                } else {
                    if (passo.v) motivo = passo.v(r.json, ctx, r);
                    if (!motivo && passo.salvar) passo.salvar(r.json, ctx);
                }
            } catch (e) {
                motivo = "erro: " + e.message;
                linha.obtido.textContent = "erro";
            }
        } else {
            linha.obtido.textContent = "—";
        }
        total++;
        motivo ? falha++ : ok++;
        linha.selo.className = "selo " + (motivo ? "falha" : "ok");
        linha.selo.textContent = motivo ? "FALHOU" : "OK";
        linha.tr.dataset.ok = motivo ? "0" : "1";
        anexarDetalhe(linha.tr, motivo, r, corpo);
        placar();
    }

    document.getElementById("c-tempo").textContent = `em ${((performance.now() - inicio) / 1000).toFixed(1)} s`;
    aplicarFiltro();
    btn.disabled = false;
    btn.textContent = "Rodar de novo";
    atualizarSessao();
}

function aplicarFiltro() {
    const so = document.getElementById("so-falhas").checked;
    tbody.querySelectorAll("tr.linha").forEach(tr => {
        const esconder = so && tr.dataset.ok === "1";
        tr.hidden = esconder;
        if (esconder && tr.nextElementSibling?.classList.contains("detalhe")) tr.nextElementSibling.hidden = true;
    });
}

document.getElementById("btn-rodar").onclick = rodarRoteiro;
document.getElementById("so-falhas").onchange = aplicarFiltro;

/* ---------- Requisição manual ---------- */

const CATALOGO = [
    ["Pública", "POST", "api/login.php", { email: CONTAS.admin.email, senha: CONTAS.admin.senha }, "Login: 200 / 400 / 401"],
    ["Pública", "POST", "api/cadastro.php", { nome: "Novo Cliente", email: "novo@teste.com", senha: "123456" }, "Cadastro de cliente: 201 / 400 / 409"],
    ["Logado", "GET", "api/me.php", null, "Quem está logado: 200 / 401"],
    ["Logado", "POST", "api/logout.php", null, "Encerra a sessão: 200"],
    ["Logado", "GET", "api/cardapio.php", null, "Produtos disponíveis: 200 / 401"],
    ["Cliente", "GET", "api/pedidos.php", null, "Meus pedidos em aberto"],
    ["Cliente", "POST", "api/pedidos.php", { itens: [{ produto_id: 1, quantidade: 2, observacao: "sem cebola" }, { produto_id: 7, quantidade: 1 }] }, "Cria pedido na mesa da sessão: 201 / 400 / 404 / 409"],
    ["Cliente", "GET", "api/cliente/pagamento.php", null, "Conta em aberto da mesa"],
    ["Cliente", "POST", "api/cliente/pagamento.php", null, "Pede a conta: 200 / 409"],
    ["Cozinha", "GET", "api/cozinha/pedidos.php", null, "Fila: recebido e em preparo"],
    ["Cozinha", "PUT", "api/cozinha/status.php", { pedido_id: 1, status: "em_preparo" }, "em_preparo ou pronto: 200 / 400 / 404 / 409"],
    ["Garçom", "GET", "api/garcom/pedidos.php", null, "Pedidos prontos para entregar"],
    ["Garçom", "PUT", "api/garcom/entregar.php", { pedido_id: 1 }, "pronto → entregue: 200 / 404 / 409"],
    ["Admin", "GET", "api/admin/dashboard.php", null, "Métricas do dia"],
    ["Admin", "GET", "api/admin/pedidos.php?status=recebido", null, "Pedidos (filtro opcional por status)"],
    ["Admin", "GET", "api/admin/caixa.php", null, "Mesas aguardando pagamento"],
    ["Admin", "POST", "api/admin/caixa.php", { mesa_id: 1 }, "Fecha a conta: 200 / 404 / 409"],
    ["Admin", "GET", "api/admin/usuarios.php", null, "Lista usuários"],
    ["Admin", "POST", "api/admin/usuarios.php", { nome: "Garçom Novo", email: "garcom.novo@restaurante.com", senha: "123456", perfil: "garcom" }, "Cria usuário de qualquer perfil"],
    ["Admin", "PUT", "api/admin/usuarios.php", { id: 5, perfil: "cozinha" }, "Muda perfil"],
    ["Admin", "DELETE", "api/admin/usuarios.php?id=5", null, "Exclui usuário"],
    ["Admin", "GET", "api/admin/produtos.php", null, "Cardápio completo"],
    ["Admin", "POST", "api/admin/produtos.php", { nome: "Brigadeiro", preco: 4.5, categoria: "Sobremesas", descricao: "Unidade" }, "Cria produto"],
    ["Admin", "PUT", "api/admin/produtos.php", { id: 10, disponivel: true }, "Altera campos enviados"],
    ["Admin", "DELETE", "api/admin/produtos.php?id=11", null, "Exclui produto"],
];

const catalogo = document.getElementById("catalogo");
CATALOGO.forEach(([grupo, metodo, url, corpo, desc]) => {
    const b = document.createElement("button");
    b.type = "button";
    b.className = "item-cat";
    const t = document.createElement("span");
    t.className = "metodo";
    t.textContent = `${metodo} `;
    const u = document.createElement("span");
    u.className = "url";
    u.textContent = url;
    const s = document.createElement("small");
    s.textContent = `${grupo} · ${desc}`;
    b.append(t, u, s);
    b.onclick = () => {
        catalogo.querySelectorAll(".ativo").forEach(x => x.classList.remove("ativo"));
        b.classList.add("ativo");
        document.getElementById("m-metodo").value = metodo;
        document.getElementById("m-url").value = url;
        document.getElementById("m-corpo").value = corpo ? JSON.stringify(corpo, null, 2) : "";
        document.getElementById("manual-titulo").textContent = `${metodo} ${url}`;
        document.getElementById("manual-desc").textContent = `${grupo} · ${desc}`;
    };
    catalogo.append(b);
});

document.getElementById("btn-enviar").onclick = async () => {
    const btn = document.getElementById("btn-enviar");
    const st = document.getElementById("m-status");
    const out = document.getElementById("m-resposta");
    btn.disabled = true;
    st.textContent = "Enviando…";
    try {
        const r = await chamar(document.getElementById("m-metodo").value,
            document.getElementById("m-url").value.trim(), document.getElementById("m-corpo").value.trim());
        st.textContent = `HTTP ${r.status} · ${r.ms} ms`;
        st.style.color = r.status < 300 ? "#17603a" : r.status < 500 ? "var(--aviso-texto)" : "var(--erro-texto)";
        out.textContent = formatar(r);
    } catch (e) {
        st.textContent = "Erro de rede";
        out.textContent = e.message;
    }
    btn.disabled = false;
    atualizarSessao();
};

atualizarSessao();
</script>
</body>
</html>
