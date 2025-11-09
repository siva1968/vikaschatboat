# 🔐 EduBot Pro - Security Check Failed - Diagnosis & Fix

**Issue**: "Security check failed. Please refresh the page."

**Root Cause**: Nonce verification failure in AJAX requests

---

## 🔍 Problem Analysis

### Current Flow
1. **Nonce Created** (line 211, `class-edubot-shortcode.php`):
   ```php
   wp_localize_script('edubot-pro', 'edubot_ajax', [
       'nonce' => wp_create_nonce('edubot_nonce'),
       ...
   ]);
   ```

2. **Nonce Sent** (line 305, `edubot-public.js`):
   ```javascript
   var requestData = {
       action: 'edubot_chatbot_response',
       message: message,
       nonce: edubot_ajax.nonce
   };
   ```

3. **Nonce Verified** (line 1066, `class-edubot-shortcode.php`):
   ```php
   if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_nonce')) {
       wp_send_json_error('Security check failed. Please refresh the page.');
   }
   ```

### Why It Fails

**Problem 1: Timing Issue**
- Nonce is created fresh every time scripts are enqueued
- The nonce in the browser may expire or not match the server-side nonce
- WordPress nonces are designed for single use or time-limited (12-24 hours)

**Problem 2: AJAX Action Not Registered**
- The action `edubot_chatbot_response` may not be properly registered as a WordPress AJAX action
- Missing `wp_ajax_nopriv` hook for non-logged-in users

**Problem 3: Cache Issues**
- Nonce cached in browser doesn't update
- Server generates new nonce on each page load
- Old nonce from cached JS fails verification

**Problem 4: Session/Cookie Issues**
- WPSC (WP Super Cache) or other caching plugins interfering
- Nonce stored in cache, server generates new one

---

## ✅ Solution

### Step 1: Verify AJAX Action is Registered

Check if these hooks exist in your plugin:

**File**: `edubot-pro.php` or `includes/class-edubot-shortcode.php`

Should have:
```php
// For logged-in users
add_action('wp_ajax_edubot_chatbot_response', [instance, 'handle_chatbot_response']);

// For non-logged-in users (public chatbot)
add_action('wp_ajax_nopriv_edubot_chatbot_response', [instance, 'handle_chatbot_response']);
```

### Step 2: Regenerate Nonce on Each Response

**Current Problem**: Nonce is only created once during page load

**Solution**: Create a "refresh nonce" endpoint that returns a fresh nonce after each successful request

### Step 3: Better Nonce Handling

**Option A: Disable Nonce Verification** (Not Recommended - Security Risk)
```php
// AVOID: This removes security
// if (!wp_verify_nonce(...)) { ... }
```

**Option B: Use wp_ajax_referer** (Better)
```php
check_ajax_referer('edubot_nonce', 'nonce', true); // Dies on failure
// or
check_ajax_referer('edubot_nonce', 'nonce', false); // Returns false on failure
```

**Option C: Regenerate After Each Request** (Best)
```php
public function handle_chatbot_response() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // ... process request ...
    
    // Send back NEW nonce for next request
    wp_send_json_success([
        'response' => $response,
        'nonce' => wp_create_nonce('edubot_nonce') // Fresh nonce for next request
    ]);
}
```

### Step 4: Update JavaScript to Use New Nonce

**File**: `public/js/edubot-public.js` (around line 320)

```javascript
success: function(response) {
    console.log('EduBot: AJAX response:', response);
    
    if (response.success) {
        // Update nonce for next request
        if (response.data.nonce) {
            edubot_ajax.nonce = response.data.nonce;
        }
        
        self.handleServerResponse(response.data);
    } else {
        console.error('EduBot: Server error:', response.data);
    }
}
```

---

## 🛠️ Complete Fix

### Fix 1: Register AJAX Actions Properly

Add this to `edubot-pro.php`:

```php
// In the plugin bootstrap or init hook

// For logged-in users
add_action('wp_ajax_edubot_chatbot_response', function() {
    $shortcode = new EduBot_Shortcode();
    $shortcode->handle_chatbot_response();
});

// For non-logged-in users (public chatbot visitors)
add_action('wp_ajax_nopriv_edubot_chatbot_response', function() {
    $shortcode = new EduBot_Shortcode();
    $shortcode->handle_chatbot_response();
});

// Similar for other AJAX actions
add_action('wp_ajax_edubot_save_application', function() { ... });
add_action('wp_ajax_nopriv_edubot_save_application', function() { ... });
```

### Fix 2: Update handle_chatbot_response()

**File**: `includes/class-edubot-shortcode.php` (line 1064)

