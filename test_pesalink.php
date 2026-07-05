<?php
$ch = curl_init('https://pesalink.online/api/create-transaction');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer 7f021e576ac51f399c4ca27867f78e1c',
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode(['number' => '255700000000', 'amount' => 1000, 'name' => 'Test']),
    CURLOPT_TIMEOUT => 15
]);
$response = curl_exec($ch);
echo "RESPONSE:\n";
var_dump($response);
echo "\nHTTP: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
if(curl_error($ch)) echo "CURL Error: " . curl_error($ch) . "\n";
