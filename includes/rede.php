<?php
// includes/rede.php — descobre em qual endereço o celular encontra este computador.
//
// O IP muda conforme a rede (Wi-Fi de casa, hotspot do celular...). Em vez de digitar o IP
// à mão, o servidor pergunta ao sistema quais placas de rede estão ligadas e escolhe a real
// (Wi-Fi ou cabo), ignorando as placas virtuais (Hyper-V/WSL, VirtualBox, VMware...).

/** Placas que o celular nunca alcança. */
const PLACAS_VIRTUAIS = '/hyper-v|vethernet|wsl|virtual|virtualbox|vmware|loopback|wi-fi direct|bluetooth|tap-|vpn|tunnel/i';

/**
 * Endereço (ex.: "http://192.168.0.86") pelo qual o celular abre o sistema, ou null se o
 * computador não estiver em nenhuma rede.
 */
function enderecoDaRede(): ?string
{
    $porta = (int) ($_SERVER["SERVER_PORT"] ?? 80);
    $sufixoPorta = in_array($porta, [80, 443], true) ? "" : ":$porta";
    $esquema = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";

    $ip = ipDoAcessoAtual() ?? ipDaPlacaReal() ?? ipPorNomeDaMaquina();
    return $ip ? "$esquema://$ip$sufixoPorta" : null;
}

/** Se a página já foi aberta pelo IP da rede (ex.: no celular), esse é o certo. */
function ipDoAcessoAtual(): ?string
{
    $host = preg_replace('/:\d+$/', "", (string) ($_SERVER["HTTP_HOST"] ?? ""));
    return ipDeRedeLocal($host) && !ehIpVirtual($host) ? $host : null;
}

/** IPv4 da placa de rede real e ligada (Wi-Fi ou cabo), lida pelo nome da placa. */
function ipDaPlacaReal(): ?string
{
    if (!function_exists("net_get_interfaces")) {
        return null;
    }
    $candidatos = [];
    foreach (net_get_interfaces() ?: [] as $nome => $placa) {
        $descricao = ($placa["description"] ?? "") . " " . $nome;
        if (empty($placa["up"]) || preg_match(PLACAS_VIRTUAIS, $descricao)) {
            continue;
        }
        foreach ($placa["unicast"] ?? [] as $end) {
            $ip = $end["address"] ?? "";
            if (($end["family"] ?? 0) === AF_INET_IPV4 && ipDeRedeLocal($ip)) {
                // Wi-Fi primeiro (é como o celular costuma se conectar), depois cabo
                $candidatos[] = [preg_match('/wi-?fi|wireless|wlan/i', $descricao) ? 0 : 1, $ip];
            }
        }
    }
    sort($candidatos);
    return $candidatos[0][1] ?? null;
}

/** Reserva: IPs do nome da máquina, deixando por último a faixa 172.16–31 (comum no WSL). */
function ipPorNomeDaMaquina(): ?string
{
    $ips = array_values(array_filter(gethostbynamel(gethostname()) ?: [], fn($ip) => ipDeRedeLocal($ip)));
    usort($ips, fn($a, $b) => ehIpVirtual($a) <=> ehIpVirtual($b));
    return $ips[0] ?? null;
}

/** Aceita só IPv4 de rede local (não aceita localhost nem endereço automático 169.254). */
function ipDeRedeLocal(string $ip): bool
{
    return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        && !str_starts_with($ip, "127.")
        && !str_starts_with($ip, "169.254.");
}

/** Faixa 172.16–31.x: costuma ser a rede virtual do WSL/Hyper-V. */
function ehIpVirtual(string $ip): bool
{
    return (bool) preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip);
}

const AF_INET_IPV4 = 2;
