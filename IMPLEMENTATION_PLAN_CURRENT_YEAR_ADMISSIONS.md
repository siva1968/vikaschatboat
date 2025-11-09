# 📋 Implementation Plan: Current Year & Next Year Admissions Support

**Date:** November 8, 2025  
**Priority:** Important Feature Gap  
**Status:** Awaiting Approval  

---

## 🎯 Problem Statement

**Current State:**
- System captures admissions ONLY for **next academic year** (2026-27)
- Parents interested in current year admission (2025-26) are NOT supported
- Hardcoded messages always mention "AY 2026-27"
- Settings exist but admission form doesn't properly utilize them

**User Request:**
> "Parents may look for admission in current year also. We are capturing only admission for next academic year."

**Impact:**
- 🔴 Missing potential admissions from parents seeking immediate enrollment
- 🔴 Unable to capture current year admission inquiries
- 🔴 Settings UI shows options but backend doesn't enforce them
- 🔴 All hardcoded year references (2026-27) prevent flexibility

---

## ✅ What Already Exists (Good Foundation)

### 1. Admin Settings - Admission Period
**Location:** `admin/views/school-settings.php` (Lines 664-670)

```php
<input type="radio" name="edubot_admission_period" value="current" />
Current Academic Year Only

<input type="radio" name="edubot_admission_period" value="next" />
Next Academic Year Only

<input type="radio" name="edubot_admission_period" value="both" />
Both Current and Next Year
```

**Status:** ✅ UI EXISTS but not fully utilized

### 2. Backend Logic - Period Filtering
**Location:** `class-school-config.php` (Lines 548-563)

```php
public function get_available_academic_years() {
    $admission_period = get_option('edubot_admission_period', 'next');
    
    switch ($admission_period) {
        case 'current':
            return array($years['current']);  // ✅ Supports current
        case 'next':
            return array($years['next']);     // ✅ Supports next
        case 'both':
            return $available_years;         // ✅ Supports both
    }
}
```

**Status:** ✅ Logic EXISTS but not fully used

### 3. Form Field - Academic Year Selection
**Location:** `class-edubot-shortcode.php` (Lines 746-800)

```php
<select id="academic_year" name="academic_year" required>
    <?php foreach ($year_options as $value => $label): ?>
        <option value="<?php echo esc_attr($value); ?>" 
                <?php selected($default_year, $value); ?>>
            <?php echo esc_html($label); ?>
        </option>
    <?php endforeach; ?>
</select>
```

**Status:** ✅ Form field EXISTS

### 4. Database Table - Applications
**Location:** `wp_edubot_applications` table

Columns already include: `academic_year` (VARCHAR 20)

**Status:** ✅ Column EXISTS

---

## ❌ What's NOT Working

### 1. Hardcoded Welcome Messages
**Location:** `class-edubot-shortcode.php`

**Lines 1186-1188:**
```php
return array(
    'response' => "Hello! **Welcome to {$school_name}.**\n\n" .
                   "We are currently accepting applications for **AY 2026–27**.\n\n" .  // ❌ HARDCODED!
```

**Lines 1884-1884:**
```php
if (empty($collected_data['academic_year'])) {
    $academic_year = '2026-27';  // ❌ HARDCODED DEFAULT!
```

**Lines 2104-2104:**
```php
$academic_year = '2026-27';  // ❌ HARDCODED DEFAULT!
```

### 2. Missing Academic Year in Chatbot Response
- Chatbot doesn't ask parent to select academic year
- Chatbot auto-fills with hardcoded 2026-27
- No option to indicate current year interest

### 3. Inconsistent Form vs Chatbot Experience
- Form: Shows academic year dropdown based on admin settings ✅
- Chatbot: Hardcoded year, no selection ❌

---

## 🛠️ Implementation Plan

### Phase 1: Dynamic Welcome Messages (HIGH IMPACT)
**Files to Modify:** 1  
**Complexity:** Low  
**Estimated Time:** 15 minutes

#### Change 1.1: Update Admission Start Message
**File:** `includes/class-edubot-shortcode.php`  
**Line:** ~1186

**Current:**
```php
"We are currently accepting applications for **AY 2026–27**.\n\n" .
```

**Proposed:**
```php
// Get available years for dynamic message
$available_years = $school_config->get_available_academic_years();
$years_text = implode(' & ', $available_years);

"We are currently accepting applications for **AY {$years_text}**.\n\n" .
```

**Impact:** Message updates based on admin settings

---

### Phase 2: Smart Academic Year Selection in Chatbot (HIGH IMPACT)
**Files to Modify:** 1  
**Complexity:** Medium  
**Estimated Time:** 30 minutes

#### Change 2.1: Add Academic Year Selection to Chatbot Flow
**File:** `includes/class-edubot-shortcode.php`  
**Location:** After grade/board collection (around line ~2100)

**Current Logic:**
- Collects: Student name → Contact info → Grade → Board → DOB

**Proposed Logic:**
- Collects: Student name → Contact info → Grade → Board → **[NEW] Academic Year Selection** → DOB

**Implementation Approach:**

```php
// STEP: Collect Academic Year (NEW)
case 'academic_year':
    $available_years = $school_config->get_available_academic_years();
    
    if (count($available_years) === 1) {
        // Auto-select if only one option
        $this->update_conversation_data($session_id, 'academic_year', $available_years[0]);
        // Skip to next step
    } else {
        // Present choice to parent
        $year_options = implode("\n", array_map(function($year, $idx) {
            return "• " . ($idx + 1) . ": " . $year;
        }, $available_years, array_keys($available_years)));
        
        return "📚 **Select Admission Year:**\n\n" . 
               $year_options . "\n\n" .
               "Reply with the number (1, 2, etc.)";
    }
```

