# Start script for Patient Management System
$phpPath = "C:\tools\php84"

# Add PHP to PATH if not already there
if (-not ($env:Path -split ';' -contains $phpPath)) {
    $env:Path = "$phpPath;$env:Path"
}

# Ensure data directory exists and is writable
$dataPath = Join-Path $PSScriptRoot "backend\data"
if (-not (Test-Path $dataPath)) {
    New-Item -ItemType Directory -Path $dataPath -Force
}

# Set permissions
$acl = Get-Acl $dataPath
$accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("Everyone","FullControl","Allow")
$acl.SetAccessRule($accessRule)
Set-Acl $dataPath $acl

Write-Host "Starting PHP development server..." -ForegroundColor Cyan
Write-Host "Access the application at: http://localhost:8000/" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow

# Start the server with router
php -S localhost:8000 router.php