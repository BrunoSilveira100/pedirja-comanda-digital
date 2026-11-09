// PedirJá — utilitários das telas: API, elementos, toasts, modal, skeleton, polling e gaveta.
// Todo texto vindo da API entra com textContent (nunca innerHTML), o que evita XSS.
"use strict";

const PJ = (() => {
    const base = document.documentElement.dataset.base || "./";
    const reduzirMovimento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* ---------- API ---------- */
    // Token CSRF da sessão: vem no <meta> da página e vai no cabeçalho de toda requisição que altera dados
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');

    async function api(metodo, url, corpo) {
        const op = { method: metodo, credentials: "same-origin", headers: { Accept: "application/json" } };
        if (metodo !== "GET" && metaCsrf) op.headers["X-CSRF-Token"] = metaCsrf.content;
        if (corpo instanceof FormData) {
            op.body = corpo;   // envio de arquivo: o navegador monta o multipart sozinho
        } else if (corpo !== undefined) {
            op.headers["Content-Type"] = "application/json";
            op.body = JSON.stringify(corpo);
        }
        let res;
        try {
            res = await fetch(base + url, op);
        } catch (e) {
            throw new ErroApi(0, "Sem conexão com o servidor. Verifique a internet.");
        }
        let dados = null;
        try { dados = await res.json(); } catch (_) { /* sem corpo JSON */ }
        if (res.status === 401) {
            window.location.href = base + "login.php";
            throw new ErroApi(401, "Sessão expirada.");
        }
        if (!res.ok) {
            throw new ErroApi(res.status, (dados && dados.erro) || mensagemPadrao(res.status), dados);
        }
        return dados;
    }

    class ErroApi extends Error {
        constructor(status, mensagem, dados) { super(mensagem); this.status = status; this.dados = dados; }
    }

    function mensagemPadrao(status) {
        return {
            400: "Confira os dados enviados.",
            403: "Seu perfil não tem permissão para isso.",
            404: "Não encontrado.",
            409: "A ação não pôde ser concluída agora.",
        }[status] || "Erro no servidor. Tente de novo em instantes.";
    }

    /* ---------- Elementos ---------- */
    // el("div", { class: "card", onclick: fn }, "texto", outroNo)
    function el(tag, attrs, ...filhos) {
        const no = document.createElement(tag);
        for (const [k, v] of Object.entries(attrs || {})) {
            if (v === null || v === undefined || v === false) continue;
            if (k.startsWith("on")) no.addEventListener(k.slice(2), v);
            else if (k === "class") no.className = v;
            else if (k === "style") no.style.cssText = v;
            else if (k in no && typeof v !== "string") no[k] = v;
            else no.setAttribute(k, v === true ? "" : v);
        }
        for (const f of filhos.flat()) {
            if (f === null || f === undefined || f === false) continue;
            no.append(f instanceof Node ? f : document.createTextNode(String(f)));
        }
        return no;
    }

    const ICONES = {
        check: '<path d="M20 6 9 17l-5-5"/>',
        x: '<path d="M18 6 6 18M6 6l12 12"/>',
        info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        alerta: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        mais: '<path d="M12 5v14M5 12h14"/>',
        menos: '<path d="M5 12h14"/>',
        sacola: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
        lixo: '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>',
        editar: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        relogio: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        prato: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/>',
        recibo: '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2 2 2V2l-2 2-3-2-3 2-3-2-3 2z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        atualizar: '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
    };
    function icone(nome, classe) {
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("fill", "none");
        svg.setAttribute("stroke", "currentColor");
        svg.setAttribute("stroke-width", "2");
        svg.setAttribute("stroke-linecap", "round");
        svg.setAttribute("stroke-linejoin", "round");
        svg.setAttribute("aria-hidden", "true");
        if (classe) svg.setAttribute("class", classe);
        svg.innerHTML = ICONES[nome] || "";   // conteúdo fixo do próprio código
        return svg;
    }

    function iconeSucesso() {
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("viewBox", "0 0 52 52");
        svg.setAttribute("class", "check");
        svg.setAttribute("aria-hidden", "true");
        svg.innerHTML = '<circle cx="26" cy="26" r="24" fill="none" stroke="currentColor" stroke-width="3"/>'
            + '<path d="M15 27l7 7 15-15" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>';
        return svg;
    }

    const pontos = () => el("span", { class: "pontos", "aria-hidden": "true" }, el("i"), el("i"), el("i"));

    /* ---------- Formatação ---------- */
    const fmtMoeda = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });
    const dinheiro = v => fmtMoeda.format(Number(v) || 0);

    function dataServidor(s) { return new Date(String(s).replace(" ", "T")); }
    function haQuanto(s) {
        const min = Math.max(0, Math.floor((Date.now() - dataServidor(s).getTime()) / 60000));
        if (min < 1) return "agora";
        if (min < 60) return `há ${min} min`;
        const h = Math.floor(min / 60);
        return `há ${h} h ${min % 60} min`;
    }
    function minutos(s) { return Math.floor((Date.now() - dataServidor(s).getTime()) / 60000); }
    const hora = s => dataServidor(s).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });

    const STATUS = {
        recebido: "Recebido", em_preparo: "Em preparo", pronto: "Pronto", entregue: "Entregue",
        solicitado_pagamento: "Conta pedida", finalizado: "Finalizado",
        livre: "Livre", ocupada: "Ocupada", aguardando_pagamento: "Aguardando pagamento",
    };
    function selo(status) {
        const s = el("span", { class: `selo ${status}` }, STATUS[status] || status);
        if (status === "em_preparo") s.prepend(pontos());
        return s;
    }

    /* ---------- Escalonamento e entrada ---------- */
    // Faz os filhos entrarem um após o outro (0.08s). A classe sai no fim para
    // que atualizações seguintes da lista não repitam a animação.
    function escalonar(container) {
        const filhos = [...container.children];
        filhos.forEach((c, i) => c.style.setProperty("--i", Math.min(i, 12)));
        container.classList.remove("escalonar");
        void container.offsetWidth;
        container.classList.add("escalonar");
        clearTimeout(container._timerEscalonar);
        container._timerEscalonar = setTimeout(() => container.classList.remove("escalonar"),
            (Math.min(filhos.length, 12) * 80) + 700);
    }

    /* ---------- Skeleton ---------- */
    function skeletonCards(n = 3) {
        return Array.from({ length: n }, () => el("div", { class: "skeleton sk-card", "aria-hidden": "true" }));
    }
    function skeletonLinhas(n = 4) {
        return el("div", { class: "card", "aria-busy": "true" },
            el("div", { class: "skeleton sk-titulo" }),
            Array.from({ length: n }, (_, i) => el("div", { class: "skeleton sk-linha", style: `width:${90 - i * 12}%` })));
    }

    function vazio(titulo, texto, nomeIcone = "prato") {
        return el("div", { class: "vazio entra" }, icone(nomeIcone), el("strong", null, titulo), texto);
    }

    /* ---------- Toasts ---------- */
    let caixaToasts;
    function toast(msg, tipo = "info", ms = 4000) {
        if (!caixaToasts) {
            caixaToasts = el("div", { class: "toasts", role: "status", "aria-live": "polite" });
            document.body.append(caixaToasts);
        }
        const t = el("div", { class: `toast ${tipo}` },
            icone(tipo === "ok" ? "check" : tipo === "erro" ? "alerta" : "info"), el("span", null, msg));
        caixaToasts.append(t);
        setTimeout(() => {
            t.classList.add("saindo");
            setTimeout(() => t.remove(), reduzirMovimento ? 0 : 300);
        }, ms);
    }

    /* ---------- Erro: tremida ---------- */
    function tremer(no) {
        no.classList.remove("tremer");
        void no.offsetWidth;   // reinicia a animação
        no.classList.add("tremer");
    }

    /* ---------- Botão carregando ---------- */
    async function comCarregando(botao, fn) {
        const original = [...botao.childNodes];
        botao.disabled = true;
        botao.replaceChildren(el("span", { class: "carregando", "aria-hidden": "true" }), el("span", { class: "sr-only" }, "Enviando"));
        try { return await fn(); }
        finally { botao.disabled = false; botao.replaceChildren(...original); }
    }

    /* ---------- Modal ---------- */
    // modal({ titulo, corpo: Node, rodape: [botões] }) → { fechar, corpoEl, painel }
    function modal({ titulo, corpo, rodape, aoFechar }) {
        const anterior = document.activeElement;
        const btnFechar = el("button", { class: "btn btn-fantasma btn-icone", type: "button", "aria-label": "Fechar" }, icone("x"));
        const corpoEl = el("div", { class: "modal-corpo" }, corpo);
        const rodapeEl = rodape && rodape.length ? el("div", { class: "modal-rodape" }, rodape) : null;
        const painel = el("div", { class: "modal", role: "dialog", "aria-modal": "true", "aria-label": titulo },
            el("div", { class: "modal-topo" }, el("h2", null, titulo), btnFechar), corpoEl, rodapeEl);
        const fundo = el("div", { class: "modal-fundo" }, painel);

        function fechar() {
            document.removeEventListener("keydown", tecla);
            fundo.classList.add("saindo");
            setTimeout(() => fundo.remove(), reduzirMovimento ? 0 : 200);
            if (anterior && anterior.focus) anterior.focus();
            if (aoFechar) aoFechar();
        }
        function tecla(e) {
            if (e.key === "Escape") fechar();
            if (e.key === "Tab") {   // mantém o foco dentro do modal
                const foco = painel.querySelectorAll("button, [href], input, select, textarea");
                const lista = [...foco].filter(x => !x.disabled);
                if (!lista.length) return;
                const primeiro = lista[0], ultimo = lista[lista.length - 1];
                if (e.shiftKey && document.activeElement === primeiro) { e.preventDefault(); ultimo.focus(); }
                else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primeiro.focus(); }
            }
        }
        btnFechar.onclick = fechar;
        fundo.addEventListener("mousedown", e => { if (e.target === fundo) fechar(); });
        document.addEventListener("keydown", tecla);
        document.body.append(fundo);
        const alvo = painel.querySelector("input, select, textarea") || painel.querySelector(".modal-rodape .btn:last-child") || btnFechar;
        setTimeout(() => alvo.focus(), 30);
        return { fechar, corpoEl, painel, rodapeEl };
    }

    // Mostra o check animado dentro do modal e fecha sozinho
    function sucessoNoModal(m, texto, ms = 1600) {
        m.corpoEl.replaceChildren(el("div", { class: "modal-sucesso" }, iconeSucesso(), el("strong", null, texto)));
        if (m.rodapeEl) m.rodapeEl.remove();
        setTimeout(m.fechar, ms);
    }

    function confirmar({ titulo, texto, botao = "Confirmar", perigo = false, acao }) {
        const btnOk = el("button", { class: `btn ${perigo ? "btn-perigo" : ""}`, type: "button" }, botao);
        const btnCancelar = el("button", { class: "btn btn-sec", type: "button" }, "Cancelar");
        const msgErro = el("div", { class: "alerta erro mt", hidden: true });
        const corpo = el("div", null, typeof texto === "string" ? el("p", null, texto) : texto, msgErro);
        const m = modal({ titulo, corpo, rodape: [btnCancelar, btnOk] });
        btnCancelar.onclick = m.fechar;
        btnOk.onclick = () => comCarregando(btnOk, async () => {
            try {
                const msg = await acao();
                sucessoNoModal(m, msg || "Pronto!");
            } catch (e) {
                msgErro.textContent = e.message;
                msgErro.hidden = false;
                tremer(m.painel);
            }
        });
        return m;
    }

    /* ---------- Polling (pausa com a aba escondida) ---------- */
    function polling(fn, ms) {
        let timer = null;
        const ciclo = async () => {
            try { await fn(); } catch (e) { if (e.status !== 401) console.warn(e); }
            timer = setTimeout(ciclo, ms);
        };
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) { clearTimeout(timer); timer = null; }
            else if (!timer) ciclo();
        });
        ciclo();
        return { agora: () => { clearTimeout(timer); ciclo(); } };
    }

    /* ---------- Gaveta do menu (celular) ---------- */
    function iniciarGaveta() {
        const app = document.querySelector(".app");
        const btn = document.querySelector(".btn-menu");
        const fundo = document.querySelector(".fundo-gaveta");
        if (!app || !btn) return;
        const alternar = abrir => {
            app.classList.toggle("gaveta-aberta", abrir);
            btn.setAttribute("aria-expanded", abrir);
        };
        btn.onclick = () => alternar(!app.classList.contains("gaveta-aberta"));
        fundo.onclick = () => alternar(false);
        document.querySelectorAll(".lateral .nav-link").forEach(a => a.addEventListener("click", () => alternar(false)));
        document.addEventListener("keydown", e => { if (e.key === "Escape") alternar(false); });
    }

    function marcarNavAtiva() {
        const hash = location.hash || "";
        document.querySelectorAll(".lateral .nav-link[data-hash]").forEach(a => {
            const ativo = a.dataset.hash === hash || (!hash && a.dataset.padrao !== undefined);
            if (ativo) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current");
        });
    }

    function rolarAte(no) {
        no.scrollIntoView({ behavior: reduzirMovimento ? "auto" : "smooth", block: "nearest" });
    }

    /* ---------- Armazenamento local (só conveniência) ---------- */
    const local = {
        ler(chave, padrao) { try { return JSON.parse(sessionStorage.getItem(chave)) ?? padrao; } catch (_) { return padrao; } },
        gravar(chave, v) { try { sessionStorage.setItem(chave, JSON.stringify(v)); } catch (_) { /* sem storage */ } },
    };

    /* ---------- PWA ---------- */
    if ("serviceWorker" in navigator && location.protocol.startsWith("http")) {
        window.addEventListener("load", () => navigator.serviceWorker.register(base + "sw.js").catch(() => {}));
    }

    // Formulários com data-confirmar pedem confirmação antes de enviar
    // (substitui onsubmit="..." no HTML, que a Content-Security-Policy bloqueia)
    document.addEventListener("submit", e => {
        const msg = e.target.dataset && e.target.dataset.confirmar;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    document.addEventListener("DOMContentLoaded", () => { iniciarGaveta(); marcarNavAtiva(); });
    window.addEventListener("hashchange", marcarNavAtiva);

    return {
        api, ErroApi, el, icone, iconeSucesso, pontos, dinheiro, haQuanto, minutos, hora, STATUS, selo,
        escalonar, skeletonCards, skeletonLinhas, vazio, toast, tremer, comCarregando, modal, sucessoNoModal,
        confirmar, polling, rolarAte, local, reduzirMovimento,
    };
})();
