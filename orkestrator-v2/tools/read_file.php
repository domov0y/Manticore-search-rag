<?php

$tools['read_file'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'read_file',
            'description' => 'Читает текстовый файл по указанному пути',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Путь к файлу'
                    ],
                    'max_bytes' => [
                        'type' => 'integer',
                        'description' => 'Максимальное количество байт для чтения',
                        'default' => 100000
                    ]
                ],
                'required' => ['path']
            ]
        ]
    ],
    'command' => 'read_file'
];


function read_file($params)
{
    $path = $params['path'];

    if (!is_file($path)) {
        return "Ошибка: файл не найден: " . $path;
    }

    if (!is_readable($path)) {
        return "Ошибка: файл недоступен для чтения: " . $path;
    }

    $max_bytes = isset($params['max_bytes'])
        ? (int)$params['max_bytes']
        : 100000;

    if ($max_bytes <= 0) {
        $max_bytes = 100000;
    }

    $size = filesize($path);

    if ($size === false) {
        return "Ошибка: не удалось определить размер файла";
    }

    $content = file_get_contents($path, false, null, 0, $max_bytes);

    if ($content === false) {
        return "Ошибка: не удалось прочитать файл";
    }

    $result = '';

    if ($size > $max_bytes) {
        $result .= "[Файл обрезан. Размер: {$size} байт, прочитано: {$max_bytes}]\n\n";
    }

    $result .= $content;

    return $result;
}