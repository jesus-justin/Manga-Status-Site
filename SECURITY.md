# Security Policy

## Supported versions

This repository tracks the current production branch of the Manga Status Site.

## Reporting a vulnerability

If you discover a security issue, report it privately to the repository maintainer and include:

- A short description of the issue
- Affected file or endpoint
- Steps to reproduce
- Any proof-of-concept details

## Operational guidance

- Keep `.env` out of source control.
- Rotate database credentials before production rollout.
- Run `scripts/deploy-check.ps1` before deployment.
