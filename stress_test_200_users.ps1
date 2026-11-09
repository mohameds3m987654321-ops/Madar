$ErrorActionPreference = 'SilentlyContinue'

$base = 'http://localhost:8000'
$n = 200
$csrfUrl = "$base/register"

# get csrf token + cookie from the register page
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$page = Invoke-WebRequest -Uri $csrfUrl -WebSession $session -UseBasicParsing -TimeoutSec 15
$html = $page.Content
$token = $null
if ($html -match 'name="_token"\s+value="([^"]+)"') { $token = $Matches[1] }
if (-not $token -and $html -match '"_token"\s*:\s*"([^"]+)"') { $token = $Matches[1] }
Write-Output "CSRF token found: $($null -ne $token)"

if (-not $token) { Write-Output 'NO CSRF TOKEN - aborting'; exit 1 }

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$script:results = New-Object System.Collections.Concurrent.ConcurrentBag[object]

$jobs = @()
$swAll = [System.Diagnostics.Stopwatch]::StartNew()

# 200 jobs, each is a fire-and-forget POST with its own session
$runspacePool = [runspacefactory]::CreateRunspacePool(1, 100)
$runspacePool.Open()
$psCode = {
    param($idx, $base, $token, $stamp)
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    try {
        $p = Invoke-WebRequest -Uri "$base/register" -WebSession $s -UseBasicParsing -TimeoutSec 15
        if ($p.Content -match 'name="_token"\s+value="([^"]+)"') { $token = $Matches[1] }
    } catch {}
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $body = @{
            _token           = $token
            name             = "StressUser$stamp`_$idx"
            email            = "stress$stamp`_$idx@test.local"
            student_phone    = "0100000$($idx.ToString('0000'))"
            parent_phone     = "0110000$($idx.ToString('0000'))"
            education_stage  = 'secondary'
            education_grade  = '1'
            education_system = 'general'
            education_type   = 'general'
            password         = 'Str0ngPass!2026'
            password_confirmation = 'Str0ngPass!2026'
        }
        $r = Invoke-WebRequest -Uri "$base/users" -Method POST -Body $body -WebSession $s -UseBasicParsing -TimeoutSec 60
        [pscustomobject]@{ idx = $idx; status = [int]$r.StatusCode; ms = $sw.ElapsedMilliseconds; err = '' }
    } catch {
        $status = 0
        if ($_.Exception.Response) { $status = [int]$_.Exception.Response.StatusCode }
        [pscustomobject]@{ idx = $idx; status = $status; ms = $sw.ElapsedMilliseconds; err = $_.Exception.Message.Substring(0, [Math]::Min(120, $_.Exception.Message.Length)) }
    }
}

$handles = @()
for ($i = 1; $i -le $n; $i++) {
    $ps = [powershell]::Create()
    $ps.RunspacePool = $runspacePool
    [void]$ps.AddScript($psCode).AddArgument($i).AddArgument($base).AddArgument($token).AddArgument($stamp)
    $handles += [pscustomobject]@{ PS = $ps; Handle = $ps.BeginInvoke() }
    if ($i % 25 -eq 0) { Start-Sleep -Milliseconds 20 } # tiny stagger so all 200 land within ~1 second
}

foreach ($h in $handles) {
    $out = $h.PS.EndInvoke($h.Handle)
    foreach ($o in $out) { $script:results.Add($o) }
    $h.PS.Dispose()
}
$runspacePool.Close()
$swAll.Stop()

$results = $script:results | Sort-Object idx
$ok   = ($results | Where-Object { $_.status -in 200,301,302 }).Count
$429  = ($results | Where-Object { $_.status -eq 429 }).Count
$422  = ($results | Where-Object { $_.status -eq 422 }).Count
$500  = ($results | Where-Object { $_.status -ge 500 }).Count
$other= ($results | Where-Object { $_.status -notin 200,301,302,422,429 -and $_.status -lt 500 }).Count
$errs = ($results | Where-Object { $_.status -eq 0 }).Count

Write-Output '===== STRESS TEST REPORT ====='
Write-Output ("Total requests      : $n")
Write-Output ("Total wall time     : {0:N2} s" -f $swAll.Elapsed.TotalSeconds)
Write-Output ("Success (200/302)   : $ok")
Write-Output ("Rate-limited (429)  : $429")
Write-Output ("Validation (422)    : $422")
Write-Output ("Server error (5xx)  : $500")
Write-Output ("Other 4xx           : $other")
Write-Output ("Connection failures : $errs")
$times = $results | Where-Object { $_.ms -gt 0 } | Select-Object -ExpandProperty ms
if ($times) {
    $sorted = $times | Sort-Object
    Write-Output ("Latency avg/min/max : {0:N0} / {1} / {2} ms" -f (($times | Measure-Object -Average).Average), $sorted[0], $sorted[-1])
}
Write-Output '--- Sample of errors ---'
$results | Where-Object { $_.status -ne 200 -and $_.status -ne 302 } | Select-Object -First 10 idx,status,ms,err | Format-Table -AutoSize | Out-String
$results | Export-Csv -Path "stress_results_$stamp.csv" -NoTypeInformation -Encoding UTF8
Write-Output "CSV saved: stress_results_$stamp.csv"
