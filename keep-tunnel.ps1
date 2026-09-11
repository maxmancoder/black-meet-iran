# keep-tunnel.ps1 - keep the localhost.run website tunnel alive and record its URL
# Self-heals: if the public URL returns non-200 (e.g. localhost.run drops the
# forwarding while SSH stays connected), it kills SSH so a fresh session starts.
$ssh = 'C:\windows\System32\OpenSSH\ssh.exe'
$urlFile = 'C:\xampp\htdocs\black-meet\tunnel_url.txt'
$log = 'C:\Users\BLACKL~1\AppData\Local\Temp\opencode\ssh2.log'
while ($true) {
    if (Test-Path $log) { Remove-Item $log -Force }
    $p = Start-Process -FilePath $ssh -PassThru -WindowStyle Hidden `
        -ArgumentList '-o','StrictHostKeyChecking=no','-o','ServerAliveInterval=20','-o','ServerAliveCountMax=3','-R','80:localhost:80','nokey@localhost.run' `
        -RedirectStandardOutput $log
    Start-Sleep -Seconds 6
    $txt = Get-Content $log -Raw -ErrorAction SilentlyContinue
    $m = [regex]::Matches($txt, '([a-z0-9]+\.lhr\.life) tunneled')
    if ($m.Count -gt 0) {
        # Always take the LAST assigned tunnel (the log may contain stale lines)
        $host2 = $m[$m.Count - 1].Groups[1].Value
        Set-Content -LiteralPath $urlFile -Value ("https://" + $host2 + "/black-meet/") -Encoding ascii
    }
    # Health monitor: poll the public site; restart on failure.
    while (-not $p.HasExited) {
        Start-Sleep -Seconds 30
        $raw = Get-Content $urlFile -ErrorAction SilentlyContinue
        $base = if ($raw) { [string]$raw.TrimEnd('/') } else { '' }
        $ok = $false
        if ($base) {
            try {
                $r = Invoke-WebRequest -Uri ($base + '/index.php') -UseBasicParsing -TimeoutSec 20 -ErrorAction Stop
                if ($r.StatusCode -eq 200) { $ok = $true }
            } catch { $ok = $false }
        }
        if (-not $ok) {
            # one more check to avoid flapping on a single blip
            Start-Sleep -Seconds 10
            $ok2 = $false
            try {
                $r2 = Invoke-WebRequest -Uri ($base + '/index.php') -UseBasicParsing -TimeoutSec 20 -ErrorAction Stop
                if ($r2.StatusCode -eq 200) { $ok2 = $true }
            } catch { $ok2 = $false }
            if (-not $ok2) {
                try { $p.Kill() } catch {}
                break
            }
        }
    }
    $p.WaitForExit()
    Start-Sleep -Seconds 3
}
