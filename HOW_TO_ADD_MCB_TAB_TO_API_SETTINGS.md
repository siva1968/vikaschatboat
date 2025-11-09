# 🔧 HOW TO ADD MCB SETTINGS TAB TO API SETTINGS PAGE

**Date:** November 6, 2025  
**Goal:** Integrate MyClassBoard settings into existing API Settings page instead of separate menu  
**Difficulty:** Easy (3 simple code additions)

---

## ✅ OPTION 1: BEST APPROACH - Integrate into API Settings

The URL you're on (`http://localhost/demo/wp-admin/admin.php?page=edubot-api-settings`) is the **API Settings page**.

We can add a MyClassBoard tab here along with Facebook, Google, Email, SMS, WhatsApp, etc.

### Steps to Add MCB Tab

#### Step 1: Edit `class-api-settings-page.php`

**File:** `includes/admin/class-api-settings-page.php`

**Location:** Find the `render_page()` method around line 430

**Add MCB Tab to Navigation:**

Find this section (around line 480):
```php
<h2 class="nav-tab-wrapper">
    <a href="?page=edubot-api-settings&tab=facebook" 
       class="nav-tab <?php echo $active_tab === 'facebook' ? 'nav-tab-active' : ''; ?>">
        <span class="dashicons dashicons-facebook"></span> Facebook
    </a>
    <a href="?page=edubot-api-settings&tab=google" ...
    ...
</h2>
```

**Add this after the last tab:**
```php
<a href="?page=edubot-api-settings&tab=myclassboard" 
   class="nav-tab <?php echo $active_tab === 'myclassboard' ? 'nav-tab-active' : ''; ?>">
    <span class="dashicons dashicons-businessman"></span> MyClassBoard
</a>
```

---

#### Step 2: Add MCB Case to Switch Statement

**Location:** Same file, find the switch statement around line 445

Find:
```php
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'facebook';
...
switch ($active_tab) {
    case 'facebook':
        $this->render_facebook_settings();
        break;
    case 'google':
        $this->render_google_settings();
        break;
    ...
}
```

**Add this case before the closing brace:**
```php
    case 'myclassboard':
        $this->render_mcb_settings();
        break;
```

---

#### Step 3: Register MCB Settings

**Location:** Same file, find `register_settings()` method around line 70

**Add this after WhatsApp settings (around line 340):**

```php
// MyClassBoard settings
register_setting(
    'edubot_api_settings',
    'edubot_mcb_enabled',
    [
        'type' => 'boolean',
        'sanitize_callback' => function($value) { return $value ? 1 : 0; },
        'default' => 0,
    ]
);

register_setting(
    'edubot_api_settings',
    'edubot_mcb_org_id',
    [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '21',
    ]
);

register_setting(
    'edubot_api_settings',
    'edubot_mcb_branch_id',
    [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '113',
    ]
);

register_setting(
    'edubot_api_settings',
    'edubot_mcb_api_timeout',
    [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 65,
    ]
);

register_setting(
    'edubot_api_settings',
    'edubot_mcb_retry_attempts',
    [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 3,
    ]
);

register_setting(
    'edubot_api_settings',
    'edubot_mcb_auto_sync',
    [
        'type' => 'boolean',
        'sanitize_callback' => function($value) { return $value ? 1 : 0; },
        'default' => 1,
    ]
);
```

---

#### Step 4: Add Render Method

**Add this method to the class (at the end, before the closing brace):**

