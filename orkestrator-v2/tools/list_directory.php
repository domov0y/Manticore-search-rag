<?php

$tools['list_directory'] = [
    'description' => [
        'type' => 'function',
        'function' => [
            'name' => 'list_directory',
            'description' => 'Возвращает содержимое указанного каталога',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'Путь к каталогу'
                    ]
                ],
                'required' => ['path']
            ]
        ]
    ],

    'command' => function($args)
    {
        $path = $args['path'];

        $result = [];

        foreach (scandir($path) as $name) {

            if ($name === '.' || $name === '..') {
                continue;
            }

            $fullpath = $path . DIRECTORY_SEPARATOR . $name;

            if (is_dir($fullpath)) {
                $result[] = [
                    'name' => $name,
                    'type' => 'directory'
                ];
            }
            elseif (is_link($fullpath)) {
                $result[] = [
                    'name' => $name,
                    'type' => 'symlink',
                    'target' => readlink($fullpath)
                ];
            }
            else {
                $result[] = [
                    'name' => $name,
                    'type' => 'file',
                    'size' => filesize($fullpath),
                    'mtime' => date('Y-m-d H:i:s', filemtime($fullpath))
                ];
            }
        }

        return $result;
    }
];