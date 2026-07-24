<?php
system('chcp 65001 > nul');


include("../config.php");
include("../lib/llama.php");
include("../lib/conversation.php");

include("../tools/datetime.php");


$my_tools=getTools();

$params=    [
        'api_url' => 'https://api.vsellm.ru/v1/chat/completions',
        'api_key' => openrouter_key,
        'model' => LLAMACPP_MODEL_NAME,
        'temperature' => 0.7,
        'max_tokens' => 512
    ];


$history=[];
//todo надо переписать системный промт на что то разрешающее пользоваться инструментами
//$system_promt="";
//$history[] = ['role' => 'system', 'content' => $system_promt];



while (true)
{
  $user_message= readline("\nUser: ");
  if (in_array($user_message,['exit','quit'])) break;
  $history[] = ['role' => 'user', 'content' => $user_message];
  $answer= llm_conversation($history, $my_tools, $params);
  $history[]=  [ 'content' => $answer['content'], 'role' =>$answer['role']];
  echo  $answer['role'].": ".$answer['content'];
}




