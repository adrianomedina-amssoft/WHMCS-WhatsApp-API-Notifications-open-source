<?php

namespace Lkn\HookNotification\Core\Shared\Infrastructure;

abstract class BaseApiClient
{
    protected function httpRequest(
        string $method,
        string $baseUrl,
        string $endpoint,
        array $headers = [],
        array $body = [],
        array $queryParams = []
    ): ApiResponse {
        $requestUrl = "$baseUrl/$endpoint";

        // Usar http_build_query para codificar corretamente os parâmetros e evitar injeção de URL
        if (count($queryParams) > 0) {
            $requestUrl .= '?' . http_build_query($queryParams);
        }

        $curlHandle = curl_init();

        $curlOptions = [
            CURLOPT_URL            => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            // Timeout para evitar DoS por APIs externas lentas
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Verificação de certificado SSL para evitar ataques MITM
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if (in_array($method, ['POST', 'PUT'], true)) {
            if ($body === []) {
                $curlOptions[CURLOPT_POSTFIELDS] = '{}';
            } else {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode(
                    $body,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }
        }

        curl_setopt_array($curlHandle, $curlOptions);

        $response = curl_exec($curlHandle);

        // Tratar falha de conexão antes de tentar decodificar a resposta
        if ($response === false) {
            $curlError = curl_error($curlHandle);
            curl_close($curlHandle);

            lkn_hn_log('HTTP request failed', ['error' => $curlError, 'url' => $requestUrl]);

            return new ApiResponse(0, []);
        }

        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        curl_close($curlHandle);

        return new ApiResponse(
            $httpCode,
            json_decode($response, true) ?? []
        );
    }
}
