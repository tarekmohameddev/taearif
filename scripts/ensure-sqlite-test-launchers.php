<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$wrapperPath = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'php-with-sqlite.bat';
$phpunitBatPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit.bat';

if (! is_file($wrapperPath)) {
    fwrite(STDERR, "SQLite PHP wrapper not found: {$wrapperPath}\n");
    exit(1);
}

if (! is_dir(dirname($phpunitBatPath))) {
    fwrite(STDOUT, "Skipping phpunit.bat patch: vendor/bin is missing.\n");
    exit(0);
}

$phpunitBat = <<<'BAT'
@ECHO OFF
setlocal
set SCRIPT_DIR=%~dp0
call "%SCRIPT_DIR%..\..\scripts\php-with-sqlite.bat" "%SCRIPT_DIR%phpunit" %*
set EXIT_CODE=%ERRORLEVEL%

endlocal & exit /b %EXIT_CODE%
BAT;

$current = is_file($phpunitBatPath) ? file_get_contents($phpunitBatPath) : null;

if ($current === $phpunitBat) {
    fwrite(STDOUT, "vendor/bin/phpunit.bat already uses the SQLite wrapper.\n");
    exit(0);
}

file_put_contents($phpunitBatPath, $phpunitBat);
fwrite(STDOUT, "Patched vendor/bin/phpunit.bat to use scripts/php-with-sqlite.bat.\n");
