<?php
/**
 * Cookie Test - Shows cookies on NEXT page load
 * Visit with UTM: http://localhost/demo/test_cookies_final.php?utm_source=google
 * Then refresh to see cookies
 */
require_once dirname(__FILE__) . '/wp-load.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookie Capture Test - Final</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        h1, h2 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .info { background: #cfe2ff; color: #084298; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .warning { background: #fff3cd; color: #664d03; padding: 15px; border-radius: 5px; margin: 15px 0; }
        code { background: #f0f0f0; padding: 3px 8px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .highlight { background: #d4edda !important; }
        button { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h1>🍪 UTM Cookie Capture Test - FINAL</h1>
    
    <?php
    $has_utm_in_get = false;
    $has_utm_in_cookie = false;
    
    // Check if we have UTM in GET
    $utm_params = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
    foreach ($utm_params as $param) {
        if (isset($_GET[$param])) {
            $has_utm_in_get = true;
            break;
        }
    }
    
    // Check if we have UTM in COOKIE
    foreach ($utm_params as $param) {
        if (isset($_COOKIE['edubot_' . $param])) {
            $has_utm_in_cookie = true;
            break;
        }
    }
    ?>
    
    <h2>📊 Current State:</h2>
    
    <!-- Current Request GET -->
    <h3>1️⃣ GET Parameters (from URL)</h3>
    <?php if ($has_utm_in_get): ?>
        <div class="success">✅ URL contains UTM parameters</div>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
            <?php foreach ($utm_params as $param): ?>
                <?php if (isset($_GET[$param])): ?>
                    <tr>
                        <td><code><?php echo $param; ?></code></td>
                        <td><strong><?php echo htmlspecialchars($_GET[$param]); ?></strong></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="warning">⚠️ No UTM parameters in URL</div>
    <?php endif; ?>
    
    <!-- Cookies from Browser -->
    <h3>2️⃣ Browser Cookies (from $_COOKIE)</h3>
    <?php if ($has_utm_in_cookie): ?>
        <div class="success">✅ UTM Cookies Found! Capture is working!</div>
        <table>
            <tr>
                <th>Cookie Name</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <?php foreach ($utm_params as $param): ?>
                <?php if (isset($_COOKIE['edubot_' . $param])): ?>
                    <tr class="highlight">
                        <td><code>edubot_<?php echo $param; ?></code></td>
                        <td><strong><?php echo htmlspecialchars($_COOKIE['edubot_' . $param]); ?></strong></td>
                        <td>✅ Persisted</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <?php if ($has_utm_in_get): ?>
            <div class="info">
                <strong>ℹ️ Cookies being SET now</strong><br>
                Cookies set with <code>setcookie()</code> appear in $_COOKIE on the NEXT page load<br>
                Log shows: <code>EduBot Bootstrap: Successfully set 3 UTM cookies</code><br>
                <br>
                <button onclick="location.reload()">🔄 Refresh Page to See Cookies</button>
            </div>
        <?php else: ?>
            <div class="error">❌ No UTM cookies found and no GET parameters</div>
            <p>Start fresh:</p>
            <ol>
                <li>Clear cookies: <code>F12 → Application → Cookies → Clear</code></li>
                <li>Click button below to test</li>
            </ol>
            <button onclick="window.location='http://localhost/demo/test_cookies_final.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025'">
                🧪 Test with Google Ads
            </button>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Flow Explanation -->
    <h2>🔄 How Cookie Capture Works</h2>
    <div class="info">
        <strong>Flow:</strong><br>
        <ol>
            <li><strong>First Visit:</strong> User clicks ad with UTM parameters
                <code style="display: block; margin: 5px 0;">?utm_source=google</code>
            </li>
            <li><strong>Bootstrap Runs:</strong> EduBot code runs BEFORE WordPress
                <code style="display: block; margin: 5px 0;">setcookie('edubot_utm_source', 'google', ...)</code>
            </li>
            <li><strong>Headers Sent:</strong> Browser receives Set-Cookie header
                <code style="display: block; margin: 5px 0;">Set-Cookie: edubot_utm_source=google; Path=/; ...</code>
            </li>
            <li><strong>Browser Stores:</strong> Browser stores cookie
                <code style="display: block; margin: 5px 0;">Cookies: edubot_utm_source=google</code>
            </li>
            <li><strong>Next Request:</strong> Browser sends cookie back
                <code style="display: block; margin: 5px 0;">$_COOKIE['edubot_utm_source'] = 'google'</code>
            </li>
        </ol>
    </div>
    
    <!-- Test Scenarios -->
    <h2>✅ Test Scenarios</h2>
    
    <h3>Scenario 1: First-Time Test (Cookie Being Created)</h3>
    <div class="info">
        <p>You're currently in this scenario if you see "GET parameters" above but "No cookies"</p>
        <p><strong>Why?</strong> Browser is receiving Set-Cookie header right now</p>
        <p><strong>Next step:</strong> Refresh this page</p>
        <button onclick="location.reload()">🔄 Refresh Now</button>
    </div>
    
    <h3>Scenario 2: Cookie Retrieval (After Refresh)</h3>
    <div class="info">
        <p>You're in this scenario if you see both "GET parameters" AND "Cookies"</p>
        <p><strong>Status:</strong> ✅ Cookie capture is working perfectly!</p>
        <p>Now test the full flow:</p>
        <ol>
            <li>Go to chatbot: <a href="http://localhost/demo/" target="_blank">http://localhost/demo/</a></li>
            <li>Submit an enquiry</li>
            <li>Check database: <code>SELECT source FROM wp_edubot_enquiries ORDER BY created_at DESC LIMIT 1;</code></li>
            <li>Source should be: <code>google</code> (not "chatbot")</li>
        </ol>
    </div>
    
    <h3>Scenario 3: Persistent Cookies (After Browser Close)</h3>
    <div class="info">
        <p>Close browser completely, then return to this page</p>
        <p>If cookies still show, they persisted for 30 days ✅</p>
    </div>
    
    <!-- Debug Info -->
    <h2>🔍 Debug Information</h2>
    <table>
        <tr>
            <th>Property</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Server Time</strong></td>
            <td><?php echo date('Y-m-d H:i:s'); ?></td>
        </tr>
        <tr>
            <td><strong>Domain</strong></td>
            <td><?php echo $_SERVER['HTTP_HOST']; ?></td>
        </tr>
        <tr>
            <td><strong>HTTPS</strong></td>
            <td><?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '✅ Yes' : '⚠️ No'; ?></td>
        </tr>
        <tr>
            <td><strong>GET params</strong></td>
            <td><?php echo $has_utm_in_get ? '✅ Found' : '❌ None'; ?></td>
        </tr>
        <tr>
            <td><strong>Cookies</strong></td>
            <td><?php echo $has_utm_in_cookie ? '✅ Found' : '❌ None'; ?></td>
        </tr>
        <tr>
            <td><strong>Cookie Lifetime</strong></td>
            <td>30 days (2,592,000 seconds)</td>
        </tr>
        <tr>
            <td><strong>Cookie Path</strong></td>
            <td>/ (entire site)</td>
        </tr>
    </table>
    
    <!-- Log Check -->
    <h2>📝 Check WordPress Log</h2>
    <p>View: <code>wp-content/debug.log</code></p>
    <p>Should contain:</p>
    <code style="display: block; background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 3px;">
EduBot Bootstrap: Set cookie edubot_utm_source = google<br>
EduBot Bootstrap: Set cookie edubot_utm_medium = cpc<br>
EduBot Bootstrap: Set cookie edubot_utm_campaign = admissions_2025<br>
EduBot Bootstrap: Successfully set 3 UTM cookies
    </code>
    
    <!-- Quick Test Button -->
    <h2>🚀 Quick Test</h2>
    <p>Start fresh test:</p>
    <div style="margin: 20px 0;">
        <button onclick="
            // Clear cookies via JS (won't work with HttpOnly, but try anyway)
            document.cookie = 'edubot_utm_source=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
            document.cookie = 'edubot_utm_medium=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
            document.cookie = 'edubot_utm_campaign=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
            // Redirect to test URL
            window.location = 'http://localhost/demo/test_cookies_final.php?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov';
        ">
            Test with Facebook Ads
        </button>
        
        <button onclick="window.location = 'http://localhost/demo/test_cookies_final.php?utm_source=email&utm_medium=newsletter&utm_campaign=parent_outreach'">
            Test with Email Campaign
        </button>
        
        <button onclick="window.location = 'http://localhost/demo/test_cookies_final.php?utm_source=organic_search&utm_medium=search&utm_campaign=seo'">
            Test with Organic Search
        </button>
    </div>
    
    <!-- Final Checklist -->
    <h2>✅ Success Checklist</h2>
    <div style="margin: 20px 0;">
        <p>✅ <strong>If ALL of these are true, cookies are working:</strong></p>
        <ol>
            <li><?php echo $has_utm_in_get ? '✅' : '❌'; ?> <strong>URL has UTM parameters</strong></li>
            <li><?php echo $has_utm_in_cookie ? '✅' : '⏳'; ?> <strong>Cookies appear in $_COOKIE</strong> (may need refresh)</li>
            <li>✅ <strong>Log shows "Successfully set X UTM cookies"</strong></li>
            <li>⏳ <strong>Submit enquiry via chatbot</strong></li>
            <li>⏳ <strong>Database shows correct source</strong> (not "chatbot")</li>
        </ol>
    </div>

</div>
</body>
</html>
