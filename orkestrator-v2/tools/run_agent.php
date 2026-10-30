<?php

/**
 * Инструмент run_subagent — запуск субагента в отдельном контексте.
 *
 * Субагент — это отдельный вызов llm_conversation() со своим системным
 * промптом, своим списком инструментов и своим запросом. С родительской
 * сессией он делит только глобальный реестр инструментов (функции PHP
 * уже загружены через include tools/*.php) и доступ к Manticore/HTTP-сервисам.
 *
 * История родителя субагенту НЕ передаётся — он стартует с чистого листа.
 * Это намеренно: так проще рассуждать про изоляцию и контроль токенов.
 *
 * Защита от рекурсии: глобальный счётчик SUBAGENT_DEPTH. Если субагент
 * сам попытается вызвать run_subagent на глубине >= MAX_SUBAGENT_DEPTH —
 * получит ошибку вместо нового вложенного цикла.
 */

// Максимальная глубина вложенности субагентов.
// 1 = родитель может запустить субагента, но тот уже не может запустить
// своего субагента. 2 = разрешён один уровень вложенности.
if (!defined('MAX_SUBAGENT_DEPTH')) {
    define('MAX_SUBAGENT_DEPTH', 1);
}

$tools['run_subagent'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'run_subagent',
            'description' => 'Запускает субагента с отдельным системным промптом, заданным набором инструментов и пользовательским запросом. Субагент работает в отдельном контексте, без доступа к истории текущего диалога, и возвращает результат своей работы текстом. Используй этот инструмент для сложных многошаговых задач, которые выгодно изолировать: поиск и сбор информации, анализ документа, генерация черновика.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'system_prompt' => [
                        'type' => 'string',
                        'description' => 'Системный промпт субагента — роль и правила, по которым он должен действовать. Например: "Ты исследователь. Используй web_search для поиска источников и fetch_page для чтения найденных страниц. Верни сжатую выжимку."'
                    ],
                    'tools' => [
                        'type' => 'array',
                        'description' => 'Список имён инструментов, доступных субагенту. Имена должны совпадать с ключами в реестре инструментов: web_search, fetch_page, search_facts, add_fact, read_file, write_file, list_directory, get_current_date, get_current_time. Пустой массив = субагент без инструментов (только генерация текста).',
                        'items' => [
                            'type' => 'string'
                        ]
                    ],
                    'request' => [
                        'type' => 'string',
                        'description' => 'Задача, которую должен выполнить субагент. Формулируй её как самостоятельный пользовательский запрос — субагент не видит текущий диалог.'
                    ]
                ],
                'required' => ['system_prompt', 'tools', 'request']
            ]
        ]
    ],
    'command' => 'runSubagent'
];


function runSubagent($params)
{
    // --- Разбор параметров ---
    $system_prompt = isset($params['system_prompt']) ? (string)$params['system_prompt'] : '';
    $request       = isset($params['request'])       ? (string)$params['request']       : '';
    $wantedTools   = isset($params['tools']) && is_array($params['tools']) ? $params['tools'] : [];

    if ($request === '') {
        return "Ошибка: не задана задача субагента (параметр request)";
    }

    // --- Защита от рекурсии ---
    // Глобальный счётчик глубины. Поднимаем на входе, опускаем на выходе —
    // обязательно в finally, чтобы исключение в llm_agent не оставило
    // счётчик «зависшим» и не заблокировало все будущие вызовы.
    if (!isset($GLOBALS['SUBAGENT_DEPTH'])) {
        $GLOBALS['SUBAGENT_DEPTH'] = 0;
    }

    if ($GLOBALS['SUBAGENT_DEPTH'] >= MAX_SUBAGENT_DEPTH) {
        return "Ошибка: достигнута максимальная глубина вложенности субагентов ("
             . MAX_SUBAGENT_DEPTH . "). Текущий субагент не может запустить ещё одного.";
    }

    $GLOBALS['SUBAGENT_DEPTH']++;

    try {
        // --- Сбор подмножества инструментов по именам ---
        // Берём описания из глобального реестра $tools. Сами PHP-функции
        // уже загружены в глобальной области через include tools/*.php —
        // повторно их подключать не надо, call_user_func() в exec_tool()
        // их найдёт.
        global $tools;
        $subToolDescriptions = [];
        $unknown = [];
        foreach ($wantedTools as $name) {
            if (isset($tools[$name])) {
                $subToolDescriptions[] = $tools[$name]['description'];
            } else {
                $unknown[] = $name;
            }
        }

        // --- Параметры LLM для субагента ---
        // Температуру делаем пониже — субагент должен быть аккуратным
        // и предсказуемым. max_tokens побольше — чтобы хватило на
        // многошаговую работу с инструментами.
        $llmParams = [
            'temperature' => 0.3,
            'max_tokens'  => 4096,
        ];

        // --- Запуск ---
        // llm_agent() определена в lib/agent.php и просто собирает
        // conversation = [system?, user] и вызывает llm_conversation().
        // llm_conversation() крутит цикл tool_call'ов (до 10 итераций).
        $result = llm_agent($system_prompt, $request, $subToolDescriptions, $llmParams, []);

        // llm_conversation возвращает ['role'=>'assistant', 'content'=>...]
        // или объект с ошибкой. Извлекаем текст — родитель получит его
        // в tool-сообщении.
        $content = '';
        if (is_array($result) && isset($result['content'])) {
            $content = (string)$result['content'];
        } elseif (is_string($result)) {
            $content = $result;
        }

        // Добавляем метаинформацию для отладки — она попадёт в tool-сообщение
        // и LLM её увидит. Помогает родителю понять, что субагент реально
        // что-то сделал, а не вернул пустоту.
        $meta = "[subagent depth=" . $GLOBALS['SUBAGENT_DEPTH']
              . ", tools_granted=" . count($subToolDescriptions)
              . ", tools_unknown=" . count($unknown);
        if (!empty($unknown)) {
            $meta .= " (" . implode(',', $unknown) . ")";
        }
        $meta .= "]\n\n";

        return $meta . $content;

    } finally {
        // Гарантированно опускаем счётчик глубины — даже при исключении.
        $GLOBALS['SUBAGENT_DEPTH']--;
    }
}
