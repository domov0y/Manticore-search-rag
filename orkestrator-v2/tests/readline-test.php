<?php

system('chcp 65001 > nul');

$text = readline("> ");

echo "Ты ввел: ";
echo $text;
echo "\n";

print_r([
    'text'=>$text,
    'hex'=>bin2hex($text)
]);