# Manual Testing Guide

Start Apache and MySQL, import `database/skyline.sql`, then open [http://localhost/skyline/](http://localhost/skyline/). Run these tests in order on a freshly imported database.

## TEST 01 — Data Owner Login

Input:

- Username: `owner`
- Password: `owner123`

Expected: the Data Owner dashboard opens and shows 1 Total Report, 0 Pending Requests, 0 Approved Requests, and 20 Medical Records.

## TEST 02 — Invalid Owner Login

Input: username `owner`, password `wrong-password`.

Expected: a friendly incorrect-credentials message appears; the dashboard does not open.

## TEST 03 — Data Owner Registration

Log out and register:

- Name: `Manual Test Owner`
- Email: `manual.owner@example.test`
- Phone: `9000000099`
- Username: `manualowner`
- Password: `Manual123!`

Expected: registration succeeds, password is stored as a hash, and the new account can log in. Log out and return to `owner` for later tests.

## TEST 04 — Upload Encrypted Report

On the owner dashboard enter:

- Patient label: `Manual QA Patient`
- Age: `45`
- Gender: `Other`
- Weight: `72`
- Height: `170`
- Heartbeat: `76`
- Pulse rate: `74`
- Blood pressure: `126`
- Blood sugar: `99`
- Cholesterol: `192`
- Report name: `Manual QA Blood Report`
- Report keyword: `manual-qa-keyword`
- File: `sample-data\demo-upload.txt`
- Lifestyle flags: leave all clear except **Exercises regularly**

Expected: success message says the report was encrypted; **My Reports** contains the new record. The dashboard count becomes 2 reports and 21 medical records.

## TEST 05 — Owner Decrypted View

In **My Reports**, click **View / Download** on `Manual QA Blood Report`.

Expected: the original text displays. No master encryption key appears in the URL or page.

## TEST 06 — Data User Login

Log out and open **Data User Login**:

- Username: `doctor`
- Password: `doctor123`

Expected: the Data User dashboard opens with live summary counts.

## TEST 07 — Data User Registration

Optionally register a second user with username `manualdoctor` and password `Manual123!`.

Expected: registration and subsequent login work; a duplicate username/email is rejected clearly.

## TEST 08 — Search Seed Report

As `doctor`, search for keyword `cardiology-demo`.

Expected: exactly `Synthetic Cardiology Summary` for `Demo Patient 01` appears. Only metadata is visible; file content is not displayed.

## TEST 09 — Access Request

Click **Request Access** for `Synthetic Cardiology Summary`.

Expected: **My Requests** shows the request as `Pending`; no authorization token or decrypted content is available.

## TEST 10 — Owner Approval

Log out, sign in as `owner`, open **Access Requests**, and approve the doctor's request.

Expected: status changes to `Approved`. A success message explains that an authorization token was generated.

## TEST 11 — Approved Token Access

Log out, sign in as `doctor`, and open **My Requests**. Copy the displayed `SKY-...` token into the authorization field and click **View Decrypted Report**.

Expected: a text report beginning with `SYNTHETIC DEMO REPORT` opens. This confirms server-side authorization and AES-GCM decryption.

## TEST 12 — Incorrect Token Denied

Return to **My Requests**, enter `SKY-WRONG-TOKEN`, and submit.

Expected: access is denied with a friendly error. No report content is shown.

## TEST 13 — Rejection

As `doctor`, request `Manual QA Blood Report`. As `owner`, reject it. Return as `doctor`.

Expected: status is `Rejected`, no token is shown, and the report cannot be opened. The search page offers **Request again**.

## TEST 14 — Role/Route Protection

While signed in as `doctor`, manually open `http://localhost/skyline/dashboard.php`.

Expected: redirect to the owner login with an authorization message. While signed in as `owner`, opening `doctordashboard.php` redirects to Data User login.

## TEST 15 — Authorized Medical Analysis

After TEST 10, as `doctor`, open `http://localhost/skyline/view_health.php` and analyze Demo Patient 01.

Expected: the **Rule-Based Demonstration** table appears with a prominent educational/not-medical-advice disclaimer. No AI diagnosis claim appears.

## TEST 16 — Skyline Exact Seed Result

Use a fresh database import (or ignore the extra manual upload), sign in as `doctor`, open **Skyline Query**, select:

- Blood Pressure — Minimize
- Blood Sugar — Minimize

Expected on the untouched 20-record seed data:

- Records analyzed: `20`
- Dominated records: `13`
- Skyline records: `7`
- Results: Demo Patient `01`, `02`, `03`, `09`, `10`, `16`, and `20`

If TEST 04 was already performed, totals/results may include the additional Manual QA Patient. Re-import the SQL for the exact fixture.

## TEST 17 — Skyline Validation

Select only one criterion and run.

Expected: `Select at least two skyline criteria.` No PHP warning or empty broken table appears.

## TEST 18 — Performance Report

On a fresh seed database choose:

- Attribute: Blood Pressure
- Condition: Greater Than
- Value: `140`

Expected: 20 total records, 7 matching, 13 non-matching, 35% match rate. Matching patients are 04, 09, 11, 13, 15, 17, and 19. Both chart bars render.

## TEST 19 — K-Means Educational Demo

Choose Blood Pressure for X, Blood Sugar for Y, and 3 clusters.

Expected: three understandable cluster cards appear, each with centroid, record count, and member records. Running against an empty/insufficient dataset produces a friendly message, not division by zero.

## TEST 20 — Upload Validation

Try uploading a `.php` file renamed only by its filename, or a file larger than 5 MB.

Expected: the upload is rejected based on allowed extension/MIME/size. No file is placed in the public `upload/` directory.

## TEST 21 — Tamper Detection

For a technical test copy the database first, alter a character in `reports.encrypted_content`, then try authorized access.

Expected: AES-GCM authentication fails safely and the UI reports a decryption failure without showing partial plaintext. Restore/re-import the database afterward.
