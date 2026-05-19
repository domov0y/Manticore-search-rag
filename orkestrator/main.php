<?php

define('db_autoconnect',1);
include("config.php");
include("lib/llama.php");
include("lib/conversation.php");
include("lib/db.php");

include("tools/datetime.php");
//db_con();

$conversation=[];

/*$system_prompt= "Ты полезный ассистент с доступом к инструментам. " .
              "Можешь вызывать несколько инструментов параллельно, если это нужно. " .
              "Если результат одного инструмента требует другого инструмента - вызывай последовательно. " .
              "Всегда отвечай на русском языке.";
*/
//$user_query= "привет. подскажи текущее время";

$system_prompt = "Ты RAG агент. Твоя задача - собрать максимально полную информацию для ответа на вопрос пользователя.

Правила использования инструментов:
- search_facts: используй для поиска информации в базе знаний
- add_fact: используй ТОЛЬКО в случаях:
  a) Пользователь явно сообщает новую информацию
  b) Ты вывел новое знание на основе логического рассуждения
  c) В ответе search_facts ничего не найдено, но ты знаешь факт из своего обучения
- НЕ используй add_fact для сохранения данных, которые только что вернул search_facts

Верни структурированный отчет с найденной информацией и источниками.";

//$user_query= "привет. предложи как arduino uno  может притворяться hid устройством";
$user_query= "расскажи что знаешь про cmp 50hx";
//$user_query= "привет. предложи как arduino uno  может притворяться hid устройством";// llm_conversation($system_promt, $source_conversation, $user_query, $temperature=0.75, $max_tokens=4096, $disable_thinking=0)

$result= llm_conversation($system_prompt, $conversation, $user_query, 0.1, 4096, 1);
print_r($result);