**Impact:** Parents can select their preferred year

---

### Phase 3: Remove Hardcoded Year Defaults (CLEANUP)
**Files to Modify:** 1  
**Complexity:** Low  
**Estimated Time:** 10 minutes

#### Change 3.1: Replace Hardcoded '2026-27' with Dynamic Values
**File:** `includes/class-edubot-shortcode.php`

**Locations to Change:**
- Line 1900: `$academic_year = '2026-27';` → Use `get_default_academic_year()`
- Line 2104: `$academic_year = '2026-27';` → Use `get_default_academic_year()`
- Line 2743: `'academic_year' => $collected_data['academic_year'] ?? '2026-27'` → Use default

**Change:**
```php
// OLD
$academic_year = '2026-27';

// NEW
$academic_year = $school_config->get_default_academic_year();
```

**Impact:** Defaults respect admin settings

---

### Phase 4: WhatsApp Template Messages (MEDIUM IMPACT)
**Files to Modify:** 1  
**Complexity:** Low  
**Estimated Time:** 15 minutes

#### Change 4.1: Update WhatsApp Template Initial Message
**File:** `includes/class-edubot-workflow-manager.php`

**Current:** Hardcoded year in template

**Proposed:** Dynamic year based on available admissions

```php
$available_years = $this->school_config->get_available_academic_years();
$years_display = implode(' and ', $available_years);

// Use in template
"We are accepting admissions for AY {$years_display}"
```

**Impact:** WhatsApp messages show correct years

---

### Phase 5: Email Notifications (INCLUDED)
**Files:** Already handles academic_year correctly via `{academic_year}` placeholder

**Status:** ✅ No changes needed - already working

---

### Phase 6: Form Submission Validation (IMPORTANT)
**Files to Modify:** 1  
**Complexity:** Low  
**Estimated Time:** 10 minutes

#### Change 6.1: Validate Selected Academic Year
**File:** `includes/class-edubot-shortcode.php` (Lines 3627)

**Current:**
```php
if (!empty($academic_year) && !$school_config->is_valid_academic_year($academic_year)) {
    return array('error' => 'Invalid academic year selected');
}
```

**Status:** ✅ Already implemented - Just ensure it's always enforced

---

## 📊 Implementation Summary

| Phase | Change | File | Lines | Complexity | Time |
|-------|--------|------|-------|-----------|------|
| 1 | Dynamic welcome message | class-edubot-shortcode.php | ~1186 | Low | 15 min |
| 2 | Academic year chatbot selection | class-edubot-shortcode.php | ~2100 | Medium | 30 min |
| 3 | Remove hardcoded defaults | class-edubot-shortcode.php | 1900, 2104, 2743 | Low | 10 min |
| 4 | WhatsApp template messages | class-edubot-workflow-manager.php | TBD | Low | 15 min |
| 5 | Email notifications | (No changes) | N/A | N/A | N/A |
| 6 | Form validation | class-edubot-shortcode.php | 3627 | Low | 10 min |
| **TOTAL** | | | | | **80 min** |

---

## 🎁 Added Benefits

### 1. Flexibility
- Admissions can be for current, next, or both years
- School can change settings without code changes

### 2. User Experience
- Parents see which years are available
- No confusion about admission timeline
- Clear selection process

### 3. Data Capture
- Accurate academic year in applications
- Proper reporting by year
- Better analytics

### 4. Admin Control
- Settings in UI work as intended
- No hardcoded values
- Easy to adjust

---

## 🔄 User Flow - AFTER Implementation

### Scenario 1: Both Years Open (Admin Setting)
```
Bot: "Hello! We are accepting AY 2025-26 & 2026-27"
     [Collect name, contact, grade, board]
Bot: "Select admission year:
      • 1: 2025-26
      • 2: 2026-27"
Parent: "2"
Bot: "Great! Let me collect DOB now..."
     [Continue with DOB, creates application for 2026-27]
```

### Scenario 2: Only Current Year (Admin Setting)
```
Bot: "Hello! We are accepting AY 2025-26"
     [Collect name, contact, grade, board]
Bot: "Admission year: 2025-26 (auto-selected)"
     [Continue with DOB, creates application for 2025-26]
```

### Scenario 3: Form Submission (Multiple Years Available)
```
Parent fills form:
  Name: Sujay
  Grade: 5
  Board: CBSE
  Admission Year: [Dropdown showing 2025-26 & 2026-27]
  Parent select: 2025-26
  → Application created for 2025-26
```

---

## ✅ Approval Checklist

Before implementation, please confirm:

- [ ] **Understanding:** The plan to support both current and next year admissions is clear
- [ ] **Scope:** All identified hardcoded values should be replaced with dynamic ones
- [ ] **Priority:** Admin settings should drive all year-related logic
- [ ] **UX:** Parents should have clear selection when multiple years are available
- [ ] **Admin:** Admin "Admission Open For" setting should actually control what's available
- [ ] **Forms:** Both chatbot and web form should have consistent behavior
- [ ] **Validation:** Only valid years (per admin settings) should be accepted

---

## 🚀 Recommendation

**Recommend proceeding with full implementation** because:

1. ✅ Admin settings already exist (foundation ready)
2. ✅ Database already supports multiple years
3. ✅ Backend filtering logic already exists
4. ✅ Low risk changes (mostly configuration-based)
5. ✅ High impact for user experience
6. ✅ Prevents missing admissions from current year seekers
7. ✅ Makes admin settings meaningful

---

## 📝 Questions Before Approval?

1. Should current year be auto-selected when only one year is available?
2. Should we add analytics/reporting by admission year?
3. Any specific year preference for new deployments?
4. Should we notify parents of available admission years via first message?

---

**Please confirm approval to proceed with implementation.**
