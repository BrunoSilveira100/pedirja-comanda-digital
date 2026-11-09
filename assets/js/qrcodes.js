// Tela de QR Codes das mesas: desenha um código por mesa com o endereço escolhido.
"use strict";
(() => {
    const pagina = document.getElementById("qr-pagina");
    if (!pagina || !pagina.dataset.endereco) return;   // sem rede: a página mostra só o aviso

    const endereco = pagina.dataset.endereco;   // ex.: http://192.168.0.86 (descoberto pelo servidor)
    const caminho = pagina.dataset.caminho;     // ex.: /perdirja/
    const cartoes = pagina.querySelectorAll("[data-token]");

    const NS = "http://www.w3.org/2000/svg";
    // Margem branca em módulos. No cartão bastam 2: o fundo do cartão também é branco e completa a margem.
    // No modo ampliado usa 4, o recomendado pelo padrão QR.
    const BORDA_CARTAO = 2;
    const BORDA_AMPLIADO = 4;
    const COR_MODULO = "#221c16";   // marrom quase preto do sistema: contraste alto para a câmera

    /**
     * Desenha o QR Code como SVG: cada módulo é um quadrado exato, nítido em qualquer tamanho
     * (tela, celular ou impressão). Reduzir uma imagem de pixels deformava os módulos.
     */
    function svgDoQr(texto, BORDA = BORDA_CARTAO) {
        const temp = document.createElement("div");
        const qr = new QRCode(temp, { text: texto, correctLevel: QRCode.CorrectLevel.M });
        const modelo = qr._oQRCode;
        if (!modelo || typeof modelo.getModuleCount !== "function") return temp;   // reserva: imagem da biblioteca

        const n = modelo.getModuleCount();
        const total = n + BORDA * 2;
        let caminhoSvg = "";
        for (let linha = 0; linha < n; linha++) {
            for (let coluna = 0; coluna < n; coluna++) {
                if (modelo.isDark(linha, coluna)) caminhoSvg += `M${coluna + BORDA} ${linha + BORDA}h1v1h-1z`;
            }
        }
        const svg = document.createElementNS(NS, "svg");
        svg.setAttribute("viewBox", `0 0 ${total} ${total}`);
        svg.setAttribute("shape-rendering", "crispEdges");
        svg.setAttribute("aria-hidden", "true");
        const fundo = document.createElementNS(NS, "rect");
        fundo.setAttribute("width", total);
        fundo.setAttribute("height", total);
        fundo.setAttribute("fill", "#ffffff");
        const modulos = document.createElementNS(NS, "path");
        modulos.setAttribute("d", caminhoSvg);
        modulos.setAttribute("fill", COR_MODULO);
        svg.append(fundo, modulos);
        return svg;
    }

    function desenhar() {
        for (const cartao of cartoes) {
            const url = `${endereco}${caminho}paginas/mesa.php?t=${encodeURIComponent(cartao.dataset.token)}`;
            const alvo = cartao.querySelector(".qr");
            alvo.replaceChildren(window.QRCode ? svgDoQr(url) : document.createTextNode("Use o link abaixo."));
            cartao.querySelector(".qr-link").textContent = url;
            cartao.querySelector("[data-copiar]").dataset.url = url;
        }
    }

    // Mostra um código sozinho, bem grande: o jeito mais fácil de ler com a câmera apontada para o monitor
    function ampliar(cartao) {
        const numero = cartao.querySelector("[data-ampliar]").dataset.ampliar;
        const url = cartao.querySelector("[data-copiar]").dataset.url;
        const grande = PJ.el("div", { class: "qr-ampliado" }, svgDoQr(url, BORDA_AMPLIADO));
        const btnFechar = PJ.el("button", { class: "btn", type: "button" }, "Fechar");
        const m = PJ.modal({
            titulo: `Mesa ${numero}`,
            corpo: PJ.el("div", { class: "qr-modal" }, grande,
                PJ.el("p", { class: "suave" }, "Aponte a câmera do celular. Se não ler, afaste ou aproxime um pouco o celular.")),
            rodape: [btnFechar],
        });
        btnFechar.onclick = m.fechar;
    }

    pagina.addEventListener("click", async evento => {
        const alvoAmpliar = evento.target.closest("[data-ampliar]");
        if (alvoAmpliar) { ampliar(alvoAmpliar.closest("[data-token]")); return; }

        const botao = evento.target.closest("[data-copiar]");
        if (!botao) return;
        try {
            await navigator.clipboard.writeText(botao.dataset.url);
            PJ.toast("Link copiado.", "ok", 2000);
        } catch {
            PJ.toast("Não deu para copiar. Selecione o link abaixo do código e copie à mão.", "erro");
        }
    });

    document.getElementById("btn-imprimir")?.addEventListener("click", () => window.print());
    desenhar();
})();
