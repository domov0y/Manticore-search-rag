<?php
include("../config.php");
include("../lib/llama.php");
include("../lib/conversation.php");

include("../tools/datetime.php");
$messages = [
    [
        "role" => "user",
        "content" => "Привет. назови текущие дату и время. это нужно чтобы проверить возможность вызова инструментов"
    ]
];


$my_tools=getTools();

$params=    [
        'api_url' => 'https://api.vsellm.ru/v1/chat/completions',
        'api_key' => openrouter_key,
        'model' => LLAMACPP_MODEL_NAME,
        'temperature' => 0.7,
        'max_tokens' => 512
    ];
//$result = sendToLlama($messages, array(), $params);
$result =  llm_conversation( $messages, $my_tools, $params );
print_r($result);