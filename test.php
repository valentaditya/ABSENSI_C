<?php
$ch = curl_init('http://localhost:8000/api/join.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['userId' => 12345, 'username' => 'TestUser']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'App-Api-Key: ROBLOX_SECRET_KEY_2024'
]);
$res = curl_exec($ch);
echo "Result: $res\n";
