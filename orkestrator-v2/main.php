<?php
if (PHP_OS_FAMILY === 'Windows') 
{
    $codePage = trim(shell_exec('chcp'));

    if (!str_contains($codePage, '65001')) {
        system('chcp 65001 > nul');
    }
}

include("./config.php");

include("./lib/http.php");
include("./lib/llama.php");
include("./lib/conversation.php");
include("./lib/agent.php");

include("./tools/datetime.php");
include("./tools/searxng_search.php");
include("./tools/fetch_page.php");

include("./tools/list_directory.php");
include("./tools/read_file.php");
include("./tools/run_agent.php");
//include("../tools/write_file.php");


$my_tools=getTools();

$params=    [
        'api_url' => 'https://api.vsellm.ru/v1/chat/completions',
        'api_key' => openrouter_key,
        'model' => LLAMACPP_MODEL_NAME,
        'temperature' => 0.7,
        'max_tokens' => 20000
    ];
$history=[];

while (true)
{
  $user_message= readline("\nUser: ");
  if (in_array($user_message,['exit', 'quit'])) break;

   if ($user_message === false) {
        echo "\n[EOF] выход\n";
        break;
  }

  if (trim($user_message) === '') continue;

  $message=['role' => 'user', 'content' => $user_message];
  $history[] = $message;
  log_llm('journal',$message);
  $answer= llm_conversation($history, $my_tools, $params);
  $message=[ 'content' => $answer['content'], 'role' =>$answer['role']];
  $history[]= $message;
  log_llm('journal',$message);
  echo  $answer['role'].": ".$answer['content'];
}


