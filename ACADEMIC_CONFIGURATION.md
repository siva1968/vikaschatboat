# EduBot Pro - Flexible Academic Configuration

## Overview

The EduBot Pro plugin now includes a comprehensive academic configuration system that allows each school to customize their grade/class naming system, academic year cycles, and educational board requirements. This makes the plugin truly universal and adaptable to different educational systems worldwide.

## Key Features

### 🎓 Flexible Grade/Class Systems

**Predefined Systems Available:**
- **US K-12 System**: Kindergarten, Grade 1-12
- **Indian Class System**: Nursery, LKG, UKG, Class I-XII
- **UK Year System**: Reception, Year 1-13
- **Early Childhood Education**: Infant, Toddler, Preschool, Pre-K, Kindergarten
- **Custom System**: School can define their own grade names

**Custom Grade Configuration:**
- Schools can create completely custom grade/class naming
- Example: "Foundation Stage", "Junior School", "Senior Secondary"
- Unlimited flexibility for any educational structure

### 📅 Academic Year Management

**Predefined Academic Year Cycles:**
- **April - March**: Common in India, Japan, Thailand
- **August - June**: Common in USA, Canada
- **September - July**: Common in UK, Australia, New Zealand
- **January - December**: Common in Philippines, Brazil
- **February - November**: Common in South Africa

**Automatic Year Updates:**
- System automatically determines current academic year
- Based on school's configured academic cycle
- Can be manually overridden if needed

### 📚 Educational Board Integration (Optional)

**Supported Boards:**
- **CBSE** (Central Board of Secondary Education - India)
- **ICSE** (Indian Certificate of Secondary Education - India)
- **IB** (International Baccalaureate - International)
- **Cambridge International** (UK/International)
- **State Boards** (Various Indian states)
- **Custom Boards** (School-specific curricula)
- **No Board** (For schools without specific board affiliation)

**Board-Specific Features:**
- Automatic document requirements based on board
- Subject offerings specific to each board
- Board-specific application forms
- Compliance with board admission requirements

## Database Structure

### Enhanced School Configuration Table

```sql
wp_edubot_school_configs
├── academic_structure (JSON)
│   ├── grade_system: 'us_k12' | 'indian_class' | 'uk_year' | 'early_childhood' | 'custom'
│   ├── custom_grades: { grade_key: 'Grade Display Name' }
│   ├── admission_cycles: [
│   │   {
│   │     name: 'Regular Admission',
│   │     start_date: '2024-04-01',
│   │     end_date: '2024-06-30',
│   │     grades_available: ['grade_1', 'grade_2']
│   │   }
│   │ ]
│   └── academic_year_type: 'april_march'
├── board_settings (JSON)
│   ├── board_type: 'cbse' | 'icse' | 'ib' | 'cambridge' | 'state_board' | 'custom' | 'none'
│   ├── board_custom_name: 'Custom Board Name'
│   ├── requirements: ['birth_certificate', 'transfer_certificate']
│   ├── subjects_offered: ['mathematics', 'science', 'english']
│   └── board_specific_fields: {}
└── academic_year_settings (JSON)
    ├── academic_year_type: 'april_march'
    ├── auto_update_year: true
    └── custom_start_month: 4
```

## Admin Interface

### Academic Configuration Page

**Location:** EduBot Pro → Academic Configuration

**Sections:**
1. **Grade/Class System Configuration**
   - Select predefined system or create custom
   - Visual preview of selected system
   - Add/remove custom grades dynamically

2. **Academic Year Setup**
   - Choose academic calendar type
   - Automatic year calculation display
   - Custom start/end month configuration

3. **Educational Board (Optional)**
   - Select applicable board
   - Board-specific requirements display
   - Custom board name input
   - Subject selection per board

4. **Admission Cycles**
   - Define multiple admission windows
   - Set dates for each cycle
   - Grade availability per cycle

## Chatbot Integration

### Enhanced Conversation Flow

The chatbot now uses the academic configuration to:

1. **Display Current Academic Year**
   ```
   🎓 Great! Let's start your admission application.
   For Academic Year: 2024-2025
   Please select the grade/class you're applying for:
   ```

2. **Show Available Grades**
   - Dynamically loads grades based on school configuration
   - Displays grade names as configured by school
   - Validates selection against available options

