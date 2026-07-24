<?php
include("../config.php");
include("../lib/llama.php");
$messages = [
    [
        "role" => "user",
        "content" => "Привет. Объясни кратко что такое PHP."
    ]
];


$params=    [
        'api_url' => 'https://api.vsellm.ru/v1/chat/completions',
        'api_key' => openrouter_key,
        'model' => LLAMACPP_MODEL_NAME,
        'temperature' => 0.7,
        'max_tokens' => 512
    ];
$result = sendToLlama($messages, array(), $params);

print_r($result);