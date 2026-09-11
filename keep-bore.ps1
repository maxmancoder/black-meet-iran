# keep-bore.ps1 - keep the bore TURN tunnel alive and record its public host:port
$bore = 'C:\Users\BLACKL~1\AppData\Local\Temp\opencode\bore\bore.exe'
$tf = 'C:\xampp\htdocs\black-meet\turn_host.txt'
$out = 'C:\Users\BLACKL~1\AppData\Local\Temp\opencode\bore.out'
while ($true) {
    $p = Start-Process -FilePath $bore -PassThru -WindowStyle Hidden `
        -ArgumentList 'local','3478','--to','bore.pub' -RedirectStandardOutput $out
    Start-Sleep -Seconds 6
    $txt = Get-Content $out -Raw -ErrorAction SilentlyContinue
    if ($txt -match 'listening at ([^:\s]+:\d+)') {
        Set-Content -LiteralPath $tf -Value $Matches[1] -Encoding ascii
    }
    $p.WaitForExit()
    Start-Sleep -Seconds 3
}
