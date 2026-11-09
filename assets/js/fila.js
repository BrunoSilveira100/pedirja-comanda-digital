// Lista de pedidos atualizada por polling, sem redesenhar tudo a cada ciclo:
// cards que não mudaram ficam no lugar (só o tempo é atualizado), novos entram
// com animação e os que saíram somem com fade.
"use strict";

function criarFila(container, { criarCard, aoEntrar, vazioTitulo, vazioTexto }) {
    const { el, haQuanto, minutos, vazio } = PJ;
    const nos = new Map();     // id → { no, status }
    const segurados = new Set();   // cards com ação em andamento: o polling não mexe neles
    let primeira = true;

    function atualizar(pedidos) {
        pedidos = pedidos.filter(p => !segurados.has(p.id) || nos.has(p.id));
        const ids = new Set(pedidos.map(p => p.id));

        for (const [id, item] of nos) {
            if (segurados.has(id)) continue;
            if (!ids.has(id)) {
                nos.delete(id);
                item.no.classList.add("saindo");
                setTimeout(() => item.no.remove(), PJ.reduzirMovimento ? 0 : 300);
            }
        }

        let anterior = null;
        for (const p of pedidos) {
            let item = nos.get(p.id);
            if (segurados.has(p.id)) { anterior = item.no; continue; }
            if (!item || item.status !== p.status) {
                const novo = criarCard(p);
                if (!primeira && !item) {
                    novo.classList.add("entra-esq");
                    if (aoEntrar) aoEntrar(p);
                }
                if (item) item.no.replaceWith(novo);
                item = { no: novo, status: p.status };
                nos.set(p.id, item);
            }
            const tempo = item.no.querySelector("[data-tempo]");
            if (tempo) {
                tempo.textContent = haQuanto(p.criado_em);
                tempo.classList.toggle("atrasado", minutos(p.criado_em) >= 15);
            }
            // garante a ordem (mais antigo primeiro)
            const alvo = anterior ? anterior.nextElementSibling : container.firstElementChild;
            if (alvo !== item.no) container.insertBefore(item.no, alvo);
            anterior = item.no;
        }

        const vazioEl = container.querySelector(".vazio");
        if (!pedidos.length && !vazioEl) container.append(vazio(vazioTitulo, vazioTexto));
        if (pedidos.length && vazioEl) vazioEl.remove();

        if (primeira) {
            container.querySelectorAll(".skeleton").forEach(s => s.remove());
            PJ.escalonar(container);
        }
        primeira = false;
    }

    // remove um card na hora (depois de uma ação bem-sucedida)
    function segurar(id) { segurados.add(id); }
    function soltar(id) { segurados.delete(id); }

    function retirar(id) {
        segurados.delete(id);
        const item = nos.get(id);
        if (!item) return;
        nos.delete(id);
        item.no.classList.add("saindo");
        setTimeout(() => item.no.remove(), PJ.reduzirMovimento ? 0 : 300);
    }

    container.replaceChildren(...PJ.skeletonCards(2));
    return { atualizar, retirar, segurar, soltar };
}

// Card padrão de pedido para cozinha e garçom
function cardDePedido(p, botao) {
    const { el, selo } = PJ;
    return el("article", { class: "card interativo", "data-id": p.id },
        el("div", { class: "pedido-topo" },
            el("span", { class: "mesa-grande" }, `Mesa ${p.mesa}`),
            selo(p.status)),
        el("div", { class: "linha suave", style: "margin-bottom:12px" },
            el("span", null, `Pedido #${p.id} · ${p.cliente}`),
            el("span", { class: "tempo direita", "data-tempo": "" })),
        el("ul", { class: "itens" }, p.itens.map(i => el("li", null,
            el("span", { class: "q" }, `${i.quantidade}×`),
            el("span", null, i.nome, i.observacao ? el("span", { class: "obs" }, `⚠ ${i.observacao}`) : null)))),
        el("div", { class: "pedido-rodape" }, botao));
}
