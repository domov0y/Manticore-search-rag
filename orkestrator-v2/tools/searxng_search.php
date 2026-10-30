<?php

/**
 * Инструмент web_search — поиск через SearXNG.
 *
 * SearXNG — это приватный метапоисковик, который умеет агрегировать
 * результаты из Google, Bing, DuckDuckGo, Yandex и десятков других
 * движков. Эндпоинт /search?q=<запрос>&format=json возвращает JSON с
 * массивом results: [{title, url, content, engine, score, ...}, ...].
 *
 * Рабочий пример запроса см. в orkestrator/tests/searxng_test.php.
 *
 * Конфигурация хоста задаётся в config.php через SEARXNG_HOST.
 */

$tools['web_search'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'web_search',
            'description' => 'Ищет информацию в интернете через метапоисковик SearXNG. Возвращает список найденных документов с заголовком, ссылкой и кратким описанием. Используй этот инструмент, когда собственных знаний недостаточно или нужно проверить актуальную информацию.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Поисковый запрос. Сформулируй его кратко и точно — как обычный запрос к поисковой системе.'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Максимальное количество результатов (1..50)',
                        'default' => 10
                    ],
                    'categories' => [
                        'type' => 'string',
                        'description' => 'Категория поиска SearXNG: general (по умолчанию), news, images, videos, it, science, files, social media',
                        'default' => 'general'
                    ],
                    'language' => [
                        'type' => 'string',
                        'description' => 'Язык запроса и результатов в формате IETF (ru, en, de, ...). Если не задан — SearXNG использует настройки по умолчанию.',
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ],
    'command' => 'web_search'
];


function web_search($params)
{
    $query = trim($params['query'] ?? '');
    if ($query === '') {
        return "Ошибка: не задан поисковый запрос (параметр query)";
    }

    $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    if ($limit > 50) {
        $limit = 50;
    }

    $categories = $params['categories'] ?? 'general';
    $allowed_categories = ['general', 'news', 'images', 'videos', 'it', 'science', 'files', 'social media'];
    if (!in_array($categories, $allowed_categories, true)) {
        $categories = 'general';
    }

    $url = SEARXNG_HOST . '/search?q=' . urlencode($query)
         . '&format=json'
         . '&categories=' . urlencode($categories)
         . '&safesearch=1';

    if (!empty($params['language'])) {
        $url .= '&language=' . urlencode($params['language']);
    }

    $headers = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'          => 'application/json, text/plain, */*',
        'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
        'Connection'      => 'keep-alive',
        'Referer'         => SEARXNG_HOST . '/',
    ];

    $searchResult = http_get($url, 15, $headers);

    if (!empty($searchResult['error'])) {
        return "Ошибка сети при обращении к SearXNG: " . $searchResult['error'];
    }

    if ($searchResult['code'] !== 200) {
        return "Ошибка SearXNG: HTTP " . $searchResult['code']
             . ". Тело ответа: " . mb_substr((string)$searchResult['body'], 0, 500);
    }

    $decoded = json_decode($searchResult['body'], true);
    if (!is_array($decoded) || !isset($decoded['results'])) {
        return "Ошибка разбора ответа SearXNG: ожидался JSON с полем results.";
    }

    $rawResults = $decoded['results'];
    if (!is_array($rawResults) || empty($rawResults)) {
        return "По запросу «{$query}» ничего не найдено.";
    }

    // Оставляем только нужное количество результатов и обогащаем
    // краткой метаинформацией (источник/движок), если SearXNG её прислал.
    $out = [];
    $i = 1;
    foreach ($rawResults as $row) {
        if ($i > $limit) {
            break;
        }
        $item = [
            'n'      => $i++,
            'title'  => $row['title']   ?? '',
            'url'    => $row['url']     ?? '',
            'snippet'=> $row['content'] ?? '',
        ];
        if (!empty($row['engine'])) {
            $item['engine'] = $row['engine'];
        }
        if (!empty($row['publishedDate'])) {
            $item['published'] = $row['publishedDate'];
        }
        $out[] = $item;
    }

    // Возвращаем строку в человекочитаемом виде — это лучше всего
    // помещается в tool-сообщение и легко читается LLM.
    $resultText = "Найдено результатов: " . count($out) . " (из "
                . (isset($decoded['number_of_results']) ? $decoded['number_of_results'] : '?')
                . " по версии SearXNG)\n";
    $resultText .= "Запрос: «{$query}», категория: {$categories}\n\n";

    foreach ($out as $item) {
        $resultText .= "[" . $item['n'] . "] " . $item['title'] . "\n";
        $resultText .= "    URL: " . $item['url'] . "\n";
        if (!empty($item['engine'])) {
            $resultText .= "    Источник: " . $item['engine'];
            if (!empty($item['published'])) {
                $resultText .= " | Опубликовано: " . $item['published'];
            }
            $resultText .= "\n";
        }
        if (!empty($item['snippet'])) {
            // обрезаем длинные сниппеты — иначе один результат может
            // съесть весь контекст модели.
            $snippet = mb_substr($item['snippet'], 0, 400);
            if (mb_strlen($item['snippet']) > 400) {
                $snippet .= "…";
            }
            $resultText .= "    " . str_replace("\n", " ", $snippet) . "\n";
        }
        $resultText .= "\n";
    }

    return trim($resultText);
}
