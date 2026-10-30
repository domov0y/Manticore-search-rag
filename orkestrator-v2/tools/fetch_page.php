<?php

/**
 * Инструмент fetch_page — получение содержимого страницы через trafilatura-service.
 *
 * Сервис trafilatura-service (см. /trafilatura-service/main-1.py) поднимает
 * Flask-эндпоинт /extract?url=<url>, который:
 *   - проверяет URL на SSRF (блокирует localhost, приватные IP, метаданные облака);
 *   - загружает страницу с таймаутом 10 сек и лимитом ~18 МБ;
 *   - автоопределяет кодировку через chardet;
 *   - прогоняет HTML через trafilatura.extract() — на выходе чистый текст
 *     без навигации, рекламы, скриптов и т.п.
 *
 * Возвращает JSON вида {"url": ..., "content": ...} либо {"error": ...}.
 *
 * Рабочий пример вызова см. в orkestrator/tests/trafilatura_test.php.
 *
 * Конфигурация хоста задаётся в config.php через TRAFILATURA_HOST.
 */

$tools['fetch_page'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'fetch_page',
            'description' => 'Получает чистый текст страницы по её URL через сервис trafilatura: вырезает навигацию, рекламу, скрипты и возвращает только основное содержимое. Используй этот инструмент после web_search, чтобы прочитать полную статью по найденной ссылке.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'url' => [
                        'type' => 'string',
                        'description' => 'Полный URL страницы (http:// или https://). URL должен указывать на публично доступный сайт — сервис отклонит localhost и приватные адреса.'
                    ],
                    'max_chars' => [
                        'type' => 'integer',
                        'description' => 'Максимальная длина возвращаемого текста в символах. Используется чтобы не переполнить контекст LLM.',
                        'default' => 50000
                    ]
                ],
                'required' => ['url']
            ]
        ]
    ],
    'command' => 'fetch_page'
];


function fetch_page($params)
{
    $url = trim($params['url'] ?? '');
    if ($url === '') {
        return "Ошибка: не задан URL страницы (параметр url)";
    }

    // Базовая sanity-проверка URL — чтобы не гонять запрос за заведомо мусор.
    if (!preg_match('#^https?://#i', $url)) {
        return "Ошибка: URL должен начинаться с http:// или https://. Получено: " . $url;
    }

    $max_chars = isset($params['max_chars']) ? (int)$params['max_chars'] : 50000;
    if ($max_chars <= 0) {
        $max_chars = 50000;
    }
    // Не даём превысить 500000 — иначе ответ может не влезть в tool-сообщение.
    if ($max_chars > 500000) {
        $max_chars = 500000;
    }

    $requestUrl = TRAFILATURA_HOST . '/extract?url=' . urlencode($url);

    $headers = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'application/json, text/plain, */*',
        'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
        'Connection'      => 'keep-alive',
        'Referer'         => TRAFILATURA_HOST . '/',
    ];

    // таймаут 20 сек — больше чем внутренний 10-секундный таймаут самого
    // trafilatura-сервиса, чтобы успеть получить его собственные ошибки.
    $resp = http_get($requestUrl, 20, $headers);

    if (!empty($resp['error'])) {
        return "Ошибка сети при обращении к trafilatura-сервису: " . $resp['error'];
    }

    if ($resp['code'] !== 200) {
        // Пытаемся достать поле error из JSON-ответа сервиса.
        $errBody = json_decode($resp['body'] ?? '', true);
        $errMsg = is_array($errBody) && !empty($errBody['error'])
            ? $errBody['error']
            : mb_substr((string)($resp['body'] ?? ''), 0, 500);

        // Несколько частых случаев — чтобы LLM понимал, что делать дальше.
        switch ($resp['code']) {
            case 400:
                return "Trafilatura отклонил запрос (HTTP 400): " . $errMsg
                     . "\nВозможные причины: URL не http(s), указывает на localhost/приватный IP или на метаданные облака.";
            case 404:
                return "Trafilatura не смог извлечь текст из страницы (HTTP 404): " . $errMsg
                     . "\nВозможно, страница пустая, требует JS или закрыта от ботов. Попробуй другую ссылку.";
            case 413:
                return "Страница слишком большая (HTTP 413): " . $errMsg;
            case 504:
                return "Истёк таймаут при загрузке страницы (HTTP 504): " . $errMsg;
            default:
                return "Ошибка trafilatura-сервиса: HTTP " . $resp['code'] . ". " . $errMsg;
        }
    }

    $decoded = json_decode($resp['body'] ?? '', true);
    if (!is_array($decoded) || !isset($decoded['content'])) {
        return "Ошибка разбора ответа trafilatura: ожидался JSON с полем content. Тело: "
             . mb_substr((string)($resp['body'] ?? ''), 0, 500);
    }

    $content = (string)$decoded['content'];
    $sourceUrl = $decoded['url'] ?? $url;

    if ($content === '') {
        return "Сервис trafilatura вернул пустой текст для URL: " . $sourceUrl;
    }

    $origLen = mb_strlen($content);
    if ($origLen > $max_chars) {
        $content = mb_substr($content, 0, $max_chars)
                 . "\n\n[… текст обрезан: всего {$origLen} символов, показано {$max_chars} …]";
    }

    $header = "URL: " . $sourceUrl . "\n";
    $header .= "Извлечённый текст (длина {$origLen} символов):\n";
    $header .= str_repeat("-", 60) . "\n\n";

    return $header . $content;
}
