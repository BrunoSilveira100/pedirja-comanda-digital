// Tela do garçom: pronto → entregue
"use strict";
(() => {
    const { api, el, icone, iconeSucesso, toast, tremer, comCarregando, polling } = PJ;

    function card(p) {
        const btn = el("button", { class: "btn btn-ok btn-bloco", type: "button" }, icone("check"), "Confirmar entrega");
        const no = cardDePedido(p, btn);
        btn.onclick = () => comCarregando(btn, async () => {
            fila.segurar(p.id);
            try {
                await api("PUT", "api/garcom/entregar.php", { pedido_id: p.id });
                // mostra o check desenhando e tira o card
                no.classList.add("concluido");
                no.querySelector(".pedido-rodape").replaceChildren(
                    el("div", { class: "linha", style: "width:100%;justify-content:center" },
                        iconeSucesso(), el("strong", null, `Entregue na mesa ${p.mesa}`)));
                setTimeout(() => { fila.retirar(p.id); ciclo.agora(); }, 1300);
            } catch (e) {
                fila.soltar(p.id);
                tremer(no);
                toast(e.message, "erro");
                ciclo.agora();
            }
        });
        return no;
    }

    const fila = criarFila(document.getElementById("lista-prontos"), {
        criarCard: card,
        aoEntrar: p => {
            toast(`Pedido #${p.id} pronto para a mesa ${p.mesa}!`, "ok", 7000);
            if (navigator.vibrate) navigator.vibrate([150, 80, 150]);
        },
        vazioTitulo: "Nada para entregar agora",
        vazioTexto: "Os pedidos prontos aparecem aqui automaticamente.",
    });

    const ciclo = polling(async () => {
        const pedidos = await api("GET", "api/garcom/pedidos.php");
        fila.atualizar(pedidos);
        document.getElementById("n-prontos").textContent = pedidos.length;
        document.getElementById("atualizado").textContent =
            "Atualizado às " + new Date().toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    }, 4000);
})();
