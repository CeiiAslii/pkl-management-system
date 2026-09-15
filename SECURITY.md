# Security Policy

## Supported Status

E-PKL is under active development. Security fixes are applied to the current `main` branch; older snapshots are not maintained as supported releases.

## Reporting a Vulnerability

Please report suspected vulnerabilities privately through GitHub Security Advisories for this repository. Include the affected area, impact, and a minimal reproduction that does not contain real credentials or personal data. Do not open a public issue with exploit details, credentials, tokens, student information, or private files.

Allow the maintainer reasonable time to investigate and publish a fix before public disclosure. Never test against systems or accounts you do not own or have permission to assess.

## Security Controls

The project uses server-side role authorization, policies and ownership checks against IDOR, CSRF protection, authentication rate limiting, Laravel password hashing, session invalidation after sensitive account changes, private upload storage, MIME/size/dimension validation, security headers, protected administrator safeguards, and automated security tests.

These controls reduce risk but do not guarantee that the application is free of vulnerabilities. Deployers remain responsible for secure environment configuration, HTTPS, database permissions, queue supervision, dependency updates, backups, and protection of secrets and personal data.
