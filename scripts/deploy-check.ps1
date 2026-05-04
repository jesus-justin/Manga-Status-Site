$ErrorActionPreference = 'Stop'

Write-Host 'Checking PHP syntax...'
Get-ChildItem -Path . -Recurse -Filter *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\' } |
  ForEach-Object { php -l $_.FullName | Out-Host }

Write-Host 'Checking required files...'
@(
  'health_check.php',
  'Dockerfile',
  'docker-compose.yml',
  '.github/workflows/php-ci.yml'
) | ForEach-Object {
  if (-not (Test-Path $_)) {
    throw "Missing required file: $_"
  }
}

Write-Host 'Deployment preflight passed.'
