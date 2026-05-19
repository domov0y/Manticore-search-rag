<?php


function  llm_conversation($system_promt, $source_conversation, $user_query, $temperature=0.75, $max_tokens=4096, $disable_thinking=0)
{
  $conversation = $source_conversation;
  $conversation[0] = ['role' => 'system', 'content' => $system_promt];
  $conversation[] = ['role' => 'user', 'content' => $user_query];
  $enable_tools = true;

  for ($i = 0; $i < 10; $i++) {
    $response = sendToLlama($conversation, $temperature, $max_tokens, $disable_thinking, $enable_tools);
    if (isset($response['choices'][0]['message']['tool_calls'])) {

      $conversation[] = [
        "role" => "assistant",
        "content" => null,
        "tool_calls" => $response['choices'][0]['message']['tool_calls']
      ];


      foreach ($response['choices'][0]['message']['tool_calls'] as $asktool) {
        print_r($asktool);
        $conversation[] = exec_tool($asktool);
      }
    } else {
      $conversation[] = $response['choices'][0]['message'];
      $result=$response['choices'][0]['message'];
      break;
    }
  }
print_r($conversation);
  return $result;
}
