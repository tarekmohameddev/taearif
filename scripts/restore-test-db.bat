@echo off
setlocal ENABLEDELAYEDEXPANSION

REM ==========================================
REM Restore E2E test database: taearif_testing
REM ==========================================

set DB_NAME=taearif_testing
set DB_USER=root
set DB_PASS=
set DB_HOST=127.0.0.1
set SQL_FILE=the_test_db\taearif_testing.sql

echo.
echo ==========================================
echo Restoring E2E test database: %DB_NAME%
echo ==========================================

if not exist "%SQL_FILE%" (
    echo ERROR: SQL dump not found at %SQL_FILE%
    exit /b 1
)

echo Dropping database if exists...
mysql -h%DB_HOST% -u%DB_USER% %DB_PASS% -e "DROP DATABASE IF EXISTS %DB_NAME%;"
if errorlevel 1 (
    echo ERROR: Failed to drop database
    exit /b 1
)

echo Creating database...
mysql -h%DB_HOST% -u%DB_USER% %DB_PASS% -e ^
"CREATE DATABASE %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo ERROR: Failed to create database
    exit /b 1
)

echo Importing SQL dump...
mysql -h%DB_HOST% -u%DB_USER% %DB_PASS% %DB_NAME% < "%SQL_FILE%"
if errorlevel 1 (
    echo ERROR: Import failed
    exit /b 1
)

echo.
echo ✅ Database %DB_NAME% restored successfully.
echo.

endlocal
