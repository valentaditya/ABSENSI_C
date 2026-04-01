
local HttpService = game:GetService("HttpService")
local Players = game:GetService("Players")

local BASE_URL = "https://playtimekoloseum.vercel.app/api" 

local API_URL_JOIN = BASE_URL .. "/join.php"
local API_URL_LEAVE = BASE_URL .. "/leave.php"
local API_KEY = "ROBLOX_SECRET_KEY_2024" 

local function sendRequest(url, data)
    local jsonData = HttpService:JSONEncode(data)
    
    local success, response = pcall(function()
        return HttpService:RequestAsync({
            Url = url,
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
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

local function onPlayerAdded(player)
    print(player.Name .. " bergabung. Mencatat Join Time...")
    
    local data = {
        userId = player.UserId,
        username = player.Name
    }
    
    task.spawn(function()
        sendRequest(API_URL_JOIN, data)
    end)
end
Players.PlayerAdded:Connect(onPlayerAdded)

for _, player in ipairs(Players:GetPlayers()) do
    task.spawn(function()
        onPlayerAdded(player)
    end)
end

Players.PlayerRemoving:Connect(function(player)
    print(player.Name .. " keluar. Mencatat Leave Time...")
    
    local data = {
        userId = player.UserId
    }
    
    task.spawn(function()
        sendRequest(API_URL_LEAVE, data)
    end)
end)

print("[Absensi] Menunggu player...")
