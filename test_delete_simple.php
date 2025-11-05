<!DOCTYPE html>
<html>
<head>
    <title>Test Delete AJAX</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { color: green; padding: 10px; background: #f0f0f0; }
        .error { color: red; padding: 10px; background: #fff0f0; }
        .info { color: blue; padding: 10px; background: #f0f0ff; }
    </style>
</head>
<body>
    <h1>Test Delete Application AJAX</h1>
    
    <div id="result"></div>
    
    <p><button onclick="testDelete()">Test Delete (ID=1)</button></p>
    
    <script>
    function testDelete() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Sending delete request...</div>';
        
        // Get nonce from hidden field if available, otherwise prompt
        let nonce = document.getElementById('edubot_admin_nonce') ? document.getElementById('edubot_admin_nonce').value : prompt('Enter nonce:');
        
        if (!nonce) {
            nonce = '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>';
        }
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'edubot_delete_application',
                application_id: '1',
                nonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                resultDiv.innerHTML = '<div class="success">✅ ' + data.data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Error: ' + data.data + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<div class="error">❌ Network Error: ' + error + '</div>';
        });
    }
    </script>
    
    <?php
    // This PHP code runs on page load
    echo '<div class="info">';
    echo '<p>Nonce: ' . wp_create_nonce('edubot_admin_nonce') . '</p>';
    echo '<p>Ajax URL: ' . admin_url('admin-ajax.php') . '</p>';
    echo '</div>';
    ?>
</body>
</html>