```php
/**
 * Render MyClassBoard settings
 */
private function render_mcb_settings() {
    $mcb_enabled = get_option('edubot_mcb_enabled', 0);
    $org_id = get_option('edubot_mcb_org_id', '21');
    $branch_id = get_option('edubot_mcb_branch_id', '113');
    $timeout = get_option('edubot_mcb_api_timeout', 65);
    $retries = get_option('edubot_mcb_retry_attempts', 3);
    $auto_sync = get_option('edubot_mcb_auto_sync', 1);
    ?>
    
    <div class="edubot-settings-section">
        <h3>📱 MyClassBoard Integration Settings</h3>
        <p>Configure MyClassBoard CRM synchronization for enquiry management.</p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_enabled">Enable Integration</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="edubot_mcb_enabled" name="edubot_mcb_enabled" 
                               value="1" <?php checked($mcb_enabled, 1); ?> />
                        Enable MyClassBoard synchronization
                    </label>
                    <p class="description">Check to enable auto-sync of enquiries to MyClassBoard</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_org_id">Organization ID</label>
                </th>
                <td>
                    <input type="text" id="edubot_mcb_org_id" name="edubot_mcb_org_id" 
                           value="<?php echo esc_attr($org_id); ?>" class="regular-text" />
                    <p class="description">MyClassBoard Organization ID (Default: 21)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_branch_id">Branch ID</label>
                </th>
                <td>
                    <input type="text" id="edubot_mcb_branch_id" name="edubot_mcb_branch_id" 
                           value="<?php echo esc_attr($branch_id); ?>" class="regular-text" />
                    <p class="description">MyClassBoard Branch ID (Default: 113)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_api_timeout">API Timeout (seconds)</label>
                </th>
                <td>
                    <input type="number" id="edubot_mcb_api_timeout" name="edubot_mcb_api_timeout" 
                           value="<?php echo esc_attr($timeout); ?>" min="10" max="300" class="small-text" />
                    <p class="description">How long to wait for API response (10-300 seconds)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_retry_attempts">Retry Attempts</label>
                </th>
                <td>
                    <input type="number" id="edubot_mcb_retry_attempts" name="edubot_mcb_retry_attempts" 
                           value="<?php echo esc_attr($retries); ?>" min="1" max="10" class="small-text" />
                    <p class="description">Number of retries on sync failure (1-10, default: 3)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="edubot_mcb_auto_sync">Auto-Sync on Creation</label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="edubot_mcb_auto_sync" name="edubot_mcb_auto_sync" 
                               value="1" <?php checked($auto_sync, 1); ?> />
                        Automatically sync enquiries when created
                    </label>
                    <p class="description">Uncheck to manually sync enquiries only</p>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="edubot-settings-section" style="background: #f0f7ff; border: 1px solid #b3d9ff; padding: 15px;">
        <h3>ℹ️ Information</h3>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>API Endpoint:</strong> https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails</li>
            <li><strong>Status:</strong> View sync logs and statistics in dedicated dashboard</li>
            <li><strong>Lead Source:</strong> Automatically mapped (Chatbot → 273, Website → 231, etc.)</li>
            <li><strong>Grade Mapping:</strong> Automatically converted to MyClassBoard class IDs</li>
            <li><strong>Audit Trail:</strong> All sync attempts logged with full details</li>
        </ul>
    </div>
    
    <?php
}
```

---

## 🚀 After Making Changes

### 1. Upload the modified file:
```
Copy: includes/admin/class-api-settings-page.php
To: D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\includes\admin\class-api-settings-page.php
```

### 2. Refresh WordPress Admin:
- Go to: `http://localhost/demo/wp-admin/`
- Go to: EduBot → API Settings
- You should now see a **"MyClassBoard"** tab!

### 3. Click the tab and configure:
- Organization ID: 21
- Branch ID: 113
- Enable Integration: ☑
- Click Save

---

## ✅ RESULT

You'll see:
```
API Settings Page
├── Facebook tab
├── Google tab
├── TikTok tab
├── LinkedIn tab
├── Email tab
├── SMS tab
├── WhatsApp tab
└── ✨ MyClassBoard tab (NEW!)
```

All settings are stored in the WordPress options table as:
- `edubot_mcb_enabled`
- `edubot_mcb_org_id`
- `edubot_mcb_branch_id`
- `edubot_mcb_api_timeout`
- `edubot_mcb_retry_attempts`
- `edubot_mcb_auto_sync`

---

## 📝 Summary

| Item | Details |
|------|---------|
| **Location** | API Settings page |
| **URL** | `http://localhost/demo/wp-admin/admin.php?page=edubot-api-settings&tab=myclassboard` |
| **File to Edit** | `includes/admin/class-api-settings-page.php` |
| **Changes Needed** | 4 (add tab, case, settings, render method) |
| **Time** | ~10 minutes |
| **Difficulty** | Easy |

---

**Ready?** Make these 4 changes and MCB settings will appear in the API Settings page! 🎉

