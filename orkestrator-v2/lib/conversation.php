<?php

function log_llm($data)
{
    file_put_contents(
        "logs/llm.log",
        json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        )."\n",
        FILE_APPEND
    );
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
    $tool_answer = "Инструмент не найден";


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


      foreach ($response['choices'][0]['message']['tool_calls'] as $asktool) {
       // print_r($asktool);
        $conversation[] = exec_tool($asktool);
      }
    } else {
      $conversation[] = $response['choices'][0]['message'];
      $result=$response['choices'][0]['message'];
      break;
    }
  }

  if (isset($parameters['log']) && $parameters['log']=='Y') log_llm($conversation) ;
  return $result;
}
