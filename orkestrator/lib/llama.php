<?php
//llama.php

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


function sendToLlama( $messages, $temperature = 0.7, $max_tokens = 512, $disable_thinking=0, $enable_tools=0)
{
        $payload = [
            'model' => LLAMACPP_MODEL_NAME,
            'messages' => $messages,
            'tool_choice' => 'auto',
            'stream' => false,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        if ($enable_tools) $payload['tools'] = getTools();
	if ($disable_thinking==1){ $payload['chat_template_kwargs'] = ['enable_thinking' => false]; }
        
        $ch = curl_init(LLAMACPP_HOST . '/v1/chat/completions');
//        print_r($payload);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 3000
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        if ($error) {
            throw new Exception("CURL Error: {$error}");
        }
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: {$httpCode}, Response: {$response}");
        }
        return json_decode($response, true);
}


function getEmbedding(string $text, string $model = 'all-MiniLM-L6-v2', string $url = 'http://192.168.1.22:8081') {
    $data = [
        'input' => $text,
        'model' => $model
    ];
    
    $ch = curl_init($url . '/v1/embeddings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error $httpCode: $response");
    }
    
    $decoded = json_decode($response, true);
    
    if (!isset($decoded['data'][0]['embedding'])) {
        throw new Exception("Invalid response format: " . $response);
    }
    
    return $decoded['data'][0]['embedding'];
}

