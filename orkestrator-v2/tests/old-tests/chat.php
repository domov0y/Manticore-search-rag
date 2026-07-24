<?php
// chat_interface.php — только оболочка

if (!function_exists('readline')) {
    die("Установите расширение readline: sudo apt install php-readline\n");
}

echo "\n=== Консольный чат ===\n";
echo "Команды: /exit, /clear\n";
echo "Стрелки ← →, Backspace, Delete — работают\n\n";

$history = [];
$history_index = -1;

while (true) {
    // readline даёт полноценный ввод с редактированием
    $input = readline("Вы: ");
    
    if ($input === false) break;
    
    // Добавляем в историю для стрелок ↑ ↓
    if (trim($input) !== '') {
        $history[] = $input;
        $history_index = count($history);
    }
    readline_add_history($input);
    
    // Обработка команд
    if ($input === '/exit') {
        echo "До свидания!\n";
        break;
    }
    
    if ($input === '/clear') {
        echo "\033[2J\033[H"; // очистка экрана ANSI
        echo "=== Консольный чат ===\n\n";
        continue;
    }
    
    // Здесь будет вызов твоей llm_vconversation
    // Но это НЕ интерфейс, а логика — ты просил не писать это
    
    echo "Ассистент: [ответ]\n\n";
}