<?php 
// lib/agent.php
function llm_agent($system_prompt, $user_prompt, $tools = [], $params=[], $history = [] )
{  
    
    $conversation = $history ;

    if ($system_prompt != '') {
        array_unshift($conversation, [
            'role'    => 'system',
            'content' => $system_prompt
        ]);
    }

    $conversation[] = [
        'role'    => 'user',
        'content' => $user_prompt
    ];

    return llm_conversation(
        $conversation,
        $tools,
        $params
    );
}


?>