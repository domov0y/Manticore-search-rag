<?php

$tools['run_subagent'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'run_subagent',
            'description' => 'Запускает субагента с отдельным системным промптом, заданным набором инструментов и пользовательским запросом. Субагент работает в отдельном контексте и возвращает результат своей работы.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'system_prompt' => [
                        'type' => 'string',
                        'description' => 'Системный промпт субагента'
                    ],
                    'tools' => [
                        'type' => 'array',
                        'description' => 'Список инструментов, доступных субагенту',
                        'items' => [
                            'type' => 'string'
                        ]
                    ],
                    'request' => [
                        'type' => 'string',
                        'description' => 'Задача, которую должен выполнить субагент'
                    ],
                    'model' => [
                        'type' => 'string',
                        'description' => 'Модель, используемая субагентом. Если не указана, используется модель родительского агента'
                    ]
                ],
                'required' => [
                    'system_prompt',
                    'tools',
                    'request'
                ]
            ]
        ]
    ],
    'command' => 'runSubagent'
];

function runSubagent($params)
{
    $system_prompt = $params['system_prompt'];
    $tools = $params['tools'] ?? [];
    $request=$params['request'];
    //todo run agent
    return '';
}