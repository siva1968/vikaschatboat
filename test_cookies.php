<?php
/**
 * Direct Cookie Test - Simpler version
 */
require_once dirname(__FILE__) . '/wp-load.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookie Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        code { background: #f5f5f5; padding: 3px 8px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>

<h1>🍪 Direct Cookie Test</h1>

<h2>Cookies in $_COOKIE:</h2>
<?php if (!empty($_COOKIE)): ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Value</th>
        </tr>
        <?php foreach ($_COOKIE as $name => $value): ?>
            <tr<?php echo strpos($name, 'edubot_') === 0 ? ' style="background: #d4edda;"' : ''; ?>>
                <td><code><?php echo htmlspecialchars($name); ?></code></td>
                <td><code><?php echo htmlspecialchars($value); ?></code></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <div class="box error">❌ No cookies found</div>
<?php endif; ?>

<h2>GET Parameters:</h2>
<?php if (!empty($_GET)): ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Value</th>
        </tr>
        <?php foreach ($_GET as $name => $value): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($name); ?></code></td>
                <td><code><?php echo htmlspecialchars($value); ?></code></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Check Log:</h2>
<div class="box">
    <p>Check WordPress debug log:</p>
    <code>wp-content/debug.log</code>
    <p>Look for:</p>
    <code style="display: block; margin-top: 10px;">
EduBot Bootstrap: Set cookie edubot_utm_source = google
    </code>
</div>

<h2>Test Steps:</h2>
<ol>
    <li>Clear browser cookies completely (F12 → Application → Cookies → Delete all for localhost)</li>
    <li>Hard refresh this page (Ctrl+F5)</li>
    <li>Copy this URL: <code>http://localhost/demo/test_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025</code></li>
    <li>Paste in address bar and press Enter</li>
    <li>You should see:
        <ul>
            <li>GET parameters displayed</li>
            <li>Cookies table with edubot_utm_* entries highlighted in green</li>
        </ul>
    </li>
</ol>

<div class="box success" style="margin-top: 30px;">
    <strong>Ready?</strong><br>
    <a href="http://localhost/demo/test_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin-top: 10px;">
        Click to Test Cookies
    </a>
</div>

</body>
</html>
