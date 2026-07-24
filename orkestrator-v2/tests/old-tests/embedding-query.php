<?php
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

// Использование
try {
    $vector = getEmbedding("Привет, как дела?");
    echo "Получен вектор размерности: " . count($vector) . "\n";
    echo "Первые 10 значений: " . implode(', ', array_slice($vector, 0, 10)) . "...\n";
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>