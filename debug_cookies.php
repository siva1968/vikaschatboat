<?php
/**
 * Debug: Check if UTM Cookies are being captured
 * Visit: http://localhost/demo/debug_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
 */

require_once dirname(__FILE__) . '/wp-load.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug UTM Cookies</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 3px; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .url-box { background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px; margin: 15px 0; }
        .cookie-expired { background: #ffcccc; }
        .cookie-valid { background: #ccffcc; }
    </style>
</head>
<body>
<div class="container">
    <h1>🍪 Debug: UTM Cookies Capture</h1>
    
    <!-- Current Request -->
    <h2>1️⃣ Current Request ($_GET)</h2>
    <?php if (!empty($_GET)): ?>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
            <?php foreach ($_GET as $key => $value): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($key); ?></code></td>
                    <td><code><?php echo htmlspecialchars($value); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="success">✅ URL contains parameters - UTM should be captured to cookies</div>
    <?php else: ?>
        <div class="warning">⚠️ No GET parameters detected - UTM cookies won't be created</div>
        <div class="url-box">
            <strong>Try visiting with UTM parameters:</strong><br>
            <code style="display: block; word-break: break-all; margin-top: 10px;">
                http://localhost/demo/debug_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
            </code>
        </div>
    <?php endif; ?>
    
    <!-- Cookies Captured -->
    <h2>2️⃣ Browser Cookies ($_COOKIE)</h2>
    <?php
    $edubot_cookies = array();
    foreach ($_COOKIE as $key => $value) {
        if (strpos($key, 'edubot_') === 0) {
            $edubot_cookies[$key] = $value;
        }
    }
    
    if (!empty($edubot_cookies)): ?>
        <table>
            <tr>
                <th>Cookie Name</th>
                <th>Value</th>
                <th>Expires</th>
            </tr>
            <?php foreach ($edubot_cookies as $key => $value): 
                // Try to get expiration from browser (we can't directly, but we can estimate)
                $param_name = str_replace('edubot_', '', $key);
            ?>
                <tr class="cookie-valid">
                    <td><code><?php echo htmlspecialchars($key); ?></code></td>
                    <td><code><?php echo htmlspecialchars($value); ?></code></td>
                    <td>~30 days from capture</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="success">✅ EduBot cookies found! Capture is working!</div>
    <?php else: ?>
        <div class="error">❌ No EduBot cookies found - Cookies may not have been captured</div>
    <?php endif; ?>
    
    <!-- Session Data -->
    <h2>3️⃣ Session Data ($_SESSION)</h2>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $edubot_session = array();
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'edubot_') === 0) {
            $edubot_session[$key] = $value;
        }
    }
    
    if (!empty($edubot_session)): ?>
        <table>
            <tr>
                <th>Session Key</th>
                <th>Value</th>
            </tr>
            <?php foreach ($edubot_session as $key => $value): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($key); ?></code></td>
                    <td><code><?php echo htmlspecialchars($value); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="success">✅ Session data present</div>
    <?php else: ?>
        <div class="warning">⚠️ No EduBot session data</div>
    <?php endif; ?>
    
    <!-- Comparison -->
    <h2>4️⃣ Comparison: Cookies vs Session</h2>
    <table>
        <tr>
            <th>Parameter</th>
            <th>In Session</th>
            <th>In Cookie</th>
            <th>Status</th>
        </tr>
        <?php
        $all_params = array(
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid'
        );
        
        foreach ($all_params as $param):
            $in_session = isset($_SESSION['edubot_' . $param]) ? '✅ Yes' : '❌ No';
            $in_cookie = isset($_COOKIE['edubot_' . $param]) ? '✅ Yes' : '❌ No';
            $status = (isset($_SESSION['edubot_' . $param]) || isset($_COOKIE['edubot_' . $param])) ? '✅ Captured' : '⚪ Not captured';
        ?>
            <tr>
                <td><code><?php echo $param; ?></code></td>
                <td><?php echo $in_session; ?></td>
                <td><?php echo $in_cookie; ?></td>
                <td><?php echo $status; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- Test Steps -->
    <h2>5️⃣ Test Steps</h2>
    <ol>
        <li><strong>Clear browser cookies</strong> (DevTools → Application → Cookies → Delete all)</li>
        <li><strong>Copy test URL:</strong>
            <div class="url-box">
                <code>http://localhost/demo/debug_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025</code>
            </div>
        </li>
        <li><strong>Paste in browser</strong> address bar and press Enter</li>
        <li><strong>Check this page:</strong> Cookies section should show ✅</li>
        <li><strong>Open DevTools:</strong> F12 → Application → Cookies → http://localhost
            <br>Should see: <code>edubot_utm_source, edubot_utm_medium, edubot_utm_campaign</code>
        </li>
        <li><strong>Navigate to chatbot:</strong> http://localhost/demo/
            <br>Cookies should still be there (persisted)
        </li>
        <li><strong>Submit enquiry</strong> and check database:
            <div class="url-box">
                <code>SELECT source, utm_source FROM wp_edubot_enquiries ORDER BY created_at DESC LIMIT 1;</code>
            </div>
            Should show: <code>source = "google"</code> (not "chatbot")
        </li>
    </ol>
    
    <!-- Troubleshooting -->
    <h2>6️⃣ Troubleshooting</h2>
    
    <h3>Issue: Cookies not appearing</h3>
    <p>Possible causes:</p>
    <ul>
        <li><strong>Browser cache:</strong> Press Ctrl+F5 to hard refresh</li>
        <li><strong>Private/Incognito mode:</strong> Cookies may not persist in some browsers</li>
        <li><strong>Cookie blocking:</strong> Check browser settings (Privacy → Cookies)</li>
        <li><strong>HTTPS vs HTTP:</strong> Secure cookies won't work on plain HTTP in some cases</li>
        <li><strong>Plugin not activated:</strong> Check WordPress plugins page - EduBot Pro should be active</li>
    </ul>
    
    <h3>Check WordPress Logs</h3>
    <p>View: <code>wp-content/debug.log</code></p>
    <p>Look for lines like:</p>
    <div style="background: #f0f0f0; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; white-space: pre-wrap;">
