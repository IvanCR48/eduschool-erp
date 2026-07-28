# PowerShell release packager for Codester (Optimized for Windows MAX_PATH & Security)
$projectDir = "c:\xampp\htdocs\SistemaAdmin"
$outputZip = "c:\xampp\htdocs\SistemaAdmin\school-management-system-codester.zip"

if (Test-Path $outputZip) {
    Remove-Item $outputZip -Force
}

$tempDir = Join-Path $env:TEMP "SistemaAdminRelease"
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}

New-Item -ItemType Directory -Path $tempDir | Out-Null

$excludeList = @(
    "scratch*",
    ".git*",
    "school-management-system-codester.zip",
    ".env",
    "node_modules",
    "*.log"
)

Write-Host "Copying project files to temporary release staging..."
Get-ChildItem -Path $projectDir -Exclude $excludeList | ForEach-Object {
    if ($_.Name -ne "scratch" -and $_.Name -ne ".git" -and $_.Name -ne ".github" -and $_.Name -ne ".env") {
        Copy-Item -Path $_.FullName -Destination $tempDir -Recurse -Force
    }
}

Write-Host "Removing private API secret files (*.local.php)..."
Get-ChildItem -Path "$tempDir\config" -Filter "*.local.php" | ForEach-Object {
    Remove-Item $_.FullName -Force
}

Write-Host "Cleaning non-production unit tests and snapshots from vendor..."
# Remove vendor unit test files that cause Windows MAX_PATH (260 char limit) issues
Get-ChildItem -Path "$tempDir\vendor" -Recurse -Directory | Where-Object { 
    $_.Name -eq "test" -or $_.Name -eq "tests" -or $_.Name -eq "Test" -or $_.Name -eq "Tests" -or $_.Name -eq "__snapshots__"
} | ForEach-Object {
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host "Creating clean release ZIP file: $outputZip..."
Compress-Archive -Path "$tempDir\*" -DestinationPath $outputZip -CompressionLevel Optimal

# Cleanup tempDir
Remove-Item $tempDir -Recurse -Force

$zipSize = (Get-Item $outputZip).Length / 1MB
Write-Host "✅ Release ZIP created successfully! Size: $([math]::Round($zipSize, 2)) MB"
