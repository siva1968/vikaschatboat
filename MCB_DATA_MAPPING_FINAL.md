# MCB Data Mapping - Final Implementation

## Overview
The EduBot MCB Service now sends enquiry data to MyClassBoard with proper field mappings, board-dependent grade mapping, and lead source tracking.

## MCB Payload Structure

```json
{
    "OrganisationID": "21",
    "BranchID": "113",
    "StudentName": "String (required)",
    "ClassID": "Numeric ID (board-dependent)",
    "AcademicYearID": "Numeric ID",
    "QueryContactSourceID": "Lead Source ID",
    "FatherName": "String or NA",
    "FatherMobile": "Phone or NA",
    "FatherEmailID": "Email or NA",
    "MotherName": "String or NA",
    "MotherMobile": "Phone or NA",
    "DOB": "Date or NA",
    "Address1": "NA",
    "Remarks": "Chat EnquiryID: XXX | optional notes"
}
```

## Board-Dependent Grade Mapping

### CBSE Board
- Class I-XII: 943-944
- Class VI-X: 903-896
- Grade 1-12: Maps to CBSE system IDs

### CAIE/Cambridge Board
- Pre Nursery: 787
- Nursery: 273
- PP1: 274, PP2: 275
- Grade 1-12: 276-917
- Grade 11-12 Streams: MPC, MBipc, Bipc, Comm

## Lead Source Mapping

| Lead Source | MCB ID |
|---|---|
| News Paper | 84 |
| Hoardings | 85 |
| Leaflets | 86 |
| Facebook | 272 |
| Facebook Lead | 271 |
| Google Search | 269 |
| Google Display | 270 |
| Instagram | 268 |
| LinkedIn | 267 |
| Chat Bot | 273 |
| YouTube | 446 |
| Email | 286 |
| Website | 231 |
| Existing Parent | 232 |
| Others | 233 |
| Events | 234 |
| Walk In | 250 |
| Google Call Ads | 275 |
| Ebook | 274 |
| Newsletter | 447 |
| Word of Mouth | 448 |
| Friends | 92 |
| **Organic (default)** | **280** |

## Priority Order for Lead Source

1. **First Priority**: UTM Source from URL parameters (`utm_source`)
2. **Fallback**: Enquiry source field from form
3. **Default**: Organic (ID: 280) if no source found

## Academic Year Mapping

| Academic Year | MCB ID |
|---|---|
| 2020-21 | 11 |
| 2021-22 | 12 |
| 2022-23 | 13 |
| 2023-24 | 14 |
| 2024-25 | 15 |
| 2025-26 | 16 |
| 2026-27 | 17 |
| 2027-28 | 18 |

## Data Handling Rules

### If Data Not Available
- **String fields**: Send "NA"
- **Phone/Email**: Send "NA" if empty
- **Address**: Always "NA" (not captured)
- **Mother Name**: Always "NA" (not captured)
- **Date of Birth**: "NA" if not provided

### Remarks Field
Format: `Chat EnquiryID: {enquiry_number} | {optional_notes}`

Example: `Chat EnquiryID: ENQ20251593 | Student interested in Grade 5`

### Marketing Data
- **UTM Parameters**: Captured for lead source mapping only
- **Click IDs**: Captured for logging/debugging (gclid, fbclid)
- **Internal fields**: Marketing data stored for internal logging, not sent to MCB

## MCB Preview Tool

**Location**: `http://localhost/demo/mcb-preview.php?enquiry_id=38`

Shows exactly what data will be sent to MCB without making actual API calls.

## Testing Checklist

- [ ] Board-dependent grade mapping works (CBSE vs CAIE)
- [ ] UTM source is captured and mapped to lead source
- [ ] Default to Organic (280) when no UTM source
- [ ] Academic year ID is numeric, not text
- [ ] Remarks includes "Chat" prefix with EnquiryID
- [ ] Empty fields show "NA"
- [ ] Phone and Email are present
- [ ] ClassID matches the board system

## Files Updated

- `includes/class-edubot-mcb-service.php` - Main MCB Service with all mappings
- `mcb-preview.php` - Debug/preview tool to view MCB payload

## API Endpoint

**URL**: `https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails`

**Method**: POST

**Content-Type**: `application/json`

**Timeout**: 65 seconds
