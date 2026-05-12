param(
    [string] $Target = $env:ZAP_TARGET,
    [string] $ReportDir = "reports/security"
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($Target)) {
    $baseUrl = if ($env:E2E_BASE_URL) { $env:E2E_BASE_URL } else { "http://localhost:8081" }
    $Target = $baseUrl -replace "localhost", "host.docker.internal" -replace "127\.0\.0\.1", "host.docker.internal"
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "Docker is required to run OWASP ZAP baseline. Install Docker or run ZAP manually against $Target."
}

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$outputPath = Join-Path $repoRoot $ReportDir
New-Item -ItemType Directory -Force -Path $outputPath | Out-Null

$dockerMount = (Resolve-Path $outputPath).Path -replace "\\", "/"

docker run --rm `
    -v "${dockerMount}:/zap/wrk:rw" `
    ghcr.io/zaproxy/zaproxy:stable `
    zap-baseline.py `
    -t $Target `
    -r zap-baseline.html `
    -J zap-baseline.json `
    -x zap-baseline.xml

if ($LASTEXITCODE -ne 0) {
    throw "OWASP ZAP baseline failed with exit code $LASTEXITCODE."
}
