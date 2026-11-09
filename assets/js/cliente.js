// Tela do cliente: cardápio → carrinho → pedido → acompanhamento → conta
"use strict";
(() => {
    const { api, el, icone, dinheiro, hora, selo, escalonar, skeletonCards, skeletonLinhas, vazio,
            toast, tremer, comCarregando, modal, sucessoNoModal, confirmar, polling, rolarAte, local } = PJ;

    const tela = document.getElementById("tela-cliente");
    const temMesa = tela.dataset.mesa !== "";
    const listaProdutos = document.getElementById("lista-produtos");
    const chips = document.getElementById("chips");
    const listaPedidos = document.getElementById("lista-pedidos");
    const contaResumo = document.getElementById("conta-resumo");
    const barra = document.getElementById("barra-carrinho");

    let produtos = [];
    let categoria = "Todos";
    let carrinho = local.ler("pj-carrinho", {});    // { produtoId: { qtd, obs } }
    const statusConhecido = new Map();               // pedidoId → status (para avisar mudanças)
    let primeiraCarga = true;

    /* ---------------- Cardápio ---------------- */

    async function carregarCardapio() {
        listaProdutos.replaceChildren(...skeletonCards(6));
        try {
            produtos = await api("GET", "api/cardapio.php");
        } catch (e) {
            listaProdutos.replaceChildren(el("div", { class: "alerta erro" }, icone("alerta"), e.message));
            return;
        }
        // remove do carrinho o que saiu do cardápio
        for (const id of Object.keys(carrinho)) {
            if (!produtos.some(p => p.id === Number(id))) delete carrinho[id];
        }
        salvarCarrinho();
        desenharChips();
        desenharProdutos(true);
    }

    function desenharChips() {
        const categorias = ["Todos", ...new Set(produtos.map(p => p.categoria))];
        chips.replaceChildren(...categorias.map(c => el("button", {
            class: "chip", type: "button", "aria-pressed": String(c === categoria),
            onclick: () => { categoria = c; desenharChips(); desenharProdutos(true); },
        }, c)));
    }

    function desenharProdutos(animar) {
        const visiveis = produtos.filter(p => categoria === "Todos" || p.categoria === categoria);
        if (!visiveis.length) {
            listaProdutos.replaceChildren(vazio("Nada por aqui", "Nenhum produto disponível nesta categoria."));
            return;
        }
        listaProdutos.replaceChildren(...visiveis.map(cardProduto));
        listaProdutos.classList.remove("escalonar");
        if (animar) { void listaProdutos.offsetWidth; escalonar(listaProdutos); }
    }

    function cardProduto(p) {
        const card = el("article", { class: "card interativo produto", "data-id": p.id },
            fotoProduto(p),
            el("span", { class: "selo" }, p.categoria),
            el("h3", null, p.nome),
            el("p", null, p.descricao || ""),
            el("span", { class: "preco" }, dinheiro(p.preco)),
            el("div", { class: "rodape-produto" }, controleQtd(p)));
        return card;
    }

    // Foto do produto; sem foto (ou se o arquivo falhar) mostra o ícone do prato
    function fotoProduto(p) {
        const vazia = () => el("div", { class: "foto-produto sem-foto", "aria-hidden": "true" }, icone("prato"));
        if (!p.imagem) return vazia();
        const img = el("img", { class: "foto-produto", src: PJ.arquivo(p.imagem), alt: p.nome, loading: "lazy", width: 800, height: 600 });
        img.addEventListener("error", () => img.replaceWith(vazia()), { once: true });
        return img;
    }

    function controleQtd(p) {
        const item = carrinho[p.id];
        if (!item) {
            return el("button", { class: "btn", type: "button", "aria-label": `Adicionar ${p.nome}`,
                onclick: () => alterarQtd(p.id, 1) }, icone("mais"), "Adicionar");
        }
        return el("div", { class: "contador-qtd", role: "group", "aria-label": `Quantidade de ${p.nome}` },
            el("button", { type: "button", "aria-label": "Diminuir", onclick: () => alterarQtd(p.id, -1) }, "−"),
            el("span", { "aria-live": "polite" }, item.qtd),
            el("button", { type: "button", "aria-label": "Aumentar", onclick: () => alterarQtd(p.id, 1) }, "+"));
    }

    function alterarQtd(id, delta) {
        const atual = carrinho[id]?.qtd || 0;
        const nova = Math.min(50, Math.max(0, atual + delta));
        if (nova === 0) delete carrinho[id];
        else carrinho[id] = { qtd: nova, obs: carrinho[id]?.obs || "" };
        salvarCarrinho();
        // atualiza só o rodapé do card, sem refazer a lista
        const card = listaProdutos.querySelector(`[data-id="${id}"] .rodape-produto`);
        const p = produtos.find(x => x.id === Number(id));
        if (card && p) {
            card.lastElementChild.replaceWith(controleQtd(p));
            const foco = card.querySelector(delta > 0 ? "button:last-child" : "button");
            if (foco) foco.focus();
        }
    }

    /* ---------------- Carrinho ---------------- */

    function itensCarrinho() {
        return Object.entries(carrinho)
            .map(([id, it]) => ({ ...it, produto: produtos.find(p => p.id === Number(id)) }))
            .filter(it => it.produto);
    }

    function salvarCarrinho() {
        local.gravar("pj-carrinho", carrinho);
        const itens = itensCarrinho();
        const qtd = itens.reduce((s, it) => s + it.qtd, 0);
        const total = itens.reduce((s, it) => s + it.qtd * it.produto.preco, 0);
        document.getElementById("barra-qtd").textContent = `${qtd} ${qtd === 1 ? "item" : "itens"}`;
        document.getElementById("barra-total").textContent = dinheiro(total);
        barra.classList.toggle("visivel", qtd > 0);
    }

    function abrirCarrinho() {
        const itens = itensCarrinho();
        const lista = el("div");
        const totalEl = el("strong");
        const msgErro = el("div", { class: "alerta erro mt", role: "alert", hidden: true });

        const atualizarTotal = () => {
            totalEl.textContent = dinheiro(itensCarrinho().reduce((s, it) => s + it.qtd * it.produto.preco, 0));
        };

        for (const it of itens) {
            const qtdEl = el("span", null, it.qtd);
            const linha = el("div", { class: "item-carrinho" },
                el("div", null, el("strong", null, it.produto.nome), el("div", { class: "suave" }, dinheiro(it.produto.preco))),
                el("div", { class: "contador-qtd" },
                    el("button", { type: "button", "aria-label": "Diminuir", onclick: () => mudar(-1) }, "−"),
                    qtdEl,
                    el("button", { type: "button", "aria-label": "Aumentar", onclick: () => mudar(1) }, "+")),
                el("input", { type: "text", maxlength: 255, placeholder: "Observação (ex.: sem cebola)",
                    "aria-label": `Observação para ${it.produto.nome}`, value: it.obs || "",
                    oninput: e => { if (carrinho[it.produto.id]) { carrinho[it.produto.id].obs = e.target.value; salvarCarrinho(); } } }));
            function mudar(d) {
                alterarQtd(it.produto.id, d);
                const novo = carrinho[it.produto.id];
                if (!novo) { linha.remove(); if (!itensCarrinho().length) m.fechar(); }
                else qtdEl.textContent = novo.qtd;
                atualizarTotal();
            }
            lista.append(linha);
        }
        atualizarTotal();

        const avisoMesa = temMesa ? null
            : el("div", { class: "alerta aviso mt" }, icone("info"), "Escaneie o QR Code da mesa para enviar o pedido.");
        const btnEnviar = el("button", { class: "btn", type: "button", disabled: !temMesa }, icone("check"), "Enviar pedido");
        const btnContinuar = el("button", { class: "btn btn-sec", type: "button" }, "Continuar escolhendo");

        const m = modal({
            titulo: "Seu pedido",
            corpo: el("div", null, lista, el("div", { class: "total-linha" }, el("span", null, "Total"), totalEl), avisoMesa, msgErro),
            rodape: [btnContinuar, btnEnviar],
        });
        btnContinuar.onclick = m.fechar;
        btnEnviar.onclick = () => comCarregando(btnEnviar, async () => {
            const corpo = { itens: itensCarrinho().map(it => ({
                produto_id: it.produto.id, quantidade: it.qtd, observacao: (it.obs || "").trim() || undefined })) };
            try {
                const pedido = await api("POST", "api/pedidos.php", corpo);
                carrinho = {};
                salvarCarrinho();
                desenharProdutos(false);
                sucessoNoModal(m, `Pedido #${pedido.id} enviado para a cozinha!`);
                statusConhecido.set(pedido.id, pedido.status);
                await atualizarPedidos();
                const novo = listaPedidos.querySelector(`[data-id="${pedido.id}"]`);
                if (novo) setTimeout(() => rolarAte(novo), 1700);
            } catch (e) {
                msgErro.textContent = e.message;
                msgErro.hidden = false;
                tremer(m.painel);
                if (e.status === 409 || e.status === 404) carregarCardapio();   // algo ficou indisponível
            }
        });
    }

    document.getElementById("btn-ver-carrinho").onclick = abrirCarrinho;

    /* ---------------- Pedidos (acompanhamento ao vivo) ---------------- */

    const ETAPAS = [["recebido", "Recebido"], ["em_preparo", "Em preparo"], ["pronto", "Pronto"], ["entregue", "Entregue"]];
    const ORDEM = ["recebido", "em_preparo", "pronto", "entregue", "solicitado_pagamento", "finalizado"];
    const AVISOS = {
        em_preparo: n => `Pedido #${n} começou a ser preparado.`,
        pronto: n => `Pedido #${n} está pronto! O garçom já vai levar.`,
        entregue: n => `Pedido #${n} foi entregue. Bom apetite!`,
    };

    function cardPedido(p, novo) {
        const pos = ORDEM.indexOf(p.status);
        const etapas = el("div", { class: "etapas", "aria-label": `Situação: ${PJ.STATUS[p.status]}` },
            ETAPAS.map(([s, rotulo], i) => el("div", {
                class: "etapa" + (i < pos || (i === pos && s === "entregue") || pos > 3 ? " feita" : i === pos ? " atual" : ""),
            }, rotulo)));
        return el("article", { class: `card${novo ? " entra-dir" : ""}`, "data-id": p.id },
            el("div", { class: "pedido-topo" },
                el("h3", null, `Pedido #${p.id}`),
                el("span", { class: "tempo" }, hora(p.criado_em)),
                selo(p.status)),
            etapas,
            el("ul", { class: "itens mt" }, p.itens.map(i => el("li", null,
                el("span", { class: "q" }, `${i.quantidade}×`),
                el("span", null, i.nome, i.observacao ? el("span", { class: "obs" }, i.observacao) : null)))),
            el("div", { class: "pedido-rodape" }, el("span", { class: "suave" }, "Total"),
                el("strong", { class: "direita" }, dinheiro(p.total))));
    }

    async function atualizarPedidos() {
        if (primeiraCarga) listaPedidos.replaceChildren(...skeletonCards(2));
        let pedidos;
        try {
            pedidos = await api("GET", "api/pedidos.php");
        } catch (e) {
            if (primeiraCarga) listaPedidos.replaceChildren(el("div", { class: "alerta erro" }, icone("alerta"), e.message));
            return;
        }
        if (!pedidos.length) {
            listaPedidos.replaceChildren(vazio("Nenhum pedido em aberto", "Escolha itens no cardápio e envie seu primeiro pedido."));
        } else {
            listaPedidos.replaceChildren(...pedidos.map(p => {
                const antes = statusConhecido.get(p.id);
                if (!primeiraCarga && antes && antes !== p.status && AVISOS[p.status]) {
                    toast(AVISOS[p.status](p.id), p.status === "pronto" ? "ok" : "info", 6000);
                    if (p.status === "pronto" && navigator.vibrate) navigator.vibrate([120, 60, 120]);
                }
                const novo = !primeiraCarga && !antes;
                statusConhecido.set(p.id, p.status);
                return cardPedido(p, novo);
            }));
            if (primeiraCarga) escalonar(listaPedidos);
        }
        primeiraCarga = false;
        await atualizarConta();
    }

    /* ---------------- Conta da mesa ---------------- */

    let contaCarregada = false;
    async function atualizarConta() {
        if (!temMesa) {
            contaResumo.replaceChildren(vazio("Mesa não identificada", "Escaneie o QR Code da mesa para ver e pedir a conta.", "recibo"));
            return;
        }
        if (!contaCarregada) contaResumo.replaceChildren(skeletonLinhas(3));
        let conta;
        try {
            conta = await api("GET", "api/cliente/pagamento.php");
        } catch (e) {
            contaResumo.replaceChildren(el("div", { class: "alerta erro" }, icone("alerta"), e.message));
            return;
        }
        contaCarregada = true;
        const emAndamento = conta.pedidos.filter(p => ["recebido", "em_preparo", "pronto"].includes(p.status)).length;
        const jaPediu = conta.status_mesa === "aguardando_pagamento";

        let aviso = null;
        if (jaPediu) aviso = el("div", { class: "alerta ok" }, icone("check"), "Conta pedida. Um atendente vai até a sua mesa.");
        else if (emAndamento) aviso = el("div", { class: "alerta info" }, icone("relogio"),
            `Aguarde a entrega de ${emAndamento} pedido(s) para pedir a conta.`);

        const btn = el("button", { class: "btn", type: "button",
            disabled: jaPediu || emAndamento > 0 || conta.pedidos.length === 0 }, icone("recibo"), "Pedir a conta");
        btn.onclick = () => confirmar({
            titulo: "Pedir a conta?",
            texto: el("div", null,
                el("p", null, `Mesa ${conta.mesa} · ${conta.pedidos.length} pedido(s)`),
                el("div", { class: "total-linha" }, el("span", null, "Total"), el("span", null, dinheiro(conta.total))),
                el("p", { class: "suave mt" }, "Depois disso, a mesa não aceita novos pedidos até o caixa fechar a conta.")),
            botao: "Pedir a conta",
            acao: async () => {
                await api("POST", "api/cliente/pagamento.php");
                setTimeout(atualizarPedidos, 300);
                return "Conta pedida! Um atendente vai até a sua mesa.";
            },
        });

        contaResumo.replaceChildren(el("div", { class: "card" },
            el("div", { class: "linha" },
                el("div", { class: "metrica" },
                    el("span", { class: "rotulo" }, `Mesa ${conta.mesa} · ${conta.pedidos.length} pedido(s)`),
                    el("span", { class: "valor" }, dinheiro(conta.total))),
                el("div", { class: "direita" }, btn)),
            aviso ? el("div", { class: "mt" }, aviso) : null));
    }

    /* ---------------- Início ---------------- */
    salvarCarrinho();
    carregarCardapio();
    polling(atualizarPedidos, 5000);
})();
