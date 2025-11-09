# 🎯 MCB LEAD SOURCE MAPPING - COMPLETE REFERENCE

**Date:** November 6, 2025  
**Version:** 2.0  
**Status:** ✅ NEEDS UPDATING

---

## 📊 COMPLETE LEAD SOURCE MAPPING

### All Available MCB Lead Sources

```php
$lead_sources = array(
    // Traditional Sources
    'News Paper'           => 84,
    'Hoardings'            => 85,
    'Leaflets'             => 86,
    
    // Referral Sources
    'Existing Parent'      => 232,
    'Friends'              => 92,
    'Referral'             => 92,  // Alias for Friends
    
    // Event & Walk-in
    'Events'               => 234,
    'Walkin'               => 250,
    'Walk In'              => 250,  // Alias
    
    // Web-based
    'Website'              => 231,
    'Email'                => 286,
    
    // Social Media
    'Facebook'             => 272,
    'Facebook Lead'        => 271,
    'Instagram'            => 268,
    'LinkedIn'             => 267,
    'Linkedin'             => 267,  // Alternate spelling
    'WhatsApp'             => 273,
    'Chat Bot'             => 273,
    
    // Google
    'Google Search'        => 269,
    'Google Display'       => 270,
    'Google Call Ads'      => 275,
    
    // Other
    'E-book'               => 274,
    'Ebook'                => 274,  // Alternate spelling
    'Organic'              => 280,
    'YouTube'              => 446,
    'News Letter'          => 447,
    'Newsletter'           => 447,  // Alternate spelling
    'Word of Mouth'        => 448,
    'Word of mouth'        => 448,  // Lowercase variant
    
    // Miscellaneous
    'Others'               => 233,
    'Default'              => 280,  // Fallback
);
```

---

## 🔍 COMPARISON: EduBot vs MCB vs Epistemo

### Your Three Systems

| EduBot | MCB ID | Epistemo | Type |
|--------|--------|----------|------|
| chatbot | 273 | Chat Bot | Digital |
| website | 231 | Website | Digital |
| facebook | 272 | Facebook | Social |
| google_search | 269 | Google Search | Digital |
| google_display | 270 | Google Display | Digital |
| instagram | 268 | Instagram | Social |
| linkedin | 267 | LinkedIn | Social |
| email | 286 | Email | Digital |
| referral | 92 | Friends | Referral |
| walkin | 250 | Walkin | Event |
| organic | 280 | Organic | Digital |
| — | 84 | News Paper | Traditional |
| — | 85 | Hoardings | Traditional |
| — | 86 | Leaflets | Traditional |
| — | 232 | Existing Parent | Referral |
| — | 234 | Events | Event |
| — | 271 | Facebook Lead | Social |
| — | 274 | Ebook | Digital |
| — | 275 | Google Call Ads | Digital |
| — | 446 | YouTube | Social |
| — | 447 | News Letter | Digital |
| — | 448 | Word of Mouth | Referral |
| — | 233 | Others | Misc |

---

## ❌ MISSING IN CURRENT MAPPING

Your current EduBot mapping is **missing these sources**:

| Source | MCB ID | Type | Priority |
|--------|--------|------|----------|
| News Paper | 84 | Traditional | Medium |
| Hoardings | 85 | Traditional | Medium |
| Existing Parent | 232 | Referral | High |
| Events | 234 | Event | High |
| Facebook Lead | 271 | Social | High |
| E-book | 274 | Digital | Medium |
| Google Call Ads | 275 | Digital | High |
| Leaflets | 86 | Traditional | Low |
| YouTube | 446 | Social | Medium |
| News Letter | 447 | Digital | Medium |
| Word of Mouth | 448 | Referral | Medium |
| Others | 233 | Misc | Low |

**Total Missing:** 12 sources

---

## ✅ COMPLETE MCB INTEGRATION MAPPING

### Recommended Full Mapping

```php
private function get_complete_lead_source_mapping() {
    return array(
        // Digital/Chatbot
        'chatbot'              => '273',
        'whatsapp'             => '273',
        
        // Website & Email
        'website'              => '231',
        'email'                => '286',
        
        // Search & Display
        'google_search'        => '269',
        'google_display'       => '270',
        'google_call_ads'      => '275',
        
        // Social Media
        'facebook'             => '272',
        'facebook_lead'        => '271',
        'instagram'            => '268',
        'linkedin'             => '267',
        'youtube'              => '446',
        
        // Referral
        'referral'             => '92',
        'friends'              => '92',
        'existing_parent'      => '232',
        'word_of_mouth'        => '448',
        
        // Event & Walk-in
        'events'               => '234',
        'walkin'               => '250',
        
        // Content
        'ebook'                => '274',
        'newsletter'           => '447',
        'leaflets'             => '86',
        
        // Traditional
        'newspaper'            => '84',
        'hoardings'            => '85',
        
        // Other
        'organic'              => '280',
        'others'               => '233',
        
        // Fallback
        'unknown'              => '280',  // Default to Organic
    );
}
```

