<?php
// tools/run_agent.php

$tools['run_subagent'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'run_subagent',
            'description' => 'Запускает субагента с отдельным системным промптом, '
                . 'набором инструментов и запросом. Субагент не видит историю диалога.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'system_prompt' => ['type' => 'string', 'description' => 'Роль и правила субагента.'],
                    'tools' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Имена инструментов из реестра. Пустой массив = без инструментов.'
                    ],
                    'request' => ['type' => 'string', 'description' => 'Задача субагента.'],
                    'temperature' => [
                        'type' => 'number',
                        'description' => 'Температура субагента (0..2). По умолчанию 0.3. '. 'Повышай только для творческих задач.',
                     ],
                     'max_tokens' => [
                        'type' => 'integer',
                        'description' => 'Максимум токенов в ответе субагента. По умолчанию 4096.',
                     ],
                ],
                'required' => ['system_prompt', 'tools', 'request']
            ]
        ]
    ],
    'command' => 'runSubagent'
];

function _buildSubagentOverride(array $input, array $spec): array
{
    $override = [];
    foreach ($spec as $name => $rule) {
        if (!isset($input[$name])) {
            continue;
        }
        $raw = $input[$name];

        // Приведение типа
        if ($rule['type'] === 'int') {
            if (!is_numeric($raw)) continue;
            $val = (int)$raw;
        } else {
            if (!is_numeric($raw)) continue;
            $val = (float)$raw;
        }

        // Проверка диапазона
        if ($val < $rule['min'] || $val > $rule['max']) {
            continue;
        }

        $override[$name] = $val;
    }

    if (defined('SUBAGENT_USE_DEFAULTS_FOR_MISSING'))
    {
       foreach ($spec as $name => $rule) {
          if (!isset($override[$name]))
          $override[$name]=$rule['default'];
       }
    }
    return $override;
}


function runSubagent($tools_params)
{
    global $params, $tools;   // $params — из chat-test.php (верхний уровень)

    $system_prompt = isset($tools_params['system_prompt']) ? (string)$tools_params['system_prompt'] : '';
    $request       = isset($tools_params['request'])       ? (string)$tools_params['request']       : '';
    $wantedNames   = isset($tools_params['tools']) && is_array($tools_params['tools'])  ? $tools_params['tools'] : [];

    if ($request === '') {
        return 'Ошибка: не задана задача субагента (request).';
    }

    // Имена → описания; заодно запрещаем рекурсию
    $descriptions = [];
    foreach ($wantedNames as $name) {
        if ($name === 'run_subagent') continue;
        if (isset($tools[$name]['description'])) {
            $descriptions[] = $tools[$name]['description'];
        }
    }


    $defaults=[
       'temperature' => ['default' => 0.3,  'min' => 0.0,  'max' => 2.0,  'type' => 'float'],
       'max_tokens'  => ['default' => 4096, 'min' => 64,   'max' => 32000,'type' => 'int'],
//      'top_p'       => ['default' => 1.0,  'min' => 0.0,  'max' => 1.0,  'type' => 'float'],
    ];

    $override=_buildSubagentOverride( $tools_params, $defaults);
    $agent_params = array_merge($params, $override);

    try {
      $answer= llm_agent($system_prompt, $request, $descriptions, $agent_params, []);
      if (!is_array($answer) || !isset($answer['content'])) return "выполнение субагента закончилось ошибкой";
      return $answer['content'];
    }
    catch (Exception $e) {
        return '[subagent error] ' . $e->getMessage();
    }
}
