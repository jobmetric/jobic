# Development supervisor: one Reverb process per checkout, independent of an interactive shell.
param([string]$PhpExe = '')
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$laragonRoot = Split-Path (Split-Path $projectRoot)
if (-not $PhpExe) {
    $ini = Join-Path $laragonRoot 'usr/laragon.ini'
    if (Test-Path $ini) {
        $version = [regex]::Match((Get-Content $ini -Raw), '(?m)^\[php\]\r?\nVersion=([^\r\n]+)').Groups[1].Value
        if ($version) { $PhpExe = Join-Path $laragonRoot "bin/php/$version/php.exe" }
    }
    if (-not $PhpExe) { $PhpExe = (Get-Command php -ErrorAction Stop).Source }
}
if (-not (Test-Path $PhpExe)) { throw 'The configured PHP executable does not exist.' }
$hash = [System.Security.Cryptography.SHA256]::Create()
$scope = [BitConverter]::ToString($hash.ComputeHash([Text.Encoding]::UTF8.GetBytes($projectRoot.ToLowerInvariant()))).Replace('-', '').Substring(0, 20)
$created = $false
$mutex = [System.Threading.Mutex]::new($true, "Local\JobicReverb$scope", [ref]$created)
if (-not $created) { $mutex.Dispose(); exit 0 }
try {
    Set-Location $projectRoot
    $port = 8080
    $match = [regex]::Match((Get-Content '.env' -Raw), '(?m)^REVERB_SERVER_PORT=(\d+)')
    if ($match.Success) { $port = [int]$match.Groups[1].Value }
    $logDirectory = Join-Path $projectRoot 'storage/logs'
    New-Item -ItemType Directory -Force $logDirectory | Out-Null
    while ($true) {
        try {
            # Never compete with an existing listener, including a previous supervised instance.
            if (-not (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)) {
                $process = Start-Process -FilePath $PhpExe -ArgumentList @('artisan', 'reverb:start') -WorkingDirectory $projectRoot -NoNewWindow -PassThru -RedirectStandardOutput (Join-Path $logDirectory 'reverb.out.log') -RedirectStandardError (Join-Path $logDirectory 'reverb.error.log')
                $process.WaitForExit()
            }
        } catch {
            # Retry startup failures without exposing environment values or request data.
            Add-Content (Join-Path $logDirectory 'reverb-supervisor.log') "$(Get-Date -Format o) Reverb startup failed; retrying."
        }
        Start-Sleep -Seconds 5
    }
} finally {
    $mutex.ReleaseMutex()
    $mutex.Dispose()
}
