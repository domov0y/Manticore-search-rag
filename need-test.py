import json
import requests

def ask_llm(messages, tools=None, tool_choice="auto", config=None):
    """
    Чистый клиент для API - только отправка запроса и получение ответа
    
    Args:
        messages: полный массив сообщений (уже с системным промптом и всеми записями)
        tools: список инструментов (опционально)
        tool_choice: "auto", "none" или конкретный инструмент
        config: словарь с настройками (model, api_key и т.д.)
    
    Returns:
        raw_response: полный ответ от API
    """
    
    # Настройки по умолчанию
    if config is None:
        config = {
            "api_key": "YOUR_OPENROUTER_API_KEY",
            "model": "openai/gpt-3.5-turbo",
            "url": "https://openrouter.ai/api/v1/chat/completions"
        }
    
    headers = {
        "Authorization": f"Bearer {config['api_key']}",
        "Content-Type": "application/json"
    }
    
    # Формируем данные для запроса
    data = {
        "model": config["model"],
        "messages": messages
    }
    
    # Добавляем инструменты, если они есть
    if tools:
        data["tools"] = tools
        data["tool_choice"] = tool_choice
    
    # Отправляем запрос
    response = requests.post(config["url"], headers=headers, json=data)
    result = response.json()
    
    # Возвращаем полный ответ ассистента
    return result["choices"][0]["message"]


def conversation(user_input, messages, tools=None, available_functions=None, config=None, system_prompt=None):
    """
    Управление диалогом с поддержкой инструментов и циклическими вызовами
    """
    
    # 1. Работа с системным промптом
    if system_prompt is not None:
        if messages and len(messages) > 0 and messages[0].get('role') == 'system':
            messages[0]['content'] = system_prompt
        else:
            messages.insert(0, {"role": "system", "content": system_prompt})
    
    # 2. Добавляем сообщение пользователя
    messages.append({"role": "user", "content": user_input})
    
    # 3. [ЦИКЛ] Повторяем, пока модель не перестанет вызывать инструменты
    max_iterations = 10  # Защита от бесконечного цикла
    iteration = 0
    
    while iteration < max_iterations:
        iteration += 1
        
        # Отправляем запрос к LLM
        assistant_message = ask_llm(
            messages=messages,
            tools=tools if available_functions else None,
            tool_choice="auto",
            config=config
        )
        
        # Добавляем ответ ассистента в историю
        messages.append(assistant_message)
        
        # Проверяем, есть ли tool_calls
        if available_functions and assistant_message.get("tool_calls"):
            # Обрабатываем ВСЕ tool_calls в этом сообщении
            for tool_call in assistant_message["tool_calls"]:
                function_name = tool_call["function"]["name"]
                function_args = json.loads(tool_call["function"]["arguments"])
                
                if function_name in available_functions:
                    # Вызываем функцию
                    function_result = available_functions[function_name](**function_args)
                    
                    # Добавляем результат в историю
                    messages.append({
                        "role": "tool",
                        "tool_call_id": tool_call["id"],
                        "content": json.dumps(function_result, ensure_ascii=False)
                    })
            
            # [КЛЮЧЕВОЙ МОМЕНТ] После добавления результатов инструментов
            # НЕ ВОЗВРАЩАЕМСЯ, а продолжаем цикл!
            # Модель может снова запросить инструменты
            continue
        
        # Если tool_calls нет - выходим из цикла
        break
    
    if iteration >= max_iterations:
        print(f"Warning: Достигнуто максимальное количество итераций ({max_iterations})")
    
    # Возвращаем последний ответ ассистента (без tool_calls)
    last_message = messages[-1]
    return last_message.get("content", ""), messages
