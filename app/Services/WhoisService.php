<?php

namespace App\Services;

use Iodev\Whois\Factory;

class WhoisService
{
    public function lookup(string $domain): array
    {
        $result = ['domain' => $domain, 'success' => false];

        // WHOIS
        try {
            $whois  = Factory::get()->createWhois();
            $info   = $whois->loadDomainInfo($domain);
            if ($info) {
                $result['success']    = true;
                $result['registrar']  = $info->registrar;
                $result['owner']      = $info->owner;
                $result['created']    = $info->creationDate ? date('Y-m-d', $info->creationDate) : null;
                $result['updated']    = $info->updatedDate  ? date('Y-m-d', $info->updatedDate)  : null;
                $result['expires']    = $info->expirationDate ? date('Y-m-d', $info->expirationDate) : null;
                $result['nameservers'] = $info->nameServers ?? [];
                $result['states']     = $info->states ?? [];
            }
        } catch (\Exception $e) {
            $result['whois_error'] = $e->getMessage();
        }

        // DNS records
        try {
            $result['dns']['A']   = dns_get_record($domain, DNS_A)   ?: [];
            $result['dns']['MX']  = dns_get_record($domain, DNS_MX)  ?: [];
            $result['dns']['NS']  = dns_get_record($domain, DNS_NS)  ?: [];
            $result['dns']['TXT'] = dns_get_record($domain, DNS_TXT) ?: [];
        } catch (\Exception) {}

        // SSL certificate
        try {
            $ctx  = stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
            $sock = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if ($sock) {
                $params = stream_context_get_params($sock);
                $cert   = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                $result['ssl'] = [
                    'subject'   => $cert['subject']['CN'] ?? null,
                    'issuer'    => $cert['issuer']['O']   ?? null,
                    'valid_from' => isset($cert['validFrom_time_t']) ? date('Y-m-d', $cert['validFrom_time_t']) : null,
                    'valid_to'   => isset($cert['validTo_time_t'])   ? date('Y-m-d', $cert['validTo_time_t'])   : null,
                    'san'        => $cert['extensions']['subjectAltName'] ?? null,
                ];
                fclose($sock);
            }
        } catch (\Exception) {}

        return $result;
    }
}
