#!/usr/bin/env powershell
<#
.SYNOPSIS
    Deployment Status Checker for D:\xampp\htdocs\demo
.DESCRIPTION
    Verifies all EduBot Pro files are correctly deployed to the new location
#>

$newPath = "D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "✅ DEPLOYMENT VERIFICATION" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Check if plugin directory exists
if (Test-Path $newPath) {
    Write-Host "✅ Plugin directory exists: $newPath" -ForegroundColor Green
} else {
    Write-Host "❌ Plugin directory NOT found: $newPath" -ForegroundColor Red
    exit 1
}

# List of critical files
$criticalFiles = @(
    "edubot-pro.php",
    "includes\class-ai-validator.php",
    "includes\class-ai-admin-page.php",
    "includes\class-security-manager.php",
    "includes\class-edubot-shortcode.php",
    "includes\views\admin-ai-validator-settings.php"
)

Write-Host "`n📁 Checking critical files:" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

$allPresent = $true

foreach ($file in $criticalFiles) {
    $fullPath = Join-Path $newPath $file
    if (Test-Path $fullPath) {
        $fileSize = (Get-Item $fullPath).Length / 1KB
        Write-Host "✅ $file ($([math]::Round($fileSize, 2)) KB)" -ForegroundColor Green
    } else {
        Write-Host "❌ $file - MISSING" -ForegroundColor Red
        $allPresent = $false
    }
}

# Check for key code patterns
Write-Host "`n🔍 Verifying code content:" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan

# Check AI validator fix
$aiValidatorPath = Join-Path $newPath "includes\class-ai-validator.php"
$hasArrayMerge = Select-String -Path $aiValidatorPath -Pattern "array_merge" -Quiet
$hasInstanceOf = Select-String -Path $aiValidatorPath -Pattern "instanceof EduBot_AI_Validator" -Quiet

if ($hasArrayMerge -and $hasInstanceOf) {
    Write-Host "✅ AI Validator memory leak fix deployed" -ForegroundColor Green
} else {
    Write-Host "❌ AI Validator fix not found" -ForegroundColor Red
}

# Check Security Manager fix
$securityPath = Join-Path $newPath "includes\class-security-manager.php"
$hasSecurityFix = Select-String -Path $securityPath -Pattern "Only block private IPs in production" -Quiet

if ($hasSecurityFix) {
    Write-Host "✅ Security Manager URL validation fix deployed" -ForegroundColor Green
} else {
    Write-Host "❌ Security Manager fix not found" -ForegroundColor Red
}

# Check Admin Page
$adminPagePath = Join-Path $newPath "includes\class-ai-admin-page.php"
$hasAdminMenu = Select-String -Path $adminPagePath -Pattern "'edubot-pro'" -Quiet

if ($hasAdminMenu) {
    Write-Host "✅ AI Admin Page with correct menu registration deployed" -ForegroundColor Green
} else {
    Write-Host "❌ AI Admin Page fix not found" -ForegroundColor Red
}

# Summary
Write-Host "`n================================" -ForegroundColor Cyan
if ($allPresent) {
    Write-Host "✅ ALL FILES DEPLOYED SUCCESSFULLY" -ForegroundColor Green
    Write-Host "`n📝 Next Steps:" -ForegroundColor Cyan
    Write-Host "1. Hard refresh browser: Ctrl+Shift+Delete"
    Write-Host "2. Go to: http://localhost/demo/"
    Write-Host "3. Deactivate and reactivate EduBot Pro plugin"
    Write-Host "4. Navigate to: EduBot Pro → School Settings"
    Write-Host "5. Test logo upload or navigate to: EduBot Pro → AI Validator"
} else {
    Write-Host "❌ SOME FILES ARE MISSING" -ForegroundColor Red
    Write-Host "Please verify the deployment" -ForegroundColor Yellow
}

Write-Host "`n================================" -ForegroundColor Cyan
Write-Host "New URL: http://localhost/demo/" -ForegroundColor Yellow
Write-Host "Plugin Path: $newPath" -ForegroundColor Yellow
Write-Host "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