---

## 🔄 MAPPING BY CATEGORY

### 🖥️ Digital Sources (7)
| Source | ID | EduBot Key |
|--------|----|----|
| Website | 231 | website |
| Email | 286 | email |
| Google Search | 269 | google_search |
| Google Display | 270 | google_display |
| Google Call Ads | 275 | google_call_ads |
| Chat Bot | 273 | chatbot |
| Newsletter | 447 | newsletter |

### 👥 Social Media (5)
| Source | ID | EduBot Key |
|--------|----|----|
| Facebook | 272 | facebook |
| Facebook Lead | 271 | facebook_lead |
| Instagram | 268 | instagram |
| LinkedIn | 267 | linkedin |
| YouTube | 446 | youtube |

### 🤝 Referral (4)
| Source | ID | EduBot Key |
|--------|----|----|
| Friends | 92 | referral |
| Existing Parent | 232 | existing_parent |
| Word of Mouth | 448 | word_of_mouth |
| Referral | 92 | referral |

### 📍 Events (2)
| Source | ID | EduBot Key |
|--------|----|----|
| Events | 234 | events |
| Walk In | 250 | walkin |

### 📰 Traditional (3)
| Source | ID | EduBot Key |
|--------|----|----|
| News Paper | 84 | newspaper |
| Hoardings | 85 | hoardings |
| Leaflets | 86 | leaflets |

### 📚 Content (1)
| Source | ID | EduBot Key |
|--------|----|----|
| E-book | 274 | ebook |

### 🔀 Other (2)
| Source | ID | EduBot Key |
|--------|----|----|
| Organic | 280 | organic |
| Others | 233 | others |

---

## 🛠️ IMPLEMENTATION

### Current Code (INCOMPLETE)
```php
private function get_default_lead_source_mapping() {
    return array(
        'chatbot'           => '273',
        'website'           => '231',
        'facebook'          => '272',
        'google_search'     => '269',
        'google_display'    => '270',
        'instagram'         => '268',
        'linkedin'          => '267',
        'whatsapp'          => '273',
        'referral'          => '92',
        'email'             => '286',
        'walkin'            => '250',
        'organic'           => '280',
    );
}
```

**Missing: 12 sources** ❌

---

### Updated Code (COMPLETE)
```php
/**
 * Get complete lead source mapping for MCB
 * 
 * All 24 MCB lead sources mapped to EduBot keys
 * Includes aliases for common variations
 * 
 * @return array Lead source mapping
 */
private function get_complete_lead_source_mapping() {
    return array(
        // ====== DIGITAL/CHATBOT ======
        'chatbot'              => '273',      // Chat Bot
        'whatsapp'             => '273',      // WhatsApp
        
        // ====== WEBSITE & EMAIL ======
        'website'              => '231',      // Website
        'email'                => '286',      // Email
        
        // ====== SEARCH & DISPLAY ======
        'google_search'        => '269',      // Google Search
        'google_display'       => '270',      // Google Display
        'google_call_ads'      => '275',      // Google Call Ads
        
        // ====== SOCIAL MEDIA ======
        'facebook'             => '272',      // Facebook
        'facebook_lead'        => '271',      // Facebook Lead
        'instagram'            => '268',      // Instagram
        'linkedin'             => '267',      // LinkedIn
        'youtube'              => '446',      // YouTube
        
        // ====== REFERRAL ======
        'referral'             => '92',       // Friends/Referral
        'friends'              => '92',       // Friends
        'existing_parent'      => '232',      // Existing Parent
        'word_of_mouth'        => '448',      // Word of Mouth
        
        // ====== EVENTS ======
        'events'               => '234',      // Events
        'walkin'               => '250',      // Walk In
        
        // ====== CONTENT ======
        'ebook'                => '274',      // E-book
        'newsletter'           => '447',      // News Letter
        'leaflets'             => '86',       // Leaflets
        
        // ====== TRADITIONAL ======
        'newspaper'            => '84',       // News Paper
        'hoardings'            => '85',       // Hoardings
        
        // ====== OTHER ======
        'organic'              => '280',      // Organic
        'others'               => '233',      // Others
        
        // ====== FALLBACK ======
        'unknown'              => '280',      // Default to Organic
        'default'              => '280',      // Default to Organic
    );
}
```

**Complete: 29 mappings** ✅

---

## 📱 Source Detection

### How EduBot Determines Source