3. **Board-Specific Requirements**
   ```
   ✅ Great! You've selected Class VI for admission.
   
   📋 Required documents for CBSE admission:
   • Birth Certificate
   • Transfer Certificate
   • Previous Year Mark Sheets
   • Conduct Certificate
   • Passport Size Photos
   • Aadhar Card (for Indian students)
   ```

## API Integration

### New API Methods

**Academic Configuration:**
```php
// Get available grades for admission
Edubot_Academic_Config::get_available_grades_for_admission($school_id);

// Get current academic year
Edubot_Academic_Config::get_current_academic_year($school_id);

// Get board requirements
Edubot_Academic_Config::get_school_board_config($school_id);

// Format grade display name
Edubot_Academic_Config::get_grade_display_name($school_id, $grade_key);
```

**Example Usage:**
```php
$school_id = 1;
$current_year = Edubot_Academic_Config::get_current_academic_year($school_id);
echo $current_year['display']; // "2024-2025"

$grades = Edubot_Academic_Config::get_available_grades_for_admission($school_id);
foreach ($grades as $key => $name) {
    echo $key . ': ' . $name; // "class_6: Class VI"
}
```

## Configuration Examples

### Example 1: Indian CBSE School
```json
{
  "academic_structure": {
    "grade_system": "indian_class",
    "academic_year_type": "april_march",
    "admission_cycles": [
      {
        "name": "Regular Admission",
        "start_date": "2024-12-01",
        "end_date": "2025-02-28",
        "grades_available": ["nursery", "lkg", "ukg", "class_1", "class_6", "class_9", "class_11"]
      }
    ]
  },
  "board_settings": {
    "board_type": "cbse",
    "requirements": ["birth_certificate", "transfer_certificate", "mark_sheets", "aadhar"],
    "subjects_offered": ["mathematics", "science", "social_science", "english", "hindi"]
  }
}
```

### Example 2: US International School
```json
{
  "academic_structure": {
    "grade_system": "us_k12",
    "academic_year_type": "august_june",
    "admission_cycles": [
      {
        "name": "Fall Admission",
        "start_date": "2024-11-01",
        "end_date": "2025-03-31",
        "grades_available": ["kindergarten", "grade_1", "grade_6", "grade_9"]
      },
      {
        "name": "Spring Admission",
        "start_date": "2024-10-01",
        "end_date": "2024-12-15",
        "grades_available": ["grade_1", "grade_2"]
      }
    ]
  },
  "board_settings": {
    "board_type": "ib",
    "requirements": ["academic_transcripts", "language_proficiency", "recommendation_letters"],
    "subjects_offered": ["language_literature", "mathematics", "sciences", "individuals_societies"]
  }
}
```

### Example 3: Custom Montessori School
```json
{
  "academic_structure": {
    "grade_system": "custom",
    "custom_grades": {
      "casa": "Casa (3-6 years)",
      "elementary_lower": "Lower Elementary (6-9 years)",
      "elementary_upper": "Upper Elementary (9-12 years)",
      "adolescent": "Adolescent Program (12-15 years)"
    },
    "academic_year_type": "september_july"
  },
  "board_settings": {
    "board_type": "custom",
    "board_custom_name": "Montessori Method",
    "requirements": ["previous_school_report", "child_observation", "parent_interview"]
  }
}
```

## Migration & Backward Compatibility

The system includes automatic migration for existing installations:

1. **Default Configuration**: Schools without academic configuration get sensible defaults
2. **Grade Migration**: Existing grade data is preserved and mapped to new system
3. **Academic Year Detection**: System detects current academic year based on default patterns

## Benefits for Schools

### 🌍 Universal Compatibility
- Works with any educational system worldwide
- No coding required for customization
- Instant setup for common systems

### 🔄 Automatic Updates
- Academic year automatically advances
- No manual intervention required
- Always shows current admission cycle

### 📋 Compliance Ready
- Board-specific requirements automatically applied
- Reduces manual configuration
- Ensures compliance with educational standards

### 🎯 Personalized Experience
- Students see familiar grade names
- Requirements match their educational background
- Culturally appropriate terminology

## Future Enhancements

- **Multi-language grade names** for international schools
- **Grade progression tracking** for returning students
- **Age-based grade suggestions** using birth date
- **Integration with school management systems**
- **Admission capacity management** per grade
- **Waiting list functionality** when grades are full

This flexible academic configuration system makes EduBot Pro truly universal, allowing any educational institution worldwide to implement the chatbot with their specific requirements and terminology.
