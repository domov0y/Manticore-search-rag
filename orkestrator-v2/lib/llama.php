<?php
//llama.php




/*---------------------------------------------------------------------------------------------------------------------*/
function sendToLlama( $messages, $tools=array(), $params=array()   )
{
    $defaults = [
        'model' => LLAMACPP_MODEL_NAME,
        'temperature' => 0.7,
        'max_tokens' => 512,
        'disable_thinking' => false,
        'api_url' => LLAMACPP_HOST . '/v1/chat/completions',
        'api_key' => null,
        'timeout' => 3000,
    ];
    
    $config = array_merge($defaults, $params);


    $query_headers=[
                'Content-Type: application/json',
                'Accept: application/json'
            ] ;

    if ($config['api_key']) {
        $query_headers[] = 'Authorization: Bearer ' . $config['api_key'];
    }

        $payload = [
            'model' => $config['model'],
            'messages' => $messages,
            'stream' => false,
            'max_tokens' => $config['max_tokens'],
            'temperature' => $config['temperature']
        ];
        if (count($tools))
        {
           $payload['tools'] = $tools;
           $payload['tool_choice'] = 'auto';
        }
	if ($config['disable_thinking']){ $payload['chat_template_kwargs'] = ['enable_thinking' => false]; }


        
        $ch = curl_init($config['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $query_headers,
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false

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


/*---------------------------------------------------------------------------------------------------------------------*/

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

