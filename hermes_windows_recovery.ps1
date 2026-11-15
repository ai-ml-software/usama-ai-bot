# Hermes Windows recovery - zero-cost, no paid providers
# Run from the project directory containing hermes-install.ps1.
# This uses ASCII-only paths outside Laragon's public web root.
$ErrorActionPreference = "Stop"

$installer = Join-Path (Get-Location) "hermes-install.ps1"
if (-not (Test-Path -LiteralPath $installer -PathType Leaf)) {
    throw "hermes-install.ps1 was not found. Run this script from the directory where it was downloaded."
}
$laragon = "C:\laragon"
if (-not (Test-Path -LiteralPath $laragon -PathType Container)) {
    throw "Expected C:\laragon, but it is not present. Choose another writable ASCII-only directory."
}

$home = Join-Path $laragon "hermes-home"
$checkout = Join-Path $laragon "hermes-agent-runtime"
New-Item -Path $home -ItemType Directory -Force | Out-Null

# The default user-profile path contains non-ASCII characters. Isolate Hermes
# in ASCII-only paths to test whether Git's helper-path validation is affected.
$env:HERMES_HOME = $home
Write-Host "Hermes home: $home"
Write-Host "Hermes checkout: $checkout"
Write-Host "Running original NousResearch installer; no installer code is modified."

& $installer -HermesHome $home -InstallDir $checkout -SkipBrowser -SkipComputerUse -NonInteractive -Verbose

if ($LASTEXITCODE -ne 0) {
    Write-Warning "The installer returned exit code $LASTEXITCODE."
    Write-Host "Review the installation log in $home\logs\install.log."
    exit $LASTEXITCODE
}
Write-Host "Installer completed. Open a new PowerShell window."
Write-Host "Then run:"
Write-Host '$env:HERMES_HOME = "C:\laragon\hermes-home"'
Write-Host 'hermes --help'
Write-Host 'npx @pollinations/cli@latest harness hermes on'
Write-Host 'npx @pollinations/cli@latest harness hermes status'
Write-Host "If installation failed, do not disable security checks or replace Git binaries."
