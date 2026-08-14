<?

$tools['write_file'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'write_file',
            'description' => 'Записывает текстовый файл или добавляет текст в существующий файл',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Путь к файлу'
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'Текст для записи'
                    ],
                    'append' => [
                        'type' => 'boolean',
                        'description' => 'Добавить в конец файла вместо перезаписи',
                        'default' => false
                    ]
                ],
                'required' => ['path', 'content']
            ]
        ]
    ],
    'command' => 'write_file'
];


function write_file($params)
{
    $path = $params['path'];
    $content = $params['content'];
    $append = !empty($params['append']);

    if ($path === '') {
        return "Ошибка: не указан путь к файлу";
    }

    $flags = $append ? FILE_APPEND : 0;

    $result = file_put_contents($path, $content, $flags);

    if ($result === false) {
        return "Ошибка: не удалось записать файл: " . $path;
    }

    return "Файл успешно записан: {$path} ({$result} байт)";
}
