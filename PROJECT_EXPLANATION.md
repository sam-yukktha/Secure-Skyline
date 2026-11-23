# Project Explanation

## Project Title

**Secure and Efficient Skyline Queries on Encrypted Data**

## Problem

Medical-report applications must balance privacy and usefulness. A report owner wants to keep sensitive files protected, while a doctor or approved Data User needs to discover relevant reports and compare records across several attributes. A normal database can search and compare values, but a public plaintext report file can be downloaded by anyone who guesses its URL.

## Existing System

The recovered project was a small PHP medical-record demonstration. It contained Data Owner and Data User registration/login, health attributes, report upload, keyword search, access requests, threshold analysis, an old chart library, and partial K-Means code. It depended on removed `mysql_*` and `mcrypt_*` APIs, stored plaintext passwords, built SQL from user input, exposed uploads publicly, passed encryption keys through the workflow, lacked reliable authorization guards, and referenced missing/broken database fields and pages. It did not contain a working skyline algorithm.

## Proposed / Repaired System

The repaired application preserves those useful ideas and implements them with PHP 8, PDO, a normalized MySQL schema, authenticated encryption, role-based sessions, prepared statements, CSRF protection, controlled report access, real dashboard counts, a reusable skyline algorithm, repaired analytics, and reproducible demo data.

## Architecture

The browser sends requests to PHP pages running under Apache. Reusable code in `includes/` handles database access, authentication, CSRF, encryption, health rules, and skyline dominance. PDO talks to the `skyline` MySQL/MariaDB database. HTML/CSS renders the interface without a frontend framework or remote dependency.

The main flow is:

`Owner → Medical record + report → AES-GCM encrypted storage → User metadata search → Access request → Owner decision → Authorization token → Server validation → Server-side decryption`

## Data Owner

The Data Owner represents the patient/report owner. This role can register, log in, add medical measurements, upload a report, inspect its own records, maintain the legacy health-history questionnaire, see access requests, and approve or reject each request. Ownership is checked in SQL, so changing a numeric ID in a URL does not give access to another owner's report.

## Data User

The Data User represents a doctor or research user. This role can register, log in, search report names and keywords, request report access, inspect request state, use an approved authorization token, run skyline queries, generate performance filters, and explore educational clusters. It cannot obtain encrypted report content merely by knowing a filename or report ID.

## Encryption

New uploaded report bytes are encrypted centrally with OpenSSL AES-256-GCM. A 256-bit key is derived from the configured application secret, and every encryption gets a fresh 96-bit random nonce plus a 128-bit authentication tag. The stored `v1.` payload contains the nonce, tag, and ciphertext, but not the master secret. The record ID is authenticated as additional context, which prevents moving ciphertext to a different record context undetected.

The approval token is separate from the encryption key. Its verification form is stored with `password_hash()`, while a server-encrypted copy lets the approved demo user view the token. Decryption happens only after role, request ownership, approval state, CSRF, and token checks pass.

## Access Control

Sessions contain one authenticated identity and role. Login regenerates the session ID. Shared role guards protect owner/user pages. State-changing actions use POST plus CSRF tokens. Report authorization is evaluated on the server and cannot be bypassed by directly opening the old upload path.

## Skyline Query and Dominance

A skyline query finds non-dominated choices over several dimensions. Record A dominates record B when A is at least as good as B in every selected criterion and strictly better in at least one. For example, when lower blood pressure and lower blood sugar are preferred, a record with values `(120, 90)` dominates `(140, 120)`. Records `(120, 90)` and `(115, 105)` do not dominate each other because each is better in one dimension.

The user can choose Blood Pressure, Blood Sugar, Cholesterol, BMI, and Pulse Rate and select Minimize or Maximize independently. The system reports analyzed, dominated, and skyline counts plus the selected criteria and result rows.

This is not a claim that ordinary AES supports comparison over ciphertext. The numerical prototype data is queryable inside the trusted application/database environment. AES-encrypted report file bytes are not compared. A research-grade ciphertext-only skyline protocol would require specialized cryptography beyond this local demonstration.

## Health-Analysis Module

The old analysis page referenced undefined classes/columns and made unclear claims. It is now labeled **Rule-Based Demonstration**. It applies simple visible ranges to pulse, systolic blood pressure, blood sugar, cholesterol, and BMI, and includes the required warning that it is educational only and not medical diagnosis or advice.

## Performance Report

The Data User selects a supported numerical attribute, comparison (`>`, `=`, or `<`), and value. The application maps selections through strict whitelists, binds the value, calculates database counts, renders a local bar chart, and lists matching synthetic records. Unsupported disease-probability claims from the legacy version were removed because no validated disease model or defensible labels existed.

## K-Means

The repository contained an old two-dimensional K-Means library and broken calls using an incompatible API. The repaired page uses that project concept with deterministic seeds, numeric validation, 2–5 clusters, convergence detection, an iteration limit, and safe empty-cluster behavior. It is explicitly an educational grouping algorithm, not a trained medical model.

The legacy Naive Bayes helper remains available as a standalone library but is intentionally not part of the application workflow: its referenced training files and validated model were absent, so presenting its output as a diagnosis would be misleading. Its file-handling and empty-model runtime errors were repaired.

## Database

The reconstructed database contains:

- `data_owners` — owner profile and password hash
- `data_users` — doctor/user profile and password hash
- `medical_records` — synthetic numerical measurements and lifestyle flags
- `health_histories` — one questionnaire per owner
- `reports` — metadata and AES-GCM ciphertext
- `access_requests` — requester, state, token hash/encrypted token, and timestamps

Foreign keys enforce ownership and cascading cleanup. Unique and search indexes support authentication, requests, keywords, and skyline attributes.

## Security Improvements

The main improvements are PDO prepared statements, output escaping, password hashing, session regeneration, role checks, CSRF checks, authenticated encryption, server-side authorization, non-public encrypted report storage, upload MIME/extension/size validation, random stored names, whitelisted analytics inputs, security headers, and friendly error handling with technical logging.

## Limitations

- This is a single-server educational prototype, not a regulated clinical system.
- Numerical medical attributes and searchable metadata are queryable rather than encrypted with a special comparison scheme.
- The default encryption secret exists only for a reproducible local demo and must be replaced for real deployment.
- There is no MFA, rate limiting, email token delivery, formal audit trail, malware scanner, key rotation service, or healthcare compliance certification.
- The rule-based analysis and K-Means output are educational and are not medical predictions.

## Future Enhancements

Future work could add formal audit events, owner-to-user token delivery, expiring/revocable permissions, password reset/MFA, antivirus scanning, per-record data-encryption keys with rotation, encrypted backups, pagination, larger-data skyline optimizations, and research implementations using order-revealing encryption, homomorphic techniques, trusted execution environments, or secure multiparty computation—with the security trade-offs clearly documented and evaluated.
