param(
    [string]$SqlFile = "",
    [string[]]$Databases = @("taearif", "taearif_testing"),
    [switch]$IncludeEnvDatabase,
    [switch]$Strict
)

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$envFile = Join-Path $projectRoot ".env"

function Read-DotEnvValue {
    param(
        [string]$Path,
        [string]$Key,
        [string]$Default = ""
    )

    if (-not (Test-Path $Path)) {
        return $Default
    }

    foreach ($line in Get-Content $Path) {
        if ($line -match "^\s*#") {
            continue
        }

        if ($line -match "^\s*$Key\s*=\s*(.*)\s*$") {
            $value = $Matches[1].Trim()

            if (
                ($value.StartsWith('"') -and $value.EndsWith('"')) -or
                ($value.StartsWith("'") -and $value.EndsWith("'"))
            ) {
                $value = $value.Substring(1, $value.Length - 2)
            }

            return $value
        }
    }

    return $Default
}

function Resolve-SqlDumpPath {
    param(
        [string]$RequestedPath,
        [string]$Root
    )

    if ($RequestedPath -ne "") {
        if (-not (Test-Path $RequestedPath)) {
            throw "SQL dump not found at '$RequestedPath'."
        }

        return (Resolve-Path $RequestedPath).Path
    }

    $candidates = @(
        (Join-Path $Root "the_test_db\taearif.sql"),
        (Join-Path $Root "the_test_db\taearif_testing.sql"),
        (Join-Path $Root "database\taearif.sql"),
        (Join-Path $Root "taearif.sql")
    )

    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            return (Resolve-Path $candidate).Path
        }
    }

    throw @"
SQL dump not found.

Place your full database export at one of:
  the_test_db\taearif.sql
  the_test_db\taearif_testing.sql
  database\taearif.sql
  taearif.sql

Or pass -SqlFile 'C:\path\to\dump.sql'
"@
}

function Resolve-MySqlExecutable {
    $mysql = Get-Command mysql -ErrorAction SilentlyContinue
    if ($mysql) {
        return $mysql.Source
    }

    $laragonRoots = @(
        "D:\dev\laragon\bin\mysql",
        "C:\laragon\bin\mysql"
    )

    foreach ($root in $laragonRoots) {
        if (-not (Test-Path $root)) {
            continue
        }

        $candidate = Get-ChildItem -Path $root -Directory -ErrorAction SilentlyContinue |
            Sort-Object Name -Descending |
            ForEach-Object { Join-Path $_.FullName "bin\mysql.exe" } |
            Where-Object { Test-Path $_ } |
            Select-Object -First 1

        if ($candidate) {
            return $candidate
        }
    }

    throw "mysql client not found. Add Laragon MySQL to PATH or install the MySQL client."
}

function Invoke-MySql {
    param(
        [string]$Executable,
        [string]$HostName,
        [string]$Port,
        [string]$User,
        [string]$Password,
        [string]$Database = "",
        [string]$Sql = ""
    )

    $args = @(
        "--host=$HostName",
        "--port=$Port",
        "--user=$User",
        "--default-character-set=utf8mb4"
    )

    if ($Password -ne "") {
        $args += "--password=$Password"
    }

    if ($Database -ne "") {
        $args += $Database
    }

    if ($Sql -ne "") {
        $args += "-e"
        $args += $Sql
    }

    & $Executable @args
    if ($LASTEXITCODE -ne 0) {
        throw "mysql command failed with exit code $LASTEXITCODE."
    }
}

function Import-Database {
    param(
        [string]$Executable,
        [string]$HostName,
        [string]$Port,
        [string]$User,
        [string]$Password,
        [string]$DatabaseName,
        [string]$DumpPath,
        [bool]$StrictImport
    )

    Write-Host ""
    Write-Host "Restoring database '$DatabaseName' from '$DumpPath'..."

    Invoke-MySql -Executable $Executable -HostName $HostName -Port $Port -User $User -Password $Password -Sql "DROP DATABASE IF EXISTS ``$DatabaseName``;"
    Invoke-MySql -Executable $Executable -HostName $HostName -Port $Port -User $User -Password $Password -Sql "CREATE DATABASE ``$DatabaseName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    $importArgs = @(
        "--host=$HostName",
        "--port=$Port",
        "--user=$User",
        "--default-character-set=utf8mb4",
        "--init-command=SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET SQL_MODE=NO_AUTO_VALUE_ON_ZERO;"
    )

    if ($Password -ne "") {
        $importArgs += "--password=$Password"
    }

    if (-not $StrictImport) {
        $importArgs += "--force"
    }

    $importArgs += $DatabaseName

    $argumentString = ($importArgs | ForEach-Object {
        if ($_ -match '\s') {
            '"' + ($_ -replace '"', '\"') + '"'
        } else {
            $_
        }
    }) -join ' '

    $command = "`"$Executable`" $argumentString < `"$DumpPath`""
    cmd.exe /c $command
    if ($LASTEXITCODE -ne 0) {
        throw "Import failed for database '$DatabaseName'."
    }

    if (-not $StrictImport) {
        Write-Host "Import completed with foreign key checks disabled during restore."
        Write-Host "Some late constraint statements may have been skipped if the dump had orphaned rows."
    }

    Write-Host "Database '$DatabaseName' restored successfully."
}

$resolvedSqlFile = Resolve-SqlDumpPath -RequestedPath $SqlFile -Root $projectRoot
$mysqlExe = Resolve-MySqlExecutable
$dbHost = Read-DotEnvValue -Path $envFile -Key "DB_HOST" -Default "127.0.0.1"
$dbPort = Read-DotEnvValue -Path $envFile -Key "DB_PORT" -Default "3306"
$dbUser = Read-DotEnvValue -Path $envFile -Key "DB_USERNAME" -Default "root"
$dbPassword = Read-DotEnvValue -Path $envFile -Key "DB_PASSWORD" -Default ""

$targetDatabases = [System.Collections.Generic.List[string]]::new()
foreach ($database in $Databases) {
    if ($database -ne "" -and -not $targetDatabases.Contains($database)) {
        [void]$targetDatabases.Add($database)
    }
}

if ($IncludeEnvDatabase) {
    $envDatabase = Read-DotEnvValue -Path $envFile -Key "DB_DATABASE" -Default ""
    if ($envDatabase -ne "" -and -not $targetDatabases.Contains($envDatabase)) {
        [void]$targetDatabases.Add($envDatabase)
    }
}

if ($targetDatabases.Count -eq 0) {
    throw "No target databases were selected."
}

Write-Host "=========================================="
Write-Host "Taearif database import"
Write-Host "=========================================="
Write-Host "Dump file : $resolvedSqlFile"
Write-Host "MySQL host: ${dbHost}:${dbPort}"
Write-Host "Targets   : $($targetDatabases -join ', ')"
if ($Strict) {
    Write-Host "Mode      : strict (import stops on the first SQL error)"
} else {
    Write-Host "Mode      : resilient (continues past late foreign-key constraint errors)"
}

foreach ($databaseName in $targetDatabases) {
    Import-Database -Executable $mysqlExe -HostName $dbHost -Port $dbPort -User $dbUser -Password $dbPassword -DatabaseName $databaseName -DumpPath $resolvedSqlFile -StrictImport:$Strict.IsPresent
}

Write-Host ""
Write-Host "All selected databases were restored."
