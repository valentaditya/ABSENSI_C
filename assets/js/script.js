// Default states
let filterSelect = document.getElementById('filterSelect');
let searchInput = document.getElementById('searchInput');
let btnReset = document.getElementById('btn-reset');

// Elements
const tableBody = document.getElementById('tableBody');
const sOnline = document.getElementById('s-online');
const sToday = document.getElementById('s-today');
const sBusiest = document.getElementById('s-busiest');
const leaderboardDiv = document.getElementById('leaderboard');

// Notifikasi setup
let lastKnownTotal = 0;
let lastKnownOnline = []; // Array of UserIds online in current tracking

// Request permission for Web Notifications
if ("Notification" in window) {
    Notification.requestPermission();
}

function showNotification(title, message) {
    if ("Notification" in window && Notification.permission === "granted") {
        new Notification(title, {
            body: message,
            icon: "https://roblox.com/favicon.ico" // sample icon
        });
    }
}

function loadData() {
    const filter = filterSelect ? filterSelect.value : 'all';
    const search = searchInput ? encodeURIComponent(searchInput.value) : '';

    fetch(`ajax/get_data.php?filter=${filter}&search=${search}`)
        .then(response => response.json())
        .then(res => {
            if(res.success) {
                renderTable(res.table);
                renderStats(res.stats);
                renderLeaderboard(res.leaderboard);
                
                // Detection for new joins
                detectNewPlayers(res.table);
            }
        })
        .catch(err => {
            console.error("Gagal memuat data dari API", err);
        });
}

function renderTable(dataArray) {
    if(dataArray.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada data ditemukan</td></tr>`;
        return;
    }

    let html = '';
    dataArray.forEach(row => {
        let statusBadge = row.is_online 
            ? `<span class="status-badge status-online">Online</span>` 
            : `<span class="status-badge status-offline">Offline</span>`;
            
        html += `
            <tr>
                <td><strong>${row.username}</strong></td>
                <td><small class="text-secondary">${row.userId}</small></td>
                <td>${row.join_time_format}</td>
                <td>${row.leave_time_format}</td>
                <td>${row.duration}</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    });
    tableBody.innerHTML = html;
}

function renderStats(stats) {
    if(sOnline) sOnline.textContent = stats.online_now;
    if(sToday) sToday.textContent = stats.total_today;
    if(sBusiest) sBusiest.textContent = stats.busiest_hour;
}

function renderLeaderboard(lbData) {
    if(lbData.length === 0) {
        leaderboardDiv.innerHTML = `<span class="text-secondary">Belum ada pemain dengan waktu tayang yang cukup.</span>`;
        return;
    }

    let html = '';
    lbData.forEach((row, index) => {
        let ranksEmoji = ['🏆','🥈','🥉'];
        let rankIco = ranksEmoji[index] || `#${index+1}`;
        html += `
            <div class="lb-item">
                <div class="lb-rank">${rankIco}</div>
                <div>
                    <div class="lb-name">${row.username}</div>
                    <div class="lb-dur">${row.duration_format}</div>
                </div>
            </div>
        `;
    });
    leaderboardDiv.innerHTML = html;
}

function detectNewPlayers(tableData) {
    // Collect currently online userIds from the fresh data
    const currentOnline = tableData.filter(row => row.is_online).map(row => row.userId);
    
    // First run optimization to prevent spam
    if (lastKnownTotal === 0 && tableData.length > 0) {
        lastKnownTotal = tableData.length;
        lastKnownOnline = currentOnline;
        return;
    }

    // Check if total rows increased or new person appears in online array
    if (tableData.length > lastKnownTotal) {
        // Someone joined
        let newCount = tableData.length - lastKnownTotal;
        showNotification("Player Baru!", `${newCount} Aktivitas Record Baru.`);
        lastKnownTotal = tableData.length;
    }

    // New online player Check
    currentOnline.forEach(uid => {
        if (!lastKnownOnline.includes(uid)) {
            // Find the username
            let pData = tableData.find(u => u.userId === uid);
            if (pData) {
                showNotification("Player Bergabung", `[${pData.username}] Masuk ke dalam game!`);
            }
        }
    });

    // Save as old data for next fetch
    lastKnownOnline = currentOnline;
}

// ========= EVENT LISTENERS =========
if(filterSelect) {
    filterSelect.addEventListener('change', loadData);
}

if(searchInput) {
    let timeoutId;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(loadData, 500); // 500ms delay debouncing
    });
}

if(btnReset) {
    btnReset.addEventListener('click', function() {
        const sure = confirm("Peringatan! Ini akan menghapus SEMUA Data Absensi dari Database! Anda yakin?");
        if(sure) {
            fetch('ajax/reset_data.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success) {
                    loadData(); // reload
                }
            })
            .catch(err => {
                alert("Gagal mereset data.");
            });
        }
    });
}

// Init Load Data
if(tableBody) {
    loadData();
    // Refresh Real-Time setiap 5 Detik
    setInterval(loadData, 5000);
}
