<?php

use GuzzleHttp\Client;
use League\CommonMark\CommonMarkConverter;

require_once 'vendor/autoload.php';

$url = $argv[1];
$application = $argv[2];
$token = $argv[3];
$index = 'docs';
$client = new Client([
    // Base URI is used with relative requests
    'base_uri' => $url,
    // You can set any number of default request options.
    'timeout'  => 20.0,
    'http_errors' => false,
    'headers' => [
        'Content-Type' => 'application/json',
        'X-Sigmie-API-Key' => $token,
        'X-Sigmie-Application' => $application,
    ]
]);

// $res = $client->post("/v1/index/{$index}");
$res = $client->post("/v1/index/{$index}/clear");


//dd($res->getStatusCode(), json_decode($res->getBody()->getContents(), true));

$parsedown = new CommonMarkConverter();

$filesSubdir = glob('src/pages/docs/**/*.{md}', GLOB_BRACE);

$filesDir = glob('src/pages/docs/*.{md}', GLOB_BRACE);

$files = [...$filesSubdir, ...$filesDir];

$json = [];
foreach ($files as $file) {
    $markdown = file_get_contents($file);

    $content = $parsedown->convert($markdown)->getContent();

    if (empty($content)) {
        continue;
    }

    $path = str_replace('src/pages/', '', $file);
    $path = str_replace('.md', '', $path);
    // $titles = getTextBetweenTags($content, 'h1');
    $subtitles = getTextBetweenTags($content, 'h2');
    $headings = getTextBetweenTags($content, 'h3');
    $body = getTextBetweenTags($content, 'p');
    $lis = getTextBetweenTags($content, 'li');

    preg_match('/title:( )?(.*)\n/', $subtitles[0], $matches);
    $title = $matches[2] ?? $matches[1];
    preg_match('/description:( )?(.*)/', $subtitles[0], $matches);
    $description = $matches[2] ?? $matches[1];
    array_splice($subtitles, 0, 1);

    //Remove callouts
    $body = preg_replace('/{% ?(\/)?callout[a-zA-Z=" ]*%}/', '', $body);

    $json[] = [
        'action' => 'create',
        'body' => [
            'title' => $title,
            'description' => $description,
            'subtitles' => $subtitles,
            'h3' => $headings,
            'content' => $body,
            'li' => $lis,
            'path' => $path
        ],
    ];
}

$res = $client->put("v1/index/{$index}/batch", ['json' => $json,]);

// dd($res->getStatusCode(), json_decode($res->getBody()->getContents(), true));

// $json = json_decode($res->getBody()->getContents(), true);

// dd($json);
function getTextBetweenTags($string, $tagname)
{
    $d = new DOMDocument();
    $d->loadHTML($string);
    $return = array();
    foreach ($d->getElementsByTagName($tagname) as $item) {
        $return[] = $item->textContent;
    }
    return $return;
}
