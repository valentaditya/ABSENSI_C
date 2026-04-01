<?php
// ajax/api_maps.php
header('Content-Type: application/json');

// ==========================================
// GANTI DENGAN ID MAP / PLACE ID ROBLOX ANDA
// ==========================================
$placeId = 920587237; // Contoh Place ID (Adopt Me / game publik), ganti dengan ID Map Anda

// 1. Dapatkan Universe ID dari Place ID (API Roblox Butuh Universe ID)
$urlUniverse = "https://apis.roblox.com/universes/v1/places/{$placeId}/universe";
$ch = curl_init($urlUniverse);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Roblox kadangkala meminta user-agent asli browser
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$univResRaw = curl_exec($ch);
$univRes = json_decode($univResRaw, true);
curl_close($ch);

if (!isset($univRes['universeId'])) {
    echo json_encode([
        'error' => 'Gagal mendapatkan Universe ID. Pastikan Place ID valid.', 
        'raw_response' => $univResRaw
    ]);
    exit;
}
$universeId = $univRes['universeId'];

// 2. Ambil data Server dan Pemain dari Public Server API Roblox
$urlServers = "https://games.roblox.com/v1/games/{$universeId}/servers/Public?sortOrder=Asc&limit=10";
$ch2 = curl_init($urlServers);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$serverResRaw = curl_exec($ch2);
$serverRes = json_decode($serverResRaw, true);
curl_close($ch2);

// Outputkan hasilnya persis seperti dari Roblox agar Anda bisa melihat datanya langsung!
echo json_encode([
    'pesan_developer' => 'Ini adalah data asli dari API Map Roblox. Silakan scroll ke bagian "playerTokens". Roblox telah menghapus UserID & Username dari API publik.',
    'request_info' => [
        'placeId' => $placeId,
        'universeId' => $universeId,
    ],
    'roblox_api_response' => $serverRes
]);
?>
