Clear-Host
$filePath = ".\composer.lock"

if (Test-Path $filePath) {
    Remove-Item -Path $filePath -Force
    Write-Host "Removed lock file."
} else {
    Write-Host "Lock file does not exist."
}

& php .\bin\composer.phar update