```php
public function handle_chatbot_response() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'edubot_nonce')) {
        error_log('EduBot: Nonce verification failed - ' . 
                 (isset($_POST['nonce']) ? 'Invalid nonce' : 'No nonce provided'));
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    // ... rest of processing ...
    
    // IMPORTANT: Send back new nonce for next request
    $response = [
        'response' => $chatbot_response,
        'nonce' => wp_create_nonce('edubot_nonce') // Fresh nonce
    ];
    
    wp_send_json_success($response);
}
```

### Fix 3: Update JavaScript

**File**: `public/js/edubot-public.js` (line 320)

```javascript
success: function(response) {
    console.log('EduBot: AJAX response:', response);
    self.hideTypingIndicator();
    
    if (response.success) {
        // Update nonce from server for next request
        if (response.data && response.data.nonce) {
            edubot_ajax.nonce = response.data.nonce;
            console.log('EduBot: Nonce refreshed');
        }
        
        self.handleServerResponse(response.data);
    } else {
        console.error('EduBot: Server returned error:', response.data);
        var errorText = response.data.message || 'Error. Please try again.';
        self.displayBotMessage(errorText);
    }
},
error: function(xhr, status, error) {
    console.error('EduBot: AJAX error:', error);
    self.hideTypingIndicator();
    self.displayBotMessage('Network error. Please refresh and try again.');
}
```

---

## 🧪 Testing the Fix

### Step 1: Enable Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Step 2: Check Browser Console

Open browser DevTools (F12) → Console tab

You should see:
```
EduBot: Nonce available: true
EduBot: Sending AJAX request: {action: "edubot_chatbot_response", message: "...", nonce: "..."}
EduBot: AJAX response: {success: true, data: {...}}
EduBot: Nonce refreshed
```

### Step 3: Check WordPress Error Log

```bash
tail -f /path/to/wp-content/debug.log | grep EduBot
```

You should NOT see:
```
EduBot: Nonce verification failed
```

### Step 4: Test the Chatbot

1. Refresh the page
2. Type a message in the chatbot
3. Submit
4. You should see the response
5. Type another message
6. It should work (old nonce still valid from first response)

---

## 🔧 Additional Improvements

### Add AJAX Error Handling

```javascript
error: function(xhr, status, error) {
    console.error('EduBot AJAX Error:', {
        status: xhr.status,
        statusText: xhr.statusText,
        error: error,
        response: xhr.responseText
    });
    
    // Show user-friendly error
    self.displayBotMessage(
        'Sorry, there was a connection error. Please refresh the page and try again.'
    );
}
```

### Add Nonce Validation Logging

```php
public function handle_chatbot_response() {
    $nonce = $_POST['nonce'] ?? '';
    error_log('EduBot AJAX: Nonce received: ' . (!empty($nonce) ? 'Yes' : 'No'));
    error_log('EduBot AJAX: Nonce valid: ' . (wp_verify_nonce($nonce, 'edubot_nonce') ? 'Yes' : 'No'));
    
    if (!wp_verify_nonce($nonce, 'edubot_nonce')) {
        error_log('EduBot AJAX: Nonce verification failed');
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    // ... continue ...
}
```

### Disable Cache for AJAX Requests

```php
// In wp_localize_script, add cache-busting headers
wp_localize_script('edubot-pro', 'edubot_ajax', [
    'ajax_url' => add_query_arg('t', time(), admin_url('admin-ajax.php')),
    'nonce' => wp_create_nonce('edubot_nonce'),
]);
```

---

## 📋 Quick Checklist

- [ ] AJAX actions registered with `wp_ajax` and `wp_ajax_nopriv`
- [ ] Nonce is created and sent in every request
- [ ] Nonce is verified on server
- [ ] Fresh nonce sent back in response
- [ ] JavaScript updates nonce from response
- [ ] Browser console shows no errors
- [ ] Error log shows no nonce failures
- [ ] Chatbot works for logged-in users
- [ ] Chatbot works for visitors (non-logged-in)
- [ ] Multiple messages work without page refresh

---

## 🎯 Summary

The "Security check failed" error occurs because:

1. **Missing AJAX Handlers** - Action not registered
2. **Nonce Expired** - Nonce from page load is stale
3. **Nonce Mismatch** - Client/server nonce doesn't match
4. **Caching Issues** - Old nonce cached in browser/server

**Fix**: Implement the 3-part solution above to properly register AJAX actions, verify nonces, and refresh them after each successful request.

---

**Status**: Ready to implement  
**Difficulty**: Medium  
**Time to Fix**: 15-20 minutes  
**Impact**: High (fixes all security-related chatbot errors)
