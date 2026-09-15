# Changelog

Meaningful features, fixes, and security changes will be recorded in this file.

## Unreleased

### Added

- Multi-role Student / Teacher / Admin workflow
- Selfie and GPS attendance
- Daily reports with private photo lifecycle
- Leave and sick requests
- Teacher notes
- Telegram integration through queued jobs
- Per-student Excel recap
- Browser and security-focused automated testing

### Security

- Identifier and shared-IP authentication rate limiting
- Policy-based authorization and IDOR protection
- Private file access controls
- Session invalidation after sensitive account changes
- Upload MIME, size, and image-dimension validation
- Security response headers
- Centralized password policy hardening

### Fixed

- Stale sessions after role, status, or password changes
- Oversized image resource-exhaustion risk
- Report photo archival lifecycle
- Daily dashboard rollover behavior
