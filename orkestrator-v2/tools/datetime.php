<?php

//$tools = [];

$tools['get_current_date']=[
'description'=>            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_current_date',
                    'description' => 'Возвращает текущую дату',
                    'parameters' => [ 'type' => 'object', 'properties' => [], 'required' => [] ]
                ]
            ],
'command'=>'dateTool'
];
function dateTool(): string {
        return "Текущая дата: " . date('d.m.Y');
}


$tools['get_current_time']=[
'description'=>
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_current_time',
                    'description' => 'Возвращает текущее время',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                        'required' => []
                    ]
                ]
            ],

'command'=>'timeTool'
];
function timeTool(): string {
        return "Текущее время: " . date('H:i:s');
    }


