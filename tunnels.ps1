# tunnels.ps1 - keep BOTH tunnels alive inside this visible window.
# Double-click tunnel.cmd to launch this. Close the window to stop the tunnels.
$root = 'C:\xampp\htdocs\black-meet'

# Spawn the two watchers as children of THIS visible window's session.
Start-Process powershell -WindowStyle Hidden -ExecutionPolicy Bypass -File "$root\keep-tunnel.ps1"
Start-Process powershell -WindowStyle Hidden -ExecutionPolicy Bypass -File "$root\keep-bore.ps1"

Write-Host "============================================================="
Write-Host "  Black Meet - tunnels are running"
Write-Host "  Website URL -> $root\tunnel_url.txt"
Write-Host "  TURN host   -> $root\turn_host.txt"
Write-Host "  Leave this window open. Close it to stop the tunnels."
Write-Host "============================================================="

try {
    while ($true) { Start-Sleep -Seconds 30 }
} finally {
    Get-Process -Name ssh  -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Get-Process -Name bore -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
}
