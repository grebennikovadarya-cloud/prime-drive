<?php
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/descriptions.json';

if (!file_exists($file)) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = file_get_contents($file);
echo $data;