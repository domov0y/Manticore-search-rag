<?
$history=[];
//todo надо переписать системный промт на что то разрешающее пользоваться инструментами
$system_promt="";
$history[] = ['role' => 'system', 'content' => $system_promt];



while (true)
{
  $user_message= readline("> ");
  if (in_array($user_message,['exit','quit'])) break;
  $history[] = ['role' => 'user', 'content' => $user_message];
  $answer= llm_conversation($history, $tools, $parameters);
  $history[]=   $answer;
  //todo тут  показываем на экран сообщение
}

?>