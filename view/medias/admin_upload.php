<?php

header('Content-Type: application/json');
header("Content-Security-Policy: default-src 'self'");

$file = Router::webroot($file);

$file = str_replace('\\', '/', $file);

echo json_encode($file);
