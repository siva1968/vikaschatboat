<!DOCTYPE html>
<html>
<head>
    <title>Debug Delete AJAX</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .log { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; }
        .success { border-left-color: green; color: green; }
        .error { border-left-color: red; color: red; }
        .info { border-left-color: blue; color: blue; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Debug Delete Application AJAX</h1>
    
    <button onclick="testDelete()">Test Delete Application (ID=1)</button>
    <button onclick="clearLogs()">Clear Logs</button>
    
    <div id="logs"></div>
    
    <script>
    function log(message, type = 'info') {
        const logsDiv = document.getElementById('logs');
        const logDiv = document.createElement('div');
        logDiv.className = 'log ' + type;
        logDiv.textContent = new Date().toLocaleTimeString() + ' - ' + message;
        logsDiv.appendChild(logDiv);
        console.log('[' + type.toUpperCase() + ']', message);
    }
    
    function clearLogs() {
        document.getElementById('logs').innerHTML = '';
    }
    
    function testDelete() {
        log('Starting delete test...', 'info');
        log('AJAX URL: ' + ajaxurl, 'info');
        
        // Create nonce
        const nonce = '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>';
        log('Nonce: ' + nonce.substring(0, 15) + '...', 'info');
        
        const data = new URLSearchParams();
        data.append('action', 'edubot_delete_application');
        data.append('application_id', '1');
        data.append('nonce', nonce);
        
        log('Sending POST request with:' + JSON.stringify({action: 'edubot_delete_application', application_id: '1'}), 'info');
        
        fetch(ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(response => {
            log('Response Status: ' + response.status, 'info');
            log('Response Headers: Content-Type=' + response.headers.get('content-type'), 'info');
            return response.text();
        })
        .then(text => {
            log('Raw Response: ' + text.substring(0, 200), 'info');
            try {
                const data = JSON.parse(text);
                log('Parsed Response: ' + JSON.stringify(data), data.success ? 'success' : 'error');
            } catch (e) {
                log('Failed to parse JSON: ' + e.message, 'error');
            }
        })
        .catch(error => {
            log('Fetch Error: ' + error.message, 'error');
        });
    }
    </script>
    
    <?php
    // Display WordPress info
    echo '<div class="log info">';
    echo '<strong>WordPress Info:</strong><br>';
    echo 'Admin AJAX URL: <code>' . admin_url('admin-ajax.php') . '</code><br>';
    echo 'Nonce Sample: <code>' . wp_create_nonce('edubot_admin_nonce') . '</code><br>';
    echo 'Current User: ' . (wp_get_current_user()->ID ? wp_get_current_user()->user_login . ' (ID: ' . wp_get_current_user()->ID . ')' : 'Not logged in') . '<br>';
    echo 'User Capabilities: ' . (current_user_can('manage_options') ? '✅ manage_options' : '❌ No manage_options') . '<br>';
    echo '</div>';
    ?>
</body>
</html>
