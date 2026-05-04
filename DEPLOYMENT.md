# Deployment Guide

## Local development

1. Configure your `.env` file from `.env.example`.
2. Import `manga.sql` into MySQL.
3. Run the site through XAMPP or Apache PHP.

## Containerized deployment

```bash
docker compose up --build
```

The application will be available on `http://localhost:8080`.

## Validation

Run the local preflight checks before deployment:

```powershell
./scripts/deploy-check.ps1
```

## CI

GitHub Actions runs PHP linting on push and pull request events.