```php
/**
 * Get lead source from enquiry
 * 
 * Checks in order:
 * 1. Explicit source field
 * 2. UTM source parameter
 * 3. Referrer analysis
 * 4. Default to 'chatbot'
 * 
 * @param array $enquiry Enquiry data
 * @return string Source key
 */
public function detect_enquiry_source( $enquiry ) {
    // 1. Explicit source
    if ( isset( $enquiry['source'] ) && ! empty( $enquiry['source'] ) ) {
        return strtolower( $enquiry['source'] );
    }
    
    // 2. UTM source
    if ( isset( $_GET['utm_source'] ) ) {
        return strtolower( $_GET['utm_source'] );
    }
    
    // 3. UTM medium (fallback)
    if ( isset( $_GET['utm_medium'] ) ) {
        $medium = strtolower( $_GET['utm_medium'] );
        $source_map = array(
            'organic' => 'organic',
            'cpc' => 'google_search',
            'email' => 'email',
            'social' => 'facebook',
            'referral' => 'referral',
        );
        return isset( $source_map[$medium] ) ? $source_map[$medium] : 'chatbot';
    }
    
    // 4. Default
    return 'chatbot';
}
```

---

## 🗺️ UTM Parameter Mapping

### Common UTM Parameters to Sources

```php
$utm_source_map = array(
    'google' => 'google_search',
    'facebook' => 'facebook',
    'instagram' => 'instagram',
    'linkedin' => 'linkedin',
    'youtube' => 'youtube',
    'newsletter' => 'newsletter',
    'email' => 'email',
    'referral' => 'referral',
    'organic' => 'organic',
    'direct' => 'organic',
    'website' => 'website',
    'chatbot' => 'chatbot',
);
```

---

## 📊 Data Sync Check

### Verify Correct Mapping

**When enquiry syncs to MCB, check:**

1. ✅ Lead source appears correctly in MCB
2. ✅ No "Other" (233) unless intentional
3. ✅ Correct ID matches EduBot source
4. ✅ All fields populated correctly
5. ✅ Student name and contact info present

**Example Sync Log Entry:**
```
Request:
{
  "OrganisationID": "21",
  "BranchID": "113",
  "StudentName": "John Doe",
  "QueryContactSourceID": "269",  ← Should be mapped correctly
  "LeadSource": "269",             ← Should match above
  "AcademicYearID": 17
}

Response:
{
  "success": true,
  "message": "Student details saved",
  "enquiryCode": "ENQ123456"
}
```

---

## ⚠️ Common Issues

### Issue #1: Source Not Recognized
**Problem:** Source appears as "Other" (233) in MCB  
**Solution:** Add source to mapping array

### Issue #2: Wrong ID Mapped
**Problem:** EduBot says "Website" but MCB shows different source  
**Solution:** Check mapping key and ID match

### Issue #3: Missing Sources in Settings
**Problem:** Can't select certain sources in settings  
**Solution:** Add to settings page options

### Issue #4: UTM Parameters Ignored
**Problem:** utm_source parameter not affecting source  
**Solution:** Verify UTM parsing code

---

## 🎯 ACTION ITEMS

### To Implement Complete Mapping:

1. ✅ Update `get_default_lead_source_mapping()` with all 29 mappings
2. ✅ Update MCB settings page to include all sources
3. ✅ Test each source mapping
4. ✅ Verify sync logs show correct IDs
5. ✅ Document in admin panel

---

## 📋 TESTING CHECKLIST

For each lead source:

- [ ] Newspaper (84) - Traditional
- [ ] Hoardings (85) - Traditional
- [ ] Leaflets (86) - Traditional
- [ ] Existing Parent (232) - Referral
- [ ] Events (234) - Event
- [ ] Facebook Lead (271) - Social
- [ ] E-book (274) - Content
- [ ] Google Call Ads (275) - Digital
- [ ] YouTube (446) - Social
- [ ] News Letter (447) - Digital
- [ ] Word of Mouth (448) - Referral
- [ ] Others (233) - Misc

**Test steps per source:**
1. Create enquiry with that source
2. Verify sync to MCB
3. Check MCB shows correct source
4. Confirm enquiry code returned
5. Note any errors

---

## 📞 SUPPORT

**Need to add more sources?**
1. Get source name and ID from MCB
2. Add to mapping array with appropriate key
3. Update settings page if needed
4. Test mapping

**Source ID not working?**
1. Verify ID is correct in MCB
2. Check mapping syntax
3. Test with manual enquiry
4. Review sync logs

---

## 📝 VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Nov 1 | Initial 12 sources |
| 2.0 | Nov 6 | **CURRENT - Complete 29 sources** |

**Status:** Ready for implementation ✅

