$ErrorActionPreference = 'Stop'

Get-ChildItem -Path . -Recurse -Filter *.php |
  Where-Object { $_.FullName -notmatch '\\vendor\\' } |
  ForEach-Object {
    php -l $_.FullName
  }
