# MindCrafts AI Clean Production ZIP Builder
$ErrorActionPreference = "Stop"

$currentDir = $PSScriptRoot
if ([string]::IsNullOrEmpty($currentDir)) {
    $currentDir = (Get-Location).Path
}

$stagingDir = Join-Path $env:TEMP "mindcrafts-ai"
$distDir    = Join-Path $currentDir "dist"
$zipOutput  = Join-Path $distDir "mindcrafts-ai-for-elementor.zip"

Write-Host "Creating clean staging directory..." -ForegroundColor Cyan
if (Test-Path $stagingDir) {
    Remove-Item -Path $stagingDir -Recurse -Force
}

New-Item -ItemType Directory -Path $stagingDir -Force | Out-Null

Write-Host "Copying core production files..." -ForegroundColor Cyan
Copy-Item -Path (Join-Path $currentDir "mindcrafts-ai.php") -Destination $stagingDir
Copy-Item -Path (Join-Path $currentDir "readme.txt") -Destination $stagingDir
Copy-Item -Path (Join-Path $currentDir "README.md") -Destination $stagingDir
Copy-Item -Path (Join-Path $currentDir "CHANGELOG.md") -Destination $stagingDir
Copy-Item -Path (Join-Path $currentDir "LICENSE") -Destination $stagingDir
if (Test-Path (Join-Path $currentDir "uninstall.php")) {
    Copy-Item -Path (Join-Path $currentDir "uninstall.php") -Destination $stagingDir
}
if (Test-Path (Join-Path $currentDir "index.php")) {
    Copy-Item -Path (Join-Path $currentDir "index.php") -Destination $stagingDir
}
if (Test-Path (Join-Path $currentDir "bin")) {
    Copy-Item -Path (Join-Path $currentDir "bin") -Destination $stagingDir -Recurse
}
if (Test-Path (Join-Path $currentDir "assets")) {
    Copy-Item -Path (Join-Path $currentDir "assets") -Destination $stagingDir -Recurse
}
Copy-Item -Path (Join-Path $currentDir "includes") -Destination $stagingDir -Recurse

if (!(Test-Path $distDir)) {
    New-Item -ItemType Directory -Path $distDir -Force | Out-Null
}

$version = "3.1.6"
if (Test-Path (Join-Path $currentDir "mindcrafts-ai.php")) {
    $mainPhp = Get-Content (Join-Path $currentDir "mindcrafts-ai.php") -Raw
    if ($mainPhp -match "define\(\s*'MINDCRAFTS_AI_VERSION',\s*'([^']+)'\s*\);") {
        $version = $matches[1]
    }
}
$cleanZipName = Join-Path $distDir "mindcrafts-ai.zip"
$versionZipName = Join-Path $distDir "mindcrafts-ai-v$version.zip"

foreach ($target in @($zipOutput, $cleanZipName, $versionZipName)) {
    if (Test-Path $target) {
        Remove-Item -Path $target -Force
    }
}

Write-Host "Compressing to cross-platform WordPress ZIP package (enforcing '/' forward-slashes)..." -ForegroundColor Cyan
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::Open($zipOutput, [System.IO.Compression.ZipArchiveMode]::Create)
$files = Get-ChildItem -Path $stagingDir -Recurse -File

foreach ($file in $files) {
    $relPath = $file.FullName.Substring($stagingDir.Length).TrimStart('\', '/')
    $entryName = "mindcrafts-ai/" + $relPath.Replace('\', '/')
    $entry = $zip.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
    $entryStream = $entry.Open()
    $fileStream = [System.IO.File]::OpenRead($file.FullName)
    $fileStream.CopyTo($entryStream)
    $fileStream.Close()
    $entryStream.Close()
}
$zip.Dispose()

# Create copies
Copy-Item -Path $zipOutput -Destination $cleanZipName -Force
Copy-Item -Path $zipOutput -Destination $versionZipName -Force

Remove-Item -Path $stagingDir -Recurse -Force

$zipFile = Get-Item $zipOutput
Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  BUILD SUCCESSFUL! (LINUX & WORDPRESS COMPATIBLE ZIP)" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "Main File   : $($zipFile.FullName)"
Write-Host "Clean File  : $cleanZipName"
Write-Host "Version File: $versionZipName"
Write-Host "Size        : $([math]::Round($zipFile.Length / 1KB, 2)) KB"
Write-Host "All entry paths use UNIX forward-slashes: 'mindcrafts-ai/mindcrafts-ai.php'" -ForegroundColor Cyan
Write-Host "Upload mindcrafts-ai.zip directly to your WordPress Dashboard!" -ForegroundColor Yellow
