<?php
include("../lib/http.php");



function trafilatura_request($_request_url)
{
$url='http://127.0.0.1:5000/extract?url=' . urlencode($_request_url);
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

if ($searchResult['code'] === 200) {
    return $searchResult;
  }
 return false;
}

print_r(trafilatura_request('https://habr.com/ru/news/1035030/'));
?>
