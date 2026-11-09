// Painel do admin/caixa: cada seção é uma função que desenha em #admin-conteudo.
"use strict";
(() => {
    const { api, el, icone, dinheiro, hora, selo, escalonar, skeletonCards, skeletonLinhas, vazio,
            toast, tremer, comCarregando, modal, sucessoNoModal, confirmar, STATUS } = PJ;

    const raiz = document.getElementById("admin-conteudo");
    const tituloCabecalho = document.querySelector(".cabecalho h1");
    let timerAtualizacao = null;
    let meuId = null;

    const SECOES = {
        "#visao": ["Visão geral", visao],
        "#caixa": ["Caixa", caixa],
        "#pedidos": ["Pedidos", pedidos],
        "#cardapio": ["Cardápio", cardapio],
        "#usuarios": ["Usuários", usuarios],
    };

    function tituloAnimado(texto, destaque) {
        const palavras = texto.split(" ");
        return el("h2", { class: "titulo-animado", style: "font-size:clamp(1.8rem,4vw,2.8rem)" },
            palavras.flatMap((p, i) => {
                const ehDestaque = p.replace(/[.,!?]/g, "").toLowerCase() === destaque;
                const span = el("span", { class: ehDestaque ? "destaque" : null,
                    style: `--i:${i}` + (ehDestaque ? `;--d:${palavras.length * 0.15 + 0.55}s` : "") }, p);
                return [el("span", { class: "p" }, span), " "];
            }));
    }

    function topoSecao(titulo, sub, ...acoes) {
        return el("div", { class: "secao-topo cascata cascata-2" },
            el("div", null, el("h2", null, titulo), sub ? el("p", { class: "suave" }, sub) : null),
            acoes.length ? el("div", { class: "acoes" }, acoes) : null);
    }

    function erroNaTela(e) {
        raiz.replaceChildren(el("div", { class: "alerta erro entra" }, icone("alerta"), e.message));
    }

    function agendar(fn, ms) {
        clearTimeout(timerAtualizacao);
        timerAtualizacao = setTimeout(() => { if (!document.hidden) fn(true); else agendar(fn, ms); }, ms);
    }

    async function navegar() {
        clearTimeout(timerAtualizacao);
        const [titulo, fn] = SECOES[location.hash] || SECOES["#visao"];
        tituloCabecalho.textContent = titulo;
        document.title = `${titulo} · PedirJá`;
        window.scrollTo({ top: 0 });
        await fn(false);
    }

    document.getElementById("btn-atualizar").onclick = () => navegar();
    window.addEventListener("hashchange", navegar);

    /* ================= Visão geral ================= */

    async function visao(silencioso) {
        if (!silencioso) raiz.replaceChildren(el("div", { class: "grade-estreita" }, skeletonCards(4)));
        let d;
        try { d = await api("GET", "api/admin/dashboard.php"); } catch (e) { return erroNaTela(e); }

        const metricas = el("div", { class: "grade-estreita" },
            metrica("Faturamento hoje", dinheiro(d.faturamento_hoje)),
            metrica("Contas fechadas", d.pedidos_finalizados_hoje),
            metrica("Ticket médio", dinheiro(d.ticket_medio)),
            metrica("Em aberto (ainda não pago)", dinheiro(d.valor_em_aberto)));

        const maxStatus = Math.max(1, ...Object.values(d.pedidos_por_status));
        const porStatus = el("div", { class: "card" }, el("h3", { style: "margin-bottom:14px" }, "Pedidos de hoje por situação"),
            Object.keys(STATUS).filter(s => !["livre", "ocupada", "aguardando_pagamento"].includes(s)).map(s =>
                barra(STATUS[s], d.pedidos_por_status[s] || 0, maxStatus)));

        const mesas = el("div", { class: "card" }, el("h3", { style: "margin-bottom:14px" }, "Mesas agora"),
            el("div", { class: "linha" }, ["livre", "ocupada", "aguardando_pagamento"].map(s =>
                el("span", { class: `selo ${s}` }, `${d.mesas_por_status[s] || 0} · ${STATUS[s]}`))),
            (d.mesas_por_status.aguardando_pagamento || 0) > 0
                ? el("a", { class: "btn mt", href: "#caixa" }, icone("recibo"), "Ir para o caixa") : null);

        const maxVend = Math.max(1, ...d.mais_vendidos_hoje.map(v => v.quantidade));
        const vendidos = el("div", { class: "card" }, el("h3", { style: "margin-bottom:14px" }, "Mais pedidos hoje"),
            d.mais_vendidos_hoje.length
                ? d.mais_vendidos_hoje.map(v => barra(v.nome, v.quantidade, maxVend))
                : el("p", { class: "suave" }, "Ainda não há pedidos hoje."));

        const equipe = el("div", { class: "card" }, el("h3", { style: "margin-bottom:14px" }, "Pessoas cadastradas"),
            el("div", { class: "linha" }, Object.entries({ cliente: "Clientes", cozinha: "Cozinha", garcom: "Garçons", admin: "Admins" })
                .map(([k, r]) => el("span", { class: "selo" }, `${d.usuarios_por_perfil[k] || 0} ${r}`))));

        const grade = el("div", { class: "grade mt-g" }, porStatus, mesas, vendidos, equipe);
        if (silencioso) {
            raiz.querySelector(".grade-estreita")?.replaceWith(metricas);
            raiz.querySelector(".grade.mt-g")?.replaceWith(grade);
        } else {
            raiz.replaceChildren(
                el("section", { class: "hero", style: "margin-bottom:24px" }, tituloAnimado("Como está o dia hoje.", "hoje"),
                    el("p", { class: "sub cascata cascata-4" }, `Dados de ${new Date(d.data + "T12:00").toLocaleDateString("pt-BR")}. Atualiza a cada 15 segundos.`)),
                metricas, grade);
            escalonar(metricas);
            escalonar(grade);
        }
        agendar(visao, 15000);
    }

    function metrica(rotulo, valor) {
        return el("div", { class: "card metrica" }, el("span", { class: "rotulo" }, rotulo), el("span", { class: "valor" }, valor));
    }

    function barra(rotulo, valor, max) {
        return el("div", { class: "barra-h" }, el("span", null, rotulo),
            el("span", { class: "trilho" }, el("i", { style: `width:${(valor / max) * 100}%` })),
            el("strong", { class: "num" }, valor));
    }

    /* ================= Caixa ================= */

    async function caixa(silencioso) {
        if (!silencioso) raiz.replaceChildren(topoSecao("Contas pedidas", "Mesas aguardando pagamento."), el("div", { class: "grade" }, skeletonCards(2)));
        let contas;
        try { contas = await api("GET", "api/admin/caixa.php"); } catch (e) { return erroNaTela(e); }

        const lista = el("div", { class: "grade" });
        if (!contas.length) {
            lista.append(vazio("Nenhuma conta pedida", "Quando um cliente pedir a conta, a mesa aparece aqui.", "recibo"));
        }
        for (const c of contas) {
            const btn = el("button", { class: "btn btn-ok btn-bloco", type: "button" }, icone("check"), `Receber ${dinheiro(c.total)}`);
            btn.onclick = () => confirmar({
                titulo: `Fechar a conta da mesa ${c.mesa}?`,
                texto: el("div", null,
                    el("p", null, `${c.pedidos.length} pedido(s). Os pedidos serão finalizados e a mesa ficará livre.`),
                    el("div", { class: "total-linha" }, el("span", null, "Total a receber"), el("span", null, dinheiro(c.total)))),
                botao: "Confirmar pagamento",
                acao: async () => {
                    const r = await api("POST", "api/admin/caixa.php", { mesa_id: c.mesa_id });
                    setTimeout(() => caixa(true), 1700);
                    return `Mesa ${r.mesa} liberada · ${dinheiro(r.total)} recebido`;
                },
            });
            lista.append(el("article", { class: "card interativo" },
                el("div", { class: "pedido-topo" }, el("span", { class: "mesa-grande" }, `Mesa ${c.mesa}`), selo(c.status_mesa)),
                c.pedidos.map(p => el("div", { style: "padding:10px 0;border-top:1px solid var(--border)" },
                    el("div", { class: "linha" }, el("strong", null, `Pedido #${p.id}`),
                        el("span", { class: "suave" }, `${p.cliente} · ${hora(p.criado_em)}`),
                        el("strong", { class: "direita" }, dinheiro(p.total))),
                    el("div", { class: "suave", style: "font-size:.88rem" },
                        p.itens.map(i => `${i.quantidade}× ${i.nome}`).join(", ")))),
                el("div", { class: "total-linha" }, el("span", null, "Total"), el("span", null, dinheiro(c.total))),
                el("div", { class: "mt" }, btn)));
        }
        const atual = raiz.querySelector(".grade");
        if (silencioso && atual) atual.replaceWith(lista);
        else {
            raiz.replaceChildren(topoSecao("Contas pedidas", "Mesas aguardando pagamento. Atualiza a cada 8 segundos."), lista);
            escalonar(lista);
        }
        agendar(caixa, 8000);
    }

    /* ================= Pedidos ================= */

    let filtroStatus = "";
    async function pedidos() {
        const chips = el("div", { class: "chips cascata cascata-3", role: "group", "aria-label": "Filtrar por situação" },
            [["", "Todos"], ...["recebido", "em_preparo", "pronto", "entregue", "solicitado_pagamento", "finalizado"].map(s => [s, STATUS[s]])]
                .map(([v, r]) => el("button", { class: "chip", type: "button", "aria-pressed": String(v === filtroStatus),
                    onclick: () => { filtroStatus = v; pedidos(); } }, r)));
        const area = el("div", null, skeletonLinhas(5));
        raiz.replaceChildren(topoSecao("Acompanhamento", "Os 100 pedidos mais recentes."), chips, area);

        let lista;
        try { lista = await api("GET", "api/admin/pedidos.php" + (filtroStatus ? `?status=${filtroStatus}` : "")); }
        catch (e) { area.replaceChildren(el("div", { class: "alerta erro" }, e.message)); return; }

        if (!lista.length) { area.replaceChildren(vazio("Nenhum pedido", "Não há pedidos nesta situação.", "recibo")); return; }
        area.replaceChildren(el("div", { class: "tabela-rolagem entra" }, el("table", null,
            el("thead", null, el("tr", null, ["Pedido", "Mesa", "Cliente", "Itens", "Hora", "Situação"].map(t => el("th", null, t)),
                el("th", { class: "num" }, "Total"))),
            el("tbody", null, lista.map(p => el("tr", null,
                el("td", null, el("strong", null, `#${p.id}`)),
                el("td", null, p.mesa),
                el("td", null, p.cliente),
                el("td", null, p.itens.map(i => `${i.quantidade}× ${i.nome}`).join(", ")),
                el("td", null, hora(p.criado_em)),
                el("td", null, selo(p.status)),
                el("td", { class: "num" }, dinheiro(p.total))))))));
    }

    /* ================= Cardápio ================= */

    async function cardapio() {
        const btnNovo = el("button", { class: "btn", type: "button", onclick: () => formProduto() }, icone("mais"), "Novo produto");
        const area = el("div", null, skeletonLinhas(6));
        raiz.replaceChildren(topoSecao("Cardápio", "Itens indisponíveis não aparecem para o cliente.", btnNovo), area);

        let lista;
        try { lista = await api("GET", "api/admin/produtos.php"); } catch (e) { return erroNaTela(e); }
        cacheCategorias = [...new Set(lista.map(p => p.categoria))];

        if (!lista.length) { area.replaceChildren(vazio("Cardápio vazio", "Cadastre o primeiro produto.")); return; }
        area.replaceChildren(el("div", { class: "tabela-rolagem entra" }, el("table", null,
            el("thead", null, el("tr", null, el("th", null, "Produto"), el("th", null, "Categoria"),
                el("th", { class: "num" }, "Preço"), el("th", null, "Disponível"), el("th", { class: "num" }, "Ações"))),
            el("tbody", null, lista.map(linhaProduto)))));
    }

    let cacheCategorias = [];

    function linhaProduto(p) {
        const chk = el("input", { type: "checkbox", checked: p.disponivel, "aria-label": `${p.nome} disponível` });
        chk.onchange = async () => {
            chk.disabled = true;
            try {
                await api("PUT", "api/admin/produtos.php", { id: p.id, disponivel: chk.checked });
                p.disponivel = chk.checked;
                toast(`${p.nome} agora está ${chk.checked ? "disponível" : "indisponível"}.`, "ok", 2500);
            } catch (e) {
                chk.checked = !chk.checked;
                toast(e.message, "erro");
            } finally { chk.disabled = false; }
        };
        return el("tr", null,
            el("td", null, el("div", { class: "produto-linha" },
                p.imagem ? el("img", { class: "miniatura", src: p.imagem, alt: "", loading: "lazy", width: 48, height: 48 })
                         : el("span", { class: "miniatura sem-foto", "aria-hidden": "true" }, icone("prato")),
                el("div", null, el("strong", null, p.nome), el("small", null, p.descricao || "")))),
            el("td", null, p.categoria),
            el("td", { class: "num" }, dinheiro(p.preco)),
            el("td", null, chk),
            el("td", { class: "num" }, el("div", { class: "linha", style: "justify-content:flex-end;flex-wrap:nowrap" },
                el("button", { class: "btn btn-sec btn-icone", type: "button", "aria-label": `Editar ${p.nome}`,
                    onclick: () => formProduto(p) }, icone("editar")),
                el("button", { class: "btn btn-sec btn-icone", type: "button", "aria-label": `Excluir ${p.nome}`,
                    onclick: () => confirmar({
                        titulo: `Excluir “${p.nome}”?`,
                        texto: "Produtos que já aparecem em pedidos não podem ser excluídos; nesse caso, desmarque “Disponível”.",
                        botao: "Excluir", perigo: true,
                        acao: async () => {
                            await api("DELETE", `api/admin/produtos.php?id=${p.id}`);
                            setTimeout(cardapio, 1700);
                            return "Produto excluído.";
                        },
                    }) }, icone("lixo")))));
    }

    function formProduto(p) {
        const editando = !!p;
        const campo = (id, rotulo, input, dica) =>
            el("div", { class: "campo" }, el("label", { for: id }, rotulo), input, dica ? el("small", null, dica) : null);
        const nome = el("input", { id: "f-nome", maxlength: 120, required: true, value: p?.nome || "" });
        const desc = el("textarea", { id: "f-desc", maxlength: 500 }, p?.descricao || "");
        const preco = el("input", { id: "f-preco", type: "number", step: "0.01", min: "0.01", required: true, value: p ? String(p.preco) : "" });
        const lista = el("datalist", { id: "f-cats" }, cacheCategorias.map(c => el("option", { value: c })));
        const cat = el("input", { id: "f-cat", maxlength: 50, required: true, list: "f-cats", value: p?.categoria || "" });
        const disp = el("input", { id: "f-disp", type: "checkbox", checked: p ? p.disponivel : true });
        const erro = el("div", { class: "alerta erro", role: "alert", hidden: true });

        // Foto: pré-visualização + escolher arquivo + (ao editar) remover
        const foto = el("input", { id: "f-foto", type: "file", accept: "image/jpeg,image/png,image/webp" });
        const previa = el("div", { class: "previa-foto" });
        const remover = el("input", { id: "f-remover", type: "checkbox" });
        const linhaRemover = p?.imagem
            ? el("label", { class: "linha", for: "f-remover", style: "font-weight:600" }, remover, "Remover a foto atual")
            : null;
        let urlPrevia = null;
        const mostrarPrevia = src => previa.replaceChildren(src
            ? el("img", { src, alt: "Pré-visualização da foto" })
            : el("span", { class: "suave" }, icone("prato"), "Sem foto"));
        mostrarPrevia(p?.imagem || null);
        foto.onchange = () => {
            if (urlPrevia) URL.revokeObjectURL(urlPrevia);
            const arq = foto.files[0];
            if (arq && arq.size > 3 * 1024 * 1024) {
                erro.textContent = "A foto deve ter no máximo 3 MB.";
                erro.hidden = false;
                foto.value = "";
                return;
            }
            erro.hidden = true;
            remover.checked = false;
            urlPrevia = arq ? URL.createObjectURL(arq) : null;
            mostrarPrevia(urlPrevia || p?.imagem || null);
        };
        remover.onchange = () => {
            if (remover.checked) { foto.value = ""; mostrarPrevia(null); } else { mostrarPrevia(p?.imagem || null); }
        };

        const form = el("form", { novalidate: true },
            el("div", { class: "campo-foto" }, previa,
                el("div", { class: "campo", style: "margin:0" }, el("label", { for: "f-foto" }, "Foto"), foto,
                    el("small", null, "JPG, PNG ou WEBP, até 3 MB. Fica melhor na horizontal (4:3)."), linhaRemover)),
            campo("f-nome", "Nome", nome),
            campo("f-desc", "Descrição", desc, "Opcional."),
            el("div", { class: "linha-campos" }, campo("f-preco", "Preço (R$)", preco), campo("f-cat", "Categoria", cat), lista),
            el("label", { class: "linha", for: "f-disp", style: "margin-bottom:14px;font-weight:600" }, disp, "Disponível no cardápio"),
            erro);
        const btnSalvar = el("button", { class: "btn", type: "submit" }, editando ? "Salvar alterações" : "Cadastrar produto");
        const btnCancelar = el("button", { class: "btn btn-sec", type: "button" }, "Cancelar");
        form.append(el("button", { type: "submit", hidden: true }));   // Enter envia

        const m = modal({ titulo: editando ? `Editar ${p.nome}` : "Novo produto", corpo: form, rodape: [btnCancelar, btnSalvar] });
        btnCancelar.onclick = m.fechar;
        btnSalvar.onclick = e => { e.preventDefault(); form.requestSubmit(); };
        form.onsubmit = e => {
            e.preventDefault();
            comCarregando(btnSalvar, async () => {
                const dados = { nome: nome.value.trim(), descricao: desc.value.trim(), preco: preco.value,
                                categoria: cat.value.trim(), disponivel: disp.checked };
                try {
                    const salvo = editando
                        ? await api("PUT", "api/admin/produtos.php", { id: p.id, ...dados })
                        : await api("POST", "api/admin/produtos.php", dados);
                    // A foto vai numa segunda requisição (arquivo), depois que o produto já tem id
                    if (foto.files[0] || remover.checked) {
                        const envio = new FormData();
                        envio.append("id", salvo.id);
                        if (foto.files[0]) envio.append("imagem", foto.files[0]);
                        else envio.append("remover", "1");
                        try {
                            await api("POST", "api/admin/produto_imagem.php", envio);
                        } catch (errFoto) {
                            // os dados já foram salvos; avisa só da foto
                            toast(`${editando ? "Produto atualizado" : "Produto cadastrado"}, mas a foto não foi salva: ${errFoto.message}`, "erro", 7000);
                            m.fechar();
                            cardapio();
                            return;
                        }
                    }
                    sucessoNoModal(m, editando ? "Produto atualizado." : "Produto cadastrado.");
                    setTimeout(cardapio, 1700);
                } catch (err) {
                    erro.textContent = err.message;
                    erro.hidden = false;
                    tremer(m.painel);
                }
            });
        };
    }

    /* ================= Usuários ================= */

    const PERFIS = { cliente: "Cliente", cozinha: "Cozinha", garcom: "Garçom", admin: "Admin" };

    async function usuarios() {
        const btnNovo = el("button", { class: "btn", type: "button", onclick: formUsuario }, icone("mais"), "Novo usuário");
        const area = el("div", null, skeletonLinhas(6));
        raiz.replaceChildren(topoSecao("Usuários", "A equipe é cadastrada aqui; clientes também podem se cadastrar sozinhos.", btnNovo), area);

        let lista;
        try {
            if (meuId === null) meuId = (await api("GET", "api/me.php")).usuario.id;
            lista = await api("GET", "api/admin/usuarios.php");
        } catch (e) { return erroNaTela(e); }

        area.replaceChildren(el("div", { class: "tabela-rolagem entra" }, el("table", null,
            el("thead", null, el("tr", null, ["Nome", "Perfil", "Desde"].map(t => el("th", null, t)), el("th", { class: "num" }, "Ações"))),
            el("tbody", null, lista.map(linhaUsuario)))));
    }

    function linhaUsuario(u) {
        const souEu = u.id === meuId;
        const sel = el("select", { "aria-label": `Perfil de ${u.nome}`, disabled: souEu, style: "min-width:140px" },
            Object.entries(PERFIS).map(([v, r]) => el("option", { value: v, selected: v === u.perfil }, r)));
        sel.onchange = async () => {
            const anterior = u.perfil;
            sel.disabled = true;
            try {
                await api("PUT", "api/admin/usuarios.php", { id: u.id, perfil: sel.value });
                u.perfil = sel.value;
                toast(`${u.nome} agora é ${PERFIS[sel.value]}.`, "ok", 2500);
            } catch (e) {
                sel.value = anterior;
                toast(e.message, "erro");
            } finally { sel.disabled = souEu; }
        };
        return el("tr", null,
            el("td", null, el("strong", null, u.nome, souEu ? " (você)" : ""), el("small", null, u.email)),
            el("td", null, sel),
            el("td", null, new Date(u.criado_em.replace(" ", "T")).toLocaleDateString("pt-BR")),
            el("td", { class: "num" }, el("button", { class: "btn btn-sec btn-icone", type: "button", disabled: souEu,
                "aria-label": `Excluir ${u.nome}`,
                onclick: () => confirmar({
                    titulo: `Excluir ${u.nome}?`,
                    texto: "Usuários com pedidos registrados não podem ser excluídos.",
                    botao: "Excluir", perigo: true,
                    acao: async () => {
                        await api("DELETE", `api/admin/usuarios.php?id=${u.id}`);
                        setTimeout(usuarios, 1700);
                        return "Usuário excluído.";
                    },
                }) }, icone("lixo"))));
    }

    function formUsuario() {
        const campo = (id, rotulo, input, dica) =>
            el("div", { class: "campo" }, el("label", { for: id }, rotulo), input, dica ? el("small", null, dica) : null);
        const nome = el("input", { id: "u-nome", maxlength: 100, autocomplete: "off" });
        const email = el("input", { id: "u-email", type: "email", maxlength: 150, autocomplete: "off" });
        const senha = el("input", { id: "u-senha", type: "password", minlength: 6, autocomplete: "new-password" });
        const perfil = el("select", { id: "u-perfil" },
            Object.entries(PERFIS).map(([v, r]) => el("option", { value: v, selected: v === "garcom" }, r)));
        const erro = el("div", { class: "alerta erro", role: "alert", hidden: true, style: "display:block" });
        const form = el("form", { novalidate: true },
            campo("u-nome", "Nome", nome),
            campo("u-email", "E-mail", email),
            el("div", { class: "linha-campos" }, campo("u-senha", "Senha inicial", senha, "Mínimo de 6 caracteres."), campo("u-perfil", "Perfil", perfil)),
            erro, el("button", { type: "submit", hidden: true }));
        const btnSalvar = el("button", { class: "btn", type: "button" }, "Criar usuário");
        const btnCancelar = el("button", { class: "btn btn-sec", type: "button" }, "Cancelar");
        const m = modal({ titulo: "Novo usuário", corpo: form, rodape: [btnCancelar, btnSalvar] });
        btnCancelar.onclick = m.fechar;
        btnSalvar.onclick = () => form.requestSubmit();
        form.onsubmit = e => {
            e.preventDefault();
            comCarregando(btnSalvar, async () => {
                try {
                    await api("POST", "api/admin/usuarios.php",
                        { nome: nome.value.trim(), email: email.value.trim(), senha: senha.value, perfil: perfil.value });
                    sucessoNoModal(m, "Usuário criado.");
                    setTimeout(usuarios, 1700);
                } catch (err) {
                    erro.replaceChildren(...[err.message, ...((err.dados && err.dados.detalhes) || [])]
                        .map(t => el("div", null, t)));
                    erro.hidden = false;
                    tremer(m.painel);
                }
            });
        };
    }

    navegar();
})();
