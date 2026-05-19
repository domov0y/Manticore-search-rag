<?php
include("../lib/http.php");

$query='cmp 50hx';

function searxng_request($query)
{
$url='http://192.168.1.31/search?q=' . urlencode($query) . '&format=json';
echo $url."\n";

$headers = [
    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept' => 'application/json, text/plain, */*',
    'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
    'Accept-Encoding' => 'gzip, deflate',
    'Connection' => 'keep-alive',
    'Referer' => 'http://192.168.1.31/',
];

$searchResult = http_get($url, 10, $headers);
//print_r($searchResult);

if ($searchResult['code'] === 200) {
    $results = json_decode($searchResult['body'], true);
    // дальше парсим ответ SearXNG

    foreach($results['results'] as $row)
    {
     $result[]=['title' =>$row['title'], 'url' => $row['url'], 'content' =>$row['content'] ];
    }
}


return $result;
}

print_r(searxng_request($query));
?>