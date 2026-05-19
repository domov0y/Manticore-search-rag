<?php
// agents.php

/**
 * RAG агент - собирает информацию через инструменты
 */
function agent_rag($source_conversation, $user_query) {
    $system_prompt = "Ты RAG агент. Твоя задача - собрать максимально полную информацию для ответа на вопрос пользователя.
    Используй доступные инструменты (поиск, БД, API) чтобы получить релевантные данные.
    Верни структурированный отчет с найденной информацией и источниками.";
    
    $result = llm_conversation($system_prompt, $source_conversation, $user_query, 0.1, 2000);
    return $result['content'];
}

/**
 * Генератор ответа - создает красивый ответ на основе данных RAG
 */
function agent_report_master($source_conversation, $user_query, $rag_data) {
    $system_prompt = "Ты эксперт по составлению ответов. На основе предоставленных данных создай понятный, полезный ответ пользователю.
    Используй информацию из RAG данных. Если данных недостаточно - скажи честно.
    Ответ должен быть структурированным, но без лишних деталей.";
    
    $enhanced_query = "Вопрос пользователя: {$user_query}\n\nДанные для ответа:\n{$rag_data}";
    
    $result = llm_conversation($system_prompt, $source_conversation, $enhanced_query, 0.7, 4000);
    return $result['content'];
}

/**
 * Критик - проверяет и исправляет ответ
 */
function agent_critic($source_conversation, $user_query, $rag_data, $draft_answer) {
    $system_prompt = "Ты критик. Проверь ответ на точность и полноту.
    Если есть ошибки или неточности - исправь их.
    Если всё хорошо - верни ответ без изменений.
    Верни только финальную версию ответа, без пояснений.";
    
    $critic_query = "Вопрос: {$user_query}\n\nИсходные данные:\n{$rag_data}\n\nЧерновик ответа:\n{$draft_answer}\n\nПроверь и исправь если нужно:";
    
    $result = llm_conversation($system_prompt, $source_conversation, $critic_query, 0.3, 4000);
    return $result['content'];
}

/**
 * Главная функция - запускает цепочку агентов
 */
function process_with_agents($user_query, $conversation_history = []) {
    echo "🔍 Шаг 1: RAG агент собирает данные...\n";
    $rag_data = agent_rag($conversation_history, $user_query);
    
    echo "✍️ Шаг 2: Генератор создает ответ...\n";
    $draft = agent_report_master($conversation_history, $user_query, $rag_data);
    
    echo "🔍 Шаг 3: Критик проверяет ответ...\n";
    $final = agent_critic($conversation_history, $user_query, $rag_data, $draft);
    
    return [
        'final_answer' => $final,
        'rag_data' => $rag_data,
        'draft' => $draft
    ];
}


function process_with_agents_flexible($user_query, $conversation_source = [], $agents = ['rag', 'master', 'critic']) 
{
    $conversation = [];
    $rag_data = '';
    $draft = '';
    
    foreach ($agents as $agent) {
        switch ($agent) {
            case 'rag':
                echo "📚 RAG агент...\n";
                $rag_data = agent_rag($conversation, $user_query);
                break;
            case 'master':
                echo "✍️ Генератор...\n";
                $draft = agent_report_master($conversation, $user_query, $rag_data);
                break;
            case 'critic':
                echo "🔍 Критик...\n";
                $final = agent_critic($conversation, $user_query, $rag_data, $draft);
                break;
        }
    }
    
    return $final ?? $draft ?? $rag_data;
}