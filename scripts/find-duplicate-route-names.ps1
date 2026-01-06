param(
  # Optional: limit scanning to specific files (relative or absolute paths).
  [string[]]$Files
)

$ErrorActionPreference = 'Stop'

# Finds duplicate Laravel route names in the routes/ directory.
# Duplicate route names break: php artisan route:cache / optimize

$routeDir = Join-Path $PSScriptRoot '..\routes'
if (-not (Test-Path $routeDir)) {
  throw "routes directory not found at: $routeDir"
}

$targetFiles =
  if ($Files -and $Files.Count -gt 0) {
    foreach ($f in $Files) {
      $resolved = Resolve-Path -LiteralPath (Join-Path (Split-Path -Parent $routeDir) $f) -ErrorAction SilentlyContinue
      if (-not $resolved) {
        $resolved = Resolve-Path -LiteralPath $f -ErrorAction SilentlyContinue
      }
      if (-not $resolved) {
        throw "File not found: $f"
      }
      Get-Item -LiteralPath $resolved.Path
    }
  } else {
    Get-ChildItem -Path $routeDir -Recurse -File
  }

$names = foreach ($file in $targetFiles) {
  $i = 0
  foreach ($line in Get-Content -LiteralPath $file.FullName) {
    $i++
    $trim = $line.TrimStart()
    # Ignore commented lines (so we only report duplicates that can actually be registered)
    if ($trim.StartsWith('//') -or $trim.StartsWith('#') -or $trim.StartsWith('/*') -or $trim.StartsWith('*')) {
      continue
    }

    if ($line -match "->name\(\s*'([^']+)'\s*\)") {
      [pscustomobject]@{
        Name = $Matches[1]
        File = $file.FullName
        Line = $i
        Text = $line.Trim()
      }
    }
  }
}

$dupes =
  $names |
  Group-Object Name |
  Where-Object { $_.Count -gt 1 } |
  Sort-Object @{ Expression = 'Count'; Descending = $true }, @{ Expression = 'Name'; Descending = $false }

if (-not $dupes) {
  Write-Output "No duplicate route names found in routes/."
  exit 0
}

foreach ($d in $dupes) {
  Write-Output ""
  Write-Output ("DUPLICATE: {0} ({1})" -f $d.Name, $d.Count)
  foreach ($g in ($d.Group | Sort-Object File, Line)) {
    Write-Output ("  {0}:{1}  {2}" -f $g.File, $g.Line, $g.Text)
  }
}

exit 1


