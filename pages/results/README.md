# Results Management System - User Guide

## Overview

The Results Management System allows admins to manage student academic results through CSV upload, manual entry, or inline editing. Results are organized by class, session, and term with a maximum of 15 subjects per class.

## Features

### 1. **CSV Upload** (`upload.php`)

- Upload multiple student results at once
- Supports up to 15 subjects per upload
- Automatic student matching within selected class
- Score validation (0-100)
- Handles missing or invalid data gracefully
- Transaction-based processing for data integrity

**CSV Format:**

```
Student Name,Math,English,Science
John Doe,85,92,88
Jane Smith,90,88,95
```

### 2. **Template Download** (`download-template.php`)

- Download pre-formatted CSV templates with all students in a class
- Optionally select specific subjects to include
- Quick way to get started with CSV uploads

### 3. **Manual Entry** (`entry.php`)

- Enter results one student at a time
- Select class → session → term → student
- Enter scores for all active subjects
- Best for small batches or corrections

### 4. **View & Edit Results** (`view.php`)

- Table view of all results for a class/session/term
- Inline editing of scores
- Calculate student averages automatically
- Delete individual scores
- Quick overview of data completeness

### 5. **Results Dashboard** (`index.php`)

- Overview of all uploaded results
- Filter by class, session, and term
- Summary cards showing students and subjects
- Quick links to upload, download, view, and edit

## Database Structure

### Tables Created

**subjects**

```sql
- id (Primary Key)
- subject_name (Unique, Max 100 chars)
- subject_code (Optional)
- is_active (Default 1)
- created_at, updated_at
```

**student_results**

```sql
- id (Primary Key)
- student_id (FK → students)
- class_id (FK → classes)
- session_id (FK → sessions)
- term_id (FK → terms)
- subject_id (FK → subjects)
- score (Decimal 5,2 → 0-100)
- created_at, updated_at
- Unique constraint: (student_id, class_id, session_id, term_id, subject_id)
```

## Workflow

### Scenario 1: Bulk Upload via CSV

1. Go to Results → Upload CSV
2. Select Class, Session, Term
3. Download Template (optional) to see student names
4. Prepare your CSV with student names and scores
5. Upload the file
6. Review any warnings/errors
7. Results are automatically stored in database

### Scenario 2: Manual Entry

1. Go to Results → Enter Scores
2. Select Class → Session → Term → Student
3. Fill in scores for each subject
4. Click Save Scores

### Scenario 3: Editing Existing Results

1. Go to Results Dashboard
2. Filter by Class/Session/Term
3. Click "View & Edit Results"
4. Click on score cells to edit
5. Click ✓ to save or leave blank to delete

## Validation Rules

### Score Validation

- ✓ Decimal numbers (e.g., 85.5)
- ✓ Range: 0 to 100
- ✓ Scores > 100 are capped at 100
- ✓ Empty cells/rows are skipped
- ✓ 'N/A', 'Absent', or empty treated as no score

### Student Matching

- ✓ Searches by full name match
- ✓ Supports first + last name format
- ✓ Supports first + middle + last name format
- ✓ Case-insensitive matching
- ✗ Student must exist in selected class

### Subject Limits

- Maximum 15 subjects system-wide
- Subjects auto-created from CSV headers
- Subjects can be reused across uploads

## CSV Upload Error Handling

| Error                | Solution                                    |
| -------------------- | ------------------------------------------- |
| Student not found    | Verify student exists in selected class     |
| Invalid score format | Use decimal numbers only (e.g., 85 or 85.5) |
| Score out of range   | Scores must be 0-100                        |
| Invalid CSV format   | Ensure first column is "Student Name"       |
| Too many subjects    | Max 15 subjects allowed                     |
| File too large       | Max 5MB file size                           |

## Tips & Best Practices

1. **Use Template Download**
   - Always download template to see exact student names
   - Copy-paste names to avoid typos

2. **Batch Processing**
   - Use CSV upload for large classes (20+ students)
   - Use manual entry for quick corrections

3. **Data Quality**
   - Enter scores consistently (all decimals or all integers)
   - Avoid special characters in subject names
   - Verify students before uploading

4. **Editing**
   - Use View & Edit page for quick corrections
   - Leave score blank and save to delete entry
   - Averages calculate automatically

5. **Backups**
   - Download CSV copies after successful uploads
   - Use View & Edit to export data

## API Endpoints

### Upload Results

- **URL**: `upload.php`
- **Method**: `POST`
- **Parameters**: class_id, session_id, term_id, csv_file
- **Returns**: Success/error message with warnings

### Download Template

- **URL**: `download-template.php`
- **Method**: `GET`
- **Parameters**: class (optional), subjects[] (optional)
- **Returns**: CSV file download

### View Results

- **URL**: `view.php`
- **Method**: `GET`
- **Parameters**: class, session, term
- **Returns**: HTML table with editable results

## Security Features

- ✓ Admin-only access (role-based)
- ✓ Prepared statements (SQL injection prevention)
- ✓ Transaction support (data consistency)
- ✓ Input validation (type checking)
- ✓ File type validation (CSV only)
- ✓ File size limits (5MB max)

## Performance Notes

- Indexing on: student_id, class_id, session_id, term_id, subject_id
- Unique constraint prevents duplicate entries
- Foreign keys ensure referential integrity
- Bulk inserts optimized for CSV uploads

## Troubleshooting

### Issue: "Student not found"

- **Cause**: Student name doesn't match exactly or isn't in selected class
- **Solution**: Use Download Template to copy exact names

### Issue: "Maximum 15 subjects allowed"

- **Cause**: Trying to add more than 15 subjects
- **Solution**: Reduce number of subjects or upload in separate batches

### Issue: Scores not saving

- **Cause**: Invalid score format or out of range
- **Solution**: Ensure scores are 0-100 and in valid format

### Issue: File upload fails

- **Cause**: File too large or wrong format
- **Solution**: Use CSV format only, file < 5MB

## Contact & Support

For issues or feature requests, contact system administrator.
