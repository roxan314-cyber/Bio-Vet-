$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    throw 'No se encontró PHP en PATH. Instala PHP o agrega C:\xampp\php a PATH.'
}

$dotenvPath = Join-Path $projectRoot '.env'
if (-not (Test-Path -LiteralPath $dotenvPath)) {
    New-Item -ItemType File -Path $dotenvPath | Out-Null
}

$dotenvContent = [System.IO.File]::ReadAllText($dotenvPath)
if ($dotenvContent -notmatch '(?m)^\s*JWT_SECRET\s*=\s*\S+') {
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $bytes = New-Object byte[] 48
    $random.GetBytes($bytes)
    $random.Dispose()

    $secret = [System.BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
    $newline = [System.Environment]::NewLine
    if ($dotenvContent.Length -gt 0 -and -not $dotenvContent.EndsWith("`n")) {
        [System.IO.File]::AppendAllText($dotenvPath, $newline, (New-Object System.Text.UTF8Encoding($false)))
    }
    [System.IO.File]::AppendAllText(
        $dotenvPath,
        "JWT_SECRET=$secret$newline",
        (New-Object System.Text.UTF8Encoding($false))
    )
}

$dbPort = 3306
if ($env:DB_PORT) {
    $dbPort = [int]$env:DB_PORT
}

$mysqlReady = Test-NetConnection -ComputerName 127.0.0.1 -Port $dbPort -InformationLevel Quiet
if (-not $mysqlReady) {
    throw "MySQL no responde en 127.0.0.1:$dbPort. Inicia MySQL desde XAMPP y vuelve a ejecutar este script."
}

$router = Join-Path $projectRoot 'router.php'
Write-Host "BioVet: http://127.0.0.1:8000/"
Write-Host 'Comprobación de la API y base de datos: http://127.0.0.1:8000/api/health'
Write-Host 'Presiona Ctrl+C para detener el servidor.'

& $php.Source -S 127.0.0.1:8000 -t $projectRoot $router
