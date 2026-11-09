// Tela da cozinha: recebido → em_preparo → pronto
"use strict";
(() => {
    const { api, el, icone, toast, tremer, comCarregando, polling } = PJ;

    const ACOES = {
        recebido: { proximo: "em_preparo", rotulo: "Iniciar preparo", classe: "btn", aviso: "em preparo" },
        em_preparo: { proximo: "pronto", rotulo: "Marcar como pronto", classe: "btn btn-ok", aviso: "pronto" },
    };

    function card(p) {
        const acao = ACOES[p.status];
        const btn = el("button", { class: acao.classe, type: "button" },
            icone(acao.proximo === "pronto" ? "check" : "relogio"), acao.rotulo);
        const no = cardDePedido(p, btn);
        btn.onclick = () => comCarregando(btn, async () => {
            filas[p.status].segurar(p.id);
            try {
                await api("PUT", "api/cozinha/status.php", { pedido_id: p.id, status: acao.proximo });
                toast(`Pedido #${p.id} · Mesa ${p.mesa} agora está ${acao.aviso}.`, "ok", 3000);
                filas[p.status].retirar(p.id);
                ciclo.agora();
            } catch (e) {
                filas[p.status].soltar(p.id);
                tremer(no);
                toast(e.message, "erro");
                ciclo.agora();   // outra pessoa pode ter mudado o pedido
            }
        });
        return no;
    }

    const filas = {
        recebido: criarFila(document.getElementById("col-recebido"), {
            criarCard: card,
            aoEntrar: p => {
                toast(`Novo pedido #${p.id} · Mesa ${p.mesa}`, "info", 6000);
                if (navigator.vibrate) navigator.vibrate(200);
            },
            vazioTitulo: "Nenhum pedido novo", vazioTexto: "Os pedidos aparecem aqui assim que o cliente envia.",
        }),
        em_preparo: criarFila(document.getElementById("col-em_preparo"), {
            criarCard: card, vazioTitulo: "Nada no fogo", vazioTexto: "Inicie o preparo de um pedido recebido.",
        }),
    };

    const ciclo = polling(async () => {
        const pedidos = await api("GET", "api/cozinha/pedidos.php");
        for (const status of ["recebido", "em_preparo"]) {
            const lista = pedidos.filter(p => p.status === status);
            filas[status].atualizar(lista);
            document.getElementById(`n-${status}`).textContent = lista.length;
        }
        document.getElementById("atualizado").textContent =
            "Atualizado às " + new Date().toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    }, 4000);
})();
