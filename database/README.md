# SQL Files Location & Instructions

## 📁 Database Folder Structure

```
database/
├── fix_status_enum.sql ⭐ PRIMARY - RUN FIRST
├── add_interventions_table.sql (Optional - Run after primary)
├── add_interventions.sql (Old - ignore)
├── add_report_severity.sql (Old - ignore)
├── update_reports.sql (Old - ignore)
└── capstone_system (10).sql (Full DB backup)
```

---

## 🎯 Which Files to Import

### Required Files (Must Run in Order)

#### 1️⃣ PRIMARY FIX - `fix_status_enum.sql`
**Purpose:** Fixes the database so status updates persist

**What it does:**
- Adds 4 new status values to reports table
- Enables: under_investigation, referred_to_mswd, verified, closed

**Import in phpMyAdmin:**
```
1. Go to: http://localhost/phpmyadmin
2. Select DB: capstone_system
3. Click: Import tab
4. Choose: database/fix_status_enum.sql
5. Click: Go
```

**Verify it worked:**
- Green success message appears
- Table modified notification shown

---

#### 2️⃣ OPTIONAL - `add_interventions_table.sql`
**Purpose:** Create table for counseling/intervention tracking

**What it does:**
- Creates interventions table
- Enables intervention feature in the system
- Allows school staff to add counseling sessions

**Import in phpMyAdmin:**
```
1. Go to: http://localhost/phpmyadmin
2. Select DB: capstone_system
3. Click: Import tab
4. Choose: database/add_interventions_table.sql
5. Click: Go
```

**Only needed if:**
- You want to use the interventions feature
- You want to track counseling sessions

---

## ❌ Files to Ignore

These are old migration files - **Do NOT import**:
- ✗ `add_interventions.sql` (old version)
- ✗ `add_report_severity.sql` (old version)
- ✗ `update_reports.sql` (old version)

---

## 📊 Import Status

### Before Import
- ❌ Status updates send email but don't save to DB
- ❌ New statuses (verified, referred_to_mswd, etc.) rejected
- ❌ System appears to work but data doesn't persist

### After Import (Primary Fix Only)
- ✅ Status updates save to database
- ✅ All 9 statuses accepted
- ✅ System works correctly
- ⏳ Interventions still need optional fix

### After Import (All Files)
- ✅ Status updates save to database
- ✅ All 9 statuses accepted
- ✅ Interventions can be tracked
- ✅ System fully functional

---

## 🚀 Quick Start (Copy & Paste)

### Step 1: Open phpMyAdmin
```
http://localhost/phpmyadmin
```

### Step 2: Click "capstone_system" database

### Step 3: Click "Import" tab

### Step 4: Select File
Click "Choose File" and select:
```
c:\wamp64\www\CapstoneTracker\database\fix_status_enum.sql
```

### Step 5: Import
Click "Go" button and wait for success message

---

## 📝 Verification Checklist

After importing `fix_status_enum.sql`:

- [ ] Green success message appeared
- [ ] No error messages shown
- [ ] Can see "Table capstone_system.reports has been modified"

To manually verify:
1. In phpMyAdmin, click `reports` table
2. Click `Structure` tab
3. Find `status` field
4. Should show 9 enum values

---

## 🔄 Testing the Fix

After importing:

1. Log in as Admin
2. Go to: **All Reports**
3. Click a report
4. Change status to: **"Referred to MSWD"**
5. Click button to update
6. Wait for success message
7. **Check the reports list** - status should show new value ✅

### Before Fix Result
- Status dropdown shows change
- Reverts back to old value
- Email sent but not saved

### After Fix Result
- Status updates and stays
- Email sent AND saved
- Shows in list immediately ✅

---

## 💾 File Details

| File | Size | Type | Priority |
|------|------|------|----------|
| fix_status_enum.sql | ~0.5 KB | Migration | 🔴 CRITICAL |
| add_interventions_table.sql | ~1.2 KB | Migration | 🟡 OPTIONAL |
| add_interventions.sql | ~0.8 KB | Old | ⚫ IGNORE |
| add_report_severity.sql | ~0.6 KB | Old | ⚫ IGNORE |
| update_reports.sql | ~0.4 KB | Old | ⚫ IGNORE |

---

## 🆘 Troubleshooting

### Error: "Syntax error"
- File might be corrupted
- Try re-importing
- Check file opens in text editor properly

### Error: "Access denied"
- User doesn't have ALTER TABLE privilege
- Contact database admin
- May need root user privileges

### Error: "Unknown table"
- Wrong database selected
- Must be in `capstone_system`
- Check database name before import

### Status still not updating
- Make sure you imported `fix_status_enum.sql`
- Clear browser cache
- Log out and log back in
- Try on different report

---

## 📞 Support

All necessary files are in the `database/` folder:
- `fix_status_enum.sql` ← **Import this first**
- `add_interventions_table.sql` ← Import this second (optional)
- Documentation files for reference

