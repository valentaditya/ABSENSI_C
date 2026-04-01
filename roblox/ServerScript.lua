--[[
============= ROBLOX ABSENSI SYSTEM SCRIPT =============
Lokasi di Roblox Studio: ServerScriptService
]]

local HttpService = game:GetService("HttpService")
local Players = game:GetService("Players")

-- ==================== KONFIGURASI ====================
-- Ganti dengan domain atau IP website absensi Anda nantinya
-- Perhatikan jika localXAMPP, gunakan ngrok (misal: https://1234.ngrok-free.app) untuk menghubungkan Roblox ke localhost PC Anda
local BASE_URL = "https://56be-36-73-34-203.ngrok-free.app/api" 

local API_URL_JOIN = BASE_URL .. "/join.php"
local API_URL_LEAVE = BASE_URL .. "/leave.php"
local API_KEY = "ROBLOX_SECRET_KEY_2024" -- HARUS SAMA DENGAN config.php Backend

-- ================== FUNGSI HTTP POST ==================
local function sendRequest(url, data)
    local jsonData = HttpService:JSONEncode(data)
    
    local success, response = pcall(function()
        return HttpService:RequestAsync({
            Url = url,
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                -- Menggunakan Header App-Api-Key sebagai Validasi
                ["App-Api-Key"] = API_KEY,
                ["ngrok-skip-browser-warning"] = "true" 
            },
            Body = jsonData
        })
    end)
    
    if success and response.Success then
        print("[Absensi] Data berhasil direkam di server: ", url)
    else
        warn("[Absensi] Gagal mengirim data ke: ", url)
        if response then warn("Status Code: ", response.StatusCode, response.Body) else warn("Network Error/Timeout") end
    end
end

-- ================== EVENT LISTENER ==================

local function onPlayerAdded(player)
    print(player.Name .. " bergabung. Mencatat Join Time...")
    
    local data = {
        userId = player.UserId,
        username = player.Name
    }
    
    -- Request HTTP dapat dijeda sesaat/dibuat async non-blocking spawn jika diinginkan
    task.spawn(function()
        sendRequest(API_URL_JOIN, data)
    end)
end

-- Ketika Pemain Masuk Server
Players.PlayerAdded:Connect(onPlayerAdded)

-- Tangkap pemain yang pergerakannya terlalu cepat (seperti ketika test mode di Studio)
for _, player in ipairs(Players:GetPlayers()) do
    task.spawn(function()
        onPlayerAdded(player)
    end)
end

-- Ketika Pemain Keluar Server
Players.PlayerRemoving:Connect(function(player)
    print(player.Name .. " keluar. Mencatat Leave Time...")
    
    local data = {
        userId = player.UserId
    }
    
    -- Request Leave dikirim
    task.spawn(function()
        sendRequest(API_URL_LEAVE, data)
    end)
end)

print("[Absensi] Script Initialize. Menunggu player...")
