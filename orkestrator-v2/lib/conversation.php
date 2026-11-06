<?php

function log_llm($data)
{
    $file = SESSION_DIR . '/tools.jsonl';
    $json = json_encode(  $data,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES    );

    if ($json !== false) {
        file_put_contents(   $file,    $json . PHP_EOL,  FILE_APPEND | LOCK_EX );
    }
}

$tools = [];

function getTools()
{
  global $tools;
  $tmp=[];
  foreach($tools as $tool)
  {
   $tmp[]=$tool['description'];
  }
  return $tmp;
}

/*---------------------------------------------------------------------------------------------------------------------*/
function exec_tool($asktool)
{
//    print_r($asktool);
    global $tools;
    $tool_name = $asktool['function']['name'];
    $tool_args = json_decode($asktool['function']['arguments'],true);

    if (isset($tools[$tool_name]))
    {
      $tool_command = $tools[$tool_name]['command'];
      $tool_answer = call_user_func($tool_command, $tool_args);
    }
    else
    $tool_answer = "инструмент не найден";


   $tmp=[
            'tool_call_id' => $asktool['id'],
            'role' => 'tool',
            'name' => $tool_name,
            'content' => $tool_answer
        ];
return $tmp;
}

/*---------------------------------------------------------------------------------------------------------------------*/

function  llm_conversation( $conversation, $tools=array(), $parameters=array() )
{
  $result = [ 'role' => 'assistant', 'content' => '' ];

   $disable_thinking=0;



   for ($i = 0; $i < 10; $i++) {
    $response = sendToLlama( $conversation, $tools, $parameters );

    if (isset($response['choices'][0]['message']['tool_calls'])) {

      $conversation[] = [
        "role" => "assistant",
        "content" => null,
        "tool_calls" => $response['choices'][0]['message']['tool_calls']
      ];

      log_llm( $conversation[ array_key_last($conversation) ] );
      foreach ($response['choices'][0]['message']['tool_calls'] as $asktool) {
        $conversation[] = exec_tool($asktool);
        log_llm( $conversation[ array_key_last($conversation) ] );
      }
    } else {
      $conversation[] = $response['choices'][0]['message'];
      $result=$response['choices'][0]['message'];
      break;
    }
  }
  return $result;
}