EduBot: Captured UTM to 30-day cookie: utm_source = google
EduBot: Captured UTM to 30-day cookie: utm_medium = cpc
EduBot: UTM parameters captured to 30-day cookies at: 2025-11-05 14:32:45
    </div>
    
    <h3>Check Plugin Version</h3>
    <p>WordPress Dashboard → Plugins → EduBot Pro</p>
    <p>Should show: <code>Version 1.4.2</code> or higher</p>
    
    <!-- Additional Test -->
    <h2>7️⃣ Live Test with Chatbot</h2>
    <p>Ready to test? Follow these steps:</p>
    <div class="success">
        <strong>Step 1:</strong> Click below to visit with UTM parameters:<br>
        <a href="http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025" target="_blank" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">
            Test with Google Ads UTM
        </a>
    </div>
    
    <div class="success">
        <strong>Step 2:</strong> On the chatbot page, submit an enquiry<br>
        <strong>Step 3:</strong> Return to this debug page<br>
        <strong>Step 4:</strong> Refresh this page - you should see cookies populated
    </div>
    
    <!-- Cookie Details -->
    <h2>8️⃣ Cookie Technical Details</h2>
    <table>
        <tr>
            <th>Property</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Expiration</strong></td>
            <td>30 days (2,592,000 seconds)</td>
        </tr>
        <tr>
            <td><strong>Path</strong></td>
            <td>/ (entire site)</td>
        </tr>
        <tr>
            <td><strong>Domain</strong></td>
            <td><?php echo $_SERVER['HTTP_HOST']; ?></td>
        </tr>
        <tr>
            <td><strong>HttpOnly</strong></td>
            <td>✅ Yes (JavaScript cannot access)</td>
        </tr>
        <tr>
            <td><strong>Secure</strong></td>
            <td><?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '✅ Yes' : '⚠️ No (development)'; ?></td>
        </tr>
        <tr>
            <td><strong>Storage Location</strong></td>
            <td>Browser cookie storage</td>
        </tr>
    </table>
    
    <h2>9️⃣ Success Checklist</h2>
    <div>
        <p>✅ <strong>All checkmarks below = System working!</strong></p>
        <ul>
            <li><?php echo !empty($_GET) ? '✅' : '❌'; ?> URL contains UTM parameters</li>
            <li><?php echo !empty($edubot_cookies) ? '✅' : '❌'; ?> EduBot cookies detected</li>
            <li><?php echo !empty($edubot_session) ? '✅' : '❌'; ?> EduBot session data detected</li>
            <li><?php echo (isset($_COOKIE['edubot_utm_source']) && isset($_COOKIE['edubot_utm_medium']) && isset($_COOKIE['edubot_utm_campaign'])) ? '✅' : '❌'; ?> All three main UTM cookies present</li>
        </ul>
    </div>
    
</div>
</body>
</html>
