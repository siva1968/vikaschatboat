# Instructions to Diagnose Enquiry Creation Issue

## Step 1: Check Debug Log
Visit this link to view the debug log:
**http://localhost/demo/debug_log_viewer.php**

## Step 2: Submit the Form Again
Fill out the admission form in the chatbot with:
- Name: Prasad
- Phone: 9866133566
- Email: smasina@gmail.com
- Grade: Grade 5
- Board: CBSE
- Date of Birth: 16/10/2010

## Step 3: Check Error Message
After submission, you should see one of two things:
1. **✅ SUCCESS** - "Your Enquiry Number: ENQ2025..." 
   - This means data was saved successfully
   
2. **❌ ERROR** - "Error Submitting Your Enquiry: [error details]"
   - This tells us exactly what went wrong

## Step 4: Check Debug Log Again
After submission, refresh the debug log viewer to see new EduBot entries.

## What to Look For

If submission failed, look for:
- `EduBot ERROR:` entries
- `EduBot: Failed to save enquiry to database`
- `EduBot: Database insert failed`
- Any exceptions or SQL errors

## Database Check

The debug log viewer also shows:
- ✅/❌ Enquiries table status
- ✅/❌ Applications table status  
- Count of records in each table

## Files to Review

If you see an error, we need to check:
1. Error message displayed (tells us what failed)
2. Debug log entries (tells us why it failed)
3. Database status (confirms tables exist)

Please submit the form and share:
1. The error message you see (if any)
2. Screenshot of debug_log_viewer.php output
3. Any EduBot entries shown in the log
