@echo off
REM Deployment Verification for D:\xampp\htdocs\demo

setlocal enabledelayedexpansion

set "pluginPath=D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro"

echo.
echo ================================
echo DEPLOYMENT VERIFICATION
echo ================================
echo.

REM Check main plugin file
if exist "%pluginPath%\edubot-pro.php" (
    echo [OK] edubot-pro.php
) else (
    echo [FAIL] edubot-pro.php not found
)

REM Check AI Validator
if exist "%pluginPath%\includes\class-ai-validator.php" (
    echo [OK] class-ai-validator.php
) else (
    echo [FAIL] class-ai-validator.php not found
)

REM Check AI Admin Page
if exist "%pluginPath%\includes\class-ai-admin-page.php" (
    echo [OK] class-ai-admin-page.php
) else (
    echo [FAIL] class-ai-admin-page.php not found
)

REM Check Security Manager
if exist "%pluginPath%\includes\class-security-manager.php" (
    echo [OK] class-security-manager.php
) else (
    echo [FAIL] class-security-manager.php not found
)

REM Check Shortcode class
if exist "%pluginPath%\includes\class-edubot-shortcode.php" (
    echo [OK] class-edubot-shortcode.php
) else (
    echo [FAIL] class-edubot-shortcode.php not found
)

REM Check Settings UI
if exist "%pluginPath%\includes\views\admin-ai-validator-settings.php" (
    echo [OK] admin-ai-validator-settings.php
) else (
    echo [FAIL] admin-ai-validator-settings.php not found
)

echo.
echo ================================
echo Next Steps:
echo ================================
echo 1. Go to: http://localhost/demo/
echo 2. Hard refresh: Ctrl+Shift+Delete
echo 3. Deactivate and reactivate EduBot Pro
echo 4. Test features (logo upload or AI settings)
echo.
echo Plugin Location: %pluginPath%
echo.
