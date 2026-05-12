# OWASP Security Evidence

This document is the internal automated security evidence pack for Arteri. It is not a third-party penetration test certificate.

## Scope and Standard

- Scope: local/staging Arteri web application and its PHP/Node dependencies.
- Standard: OWASP Top 10 2021 mapped to OWASP ASVS 5.0.0 Level 1 and OWASP WSTG test techniques.
- Evidence target: automated regression tests, dependency audit output, and OWASP ZAP baseline scan.
- Claim wording: "tested against OWASP Top 10 with documented automated evidence."

## Commands

```powershell
npm run test:security:e2e
vendor\bin\phpunit tests\app\Security\OwaspSecurityTest.php --testdox
composer audit
npm audit --audit-level=moderate
npm run audit:zap
```

ZAP baseline expects the app to be reachable locally. The script defaults to `http://host.docker.internal:8081` from inside Docker when `E2E_BASE_URL` is not set.

## Latest Local Run

Date: 2026-05-12, Asia/Bangkok.

| Evidence | Result |
| --- | --- |
| `npm run test:security:e2e -- --output=C:\tmp\arteri-pw-security` | Pass: 5 tests passed on Chromium. |
| `vendor\bin\phpunit tests\app\Security\OwaspSecurityTest.php --testdox` | Pass: 8 tests, 26 assertions. |
| `npm audit --audit-level=moderate` | Pass: 0 vulnerabilities found. |
| `composer audit` | Pass: no security vulnerability advisories found. |
| `npm run audit:zap` | Not completed locally: Docker CLI exists, but Docker Desktop daemon was not running (`dockerDesktopLinuxEngine` pipe missing). Run again when Docker is active to generate `reports/security/zap-baseline.*`. |

## OWASP Top 10 Status

| OWASP 2021 | Status | Evidence |
| --- | --- | --- |
| A01 Broken Access Control | Pass | Playwright verifies auth redirects, user/admin route boundaries, classification IDOR for direct archive detail and files. PHPUnit checks path traversal rejection. |
| A02 Cryptographic Failures | Risk Accepted | PHPUnit verifies seeded passwords are bcrypt hashes and cookie baseline is `HttpOnly` + `SameSite=Lax`. Production HTTPS and secure-cookie flags must be enabled in deployment config. |
| A03 Injection | Pass | Playwright covers SQL-looking login/search payloads and stored XSS rendering. PHPUnit verifies server-rendered archive detail escapes stored XSS. |
| A04 Insecure Design | Pass | Existing auth tests cover login throttling, password policy, safe redirect handling, and role boundary behavior. |
| A05 Security Misconfiguration | Pass | Global secure headers are enabled. Playwright asserts security headers and CSRF rejection for tokenless POST. Production debug mode must stay disabled. |
| A06 Vulnerable Components | Pass | Latest local `composer audit` found no advisories and `npm audit --audit-level=moderate` found 0 vulnerabilities. Re-run for each release. |
| A07 Identification and Authentication Failures | Pass | Playwright verifies logout invalidates session. PHPUnit verifies bcrypt passwords and safe login redirect. Existing tests cover login success/failure logging. |
| A08 Software and Data Integrity Failures | Pass | Existing upload/import tests cover allowed formats, unsupported files, and damaged XLSX. Export now writes user-controlled spreadsheet cells explicitly as strings to reduce formula injection risk. |
| A09 Security Logging and Monitoring Failures | Pass | Existing controller tests cover login, logout, CRUD, import, and audit log visibility. PHPUnit verifies submitted passwords are not persisted in audit log details. |
| A10 SSRF | Not Applicable | No user-controlled outbound HTTP client sink is present in application code; PHPUnit inventory test scans for common outbound sink APIs. Add dedicated SSRF tests if future features accept URLs or call external services. |

## Release Gate

- `npm run test:security:e2e` passes.
- `vendor\bin\phpunit tests\app\Security\OwaspSecurityTest.php --testdox` passes.
- `composer audit` has no unresolved high/critical advisory.
- `npm audit --audit-level=moderate` has no unresolved high/critical advisory.
- ZAP baseline findings are reviewed and documented as fixed or risk accepted.

## Production Checklist

- Set `CI_ENVIRONMENT=production`.
- Serve only over HTTPS.
- Set secure cookies for HTTPS deployments.
- Keep default `admin/admin` and `user/user` only for local seed/testing; require credential rotation before real use.
- Store generated reports under `reports/security/` and attach them to release notes.
