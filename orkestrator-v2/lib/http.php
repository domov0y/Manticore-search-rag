<?php

/**
 * Универсальный GET-запрос с обработкой ошибок и таймаутов.
 * @param string $url
 * @param int $timeout Секунды
 * @param array $headers Ассоциативный массив заголовков
 * @return array ['code'=>httpCode, 'body'=>string, 'error'=>string|null]
 */
function http_get(string $url, int $timeout = 10, array $headers = []): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'HomelabOrchestrator/1.0',
    ]);
    
    if (!empty($headers)) {
        $flatHeaders = [];
        foreach ($headers as $key => $value) {
            $flatHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $flatHeaders);
    }
    
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch) ?: null;
    curl_close($ch);
    
    return [
        'code' => $code,
        'body' => $body,
        'error' => $error,
    ];
}