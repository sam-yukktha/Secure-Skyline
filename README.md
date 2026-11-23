# Secure and Efficient Skyline Queries on Encrypted Data

This repository is a repaired PHP 8/MySQL final-year project. It demonstrates encrypted medical-report storage, owner-controlled report access, server-side authenticated decryption, multi-criteria skyline queries, rule-based health summaries, performance filtering, and an educational K-Means view.

> This implementation is an educational prototype demonstrating encrypted storage, controlled access, and skyline computation in an authorized processing environment. It does **not** perform arbitrary comparisons directly over AES ciphertext.

## Requirements

- Windows 10/11
- XAMPP with Apache and MySQL/MariaDB
- PHP 8.1 or later
- PHP extensions: `pdo_mysql`, `openssl`, `fileinfo`, and `session`
- Chrome or Edge

No Composer, Node.js, or external chart CDN is required.

## Installation

1. Copy this project folder to:

   `C:\xampp\htdocs\skyline`

2. Start **Apache** and **MySQL** in the XAMPP Control Panel.

3. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).

4. Select **Import**, choose `C:\xampp\htdocs\skyline\database\skyline.sql`, and run the import. The script creates the `skyline` database, all tables, indexes, relationships, demo accounts, 20 synthetic medical records, and an encrypted sample report.

5. Open [http://localhost/skyline/](http://localhost/skyline/).

The defaults in `config/app.php` match a normal XAMPP setup:

- Host: `localhost`
- Port: `3306`
- Database: `skyline`
- Username: `root`
- Password: empty

Change configuration in that one file, or use `SKYLINE_DB_HOST`, `SKYLINE_DB_PORT`, `SKYLINE_DB_NAME`, `SKYLINE_DB_USER`, `SKYLINE_DB_PASS`, `SKYLINE_BASE_PATH`, and `SKYLINE_ENCRYPTION_KEY` environment variables.

## Demo Accounts

Data Owner:

- Username: `owner`
- Password: `owner123`

Data User / Doctor:

- Username: `doctor`
- Password: `doctor123`

Passwords in the database are one-way `password_hash()` values, not plaintext.

## Main Workflow

1. The Data Owner signs in and adds medical values plus a PDF/TXT/PNG/JPG report.
2. PHP validates extension, MIME type, upload error, and the 5 MB limit.
3. The report bytes are encrypted with AES-256-GCM using a fresh random nonce and authentication tag, then stored in MySQL. The original upload is not placed in a public web folder.
4. The Data User searches report name/keyword metadata.
5. The Data User sends an access request. Its initial status is `pending`.
6. The owner approves or rejects it. Approval creates a random authorization token whose verification form is stored as a password hash; the display copy is itself encrypted at rest.
7. The approved user enters the token. The server validates account, request ownership, approval state, CSRF token, and authorization token before decrypting the report internally.

The user-facing authorization token is never the AES master encryption key.

## Skyline Demo

1. Sign in as `doctor`.
2. Open **Skyline Query**.
3. Select **Blood Pressure** and **Blood Sugar**.
4. Set both directions to **Minimize** and run the query.

With an untouched seed database, the expected result is:

- Records analyzed: 20
- Dominated records: 13
- Skyline records: 7
- Patient IDs/names: 1 / Demo Patient 01, 2 / Demo Patient 02, 3 / Demo Patient 03, 9 / Demo Patient 09, 10 / Demo Patient 10, 16 / Demo Patient 16, and 20 / Demo Patient 20

The PHP implementation checks pairwise dominance using a reusable function in `includes/skyline.php`.

## Performance and Analysis Modules

- **Performance Report** uses a strict attribute/operator whitelist and bound numeric values. Example: Blood Pressure, Greater Than, 140 returns 7 of the 20 seeded records.
- **Rule-Based Demonstration** is accessible for an owner's own record or a Data User's approved record. It uses transparent thresholds and is not an AI diagnosis.
- **K-Means Clustering Demonstration** retains the original educational concept, now with deterministic initialization, empty-data protection, cluster-count validation, and a 100-iteration limit.
- The result-count chart is local HTML/CSS and has no dead remote JavaScript dependency.

## Security Design

- PDO with prepared statements for all user-supplied values
- `password_hash()` / `password_verify()` credentials
- Regenerated session identifier after login
- Role guards on every protected route
- CSRF validation on state-changing forms
- Context-authenticated AES-256-GCM encryption
- Authorization token separated from the master encryption key
- Escaped HTML output
- Whitelisted analytics columns/operators
- MIME/extension/size upload validation and random server-side storage names
- Security response headers
- Legacy `upload/` is denied by Apache through `.htaccess`

For a production deployment, set a long random `SKYLINE_ENCRYPTION_KEY` outside source control, serve only through HTTPS, move secrets into the host environment, add account lockout/rate limiting, and use formal key rotation. Changing the encryption key makes existing encrypted data unreadable.

## Automated Verification

With XAMPP MySQL running and the SQL imported:

```powershell
cd C:\xampp\htdocs\skyline
C:\xampp\php\php.exe tests\run.php
```

The script uses a transaction and rolls its temporary test data back. It verifies database connectivity, registrations, password checks, medical record/report creation, encryption, search, request/approval, token verification, decryption, incorrect-token rejection, skyline logic/results, performance filtering, and route authorization.

Check PHP syntax recursively:

```powershell
$php = 'C:\xampp\php\php.exe'
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object { & $php -l $_.FullName }
```

See `MANUAL_TESTING.md` for browser test cases.

## Troubleshooting

### Apache port conflict

If Apache cannot start, stop IIS/another web server or change Apache's listen port in XAMPP. With port 8080, use `http://localhost:8080/skyline/`.

### MySQL connection error

Confirm MySQL is running. Check the host, port, database, username, and password in `config/app.php`. A nonstandard XAMPP MySQL port must be updated there.

### Missing database or table

Re-import `database/skyline.sql` in phpMyAdmin. The import is intentionally repeatable and rebuilds the six application tables.

### Upload fails

Confirm the file is PDF, TXT, PNG, JPG/JPEG and no larger than 5 MB. In `php.ini`, ensure `file_uploads=On`, and set `upload_max_filesize` and `post_max_size` to at least `6M`. Restart Apache after changing PHP settings.

### Missing PHP extension

In `C:\xampp\php\php.ini`, enable `extension=pdo_mysql`, `extension=openssl`, and `extension=fileinfo`, then restart Apache.

### Blank page or HTTP 500

Read `C:\xampp\apache\logs\error.log`. Run the recursive PHP syntax command above. During local debugging only, temporarily enable PHP error logging; do not expose stack traces in a shared environment.

### Seed report will not decrypt

The seed ciphertext uses the default development secret in `config/app.php`. If `SKYLINE_ENCRYPTION_KEY` was changed before importing, upload a new report under that key or restore the documented development key for the demo. Never use the default key in production.

## Important Limitations

- AES-GCM protects report file content at rest. Searchable report metadata and numerical demo attributes are stored in queryable form for this prototype.
- Skyline, filtering, and clustering execute in the trusted PHP/MySQL environment—not directly over ciphertext and not with homomorphic encryption, secure multiparty computation, or searchable-encryption proofs.
- Thresholds are simplified educational examples and not clinical guidance.
- This local demo has no email/token delivery service, audit export, key rotation service, MFA, or production-grade identity verification.
- Files retained in `upload/` are legacy project artifacts and are blocked from HTTP access; the repaired workflow does not use them.
