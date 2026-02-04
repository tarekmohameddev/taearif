#!/usr/bin/env bash
set -e

# ==========================================
# Restore E2E test database: taearif_testing
# ==========================================

DB_NAME="taearif_testing"
DB_USER="root"
DB_PASS=""
DB_HOST="127.0.0.1"
SQL_FILE="the_test_db/taearif_testing.sql"

echo "=========================================="
echo "Restoring E2E test database: ${DB_NAME}"
echo "=========================================="

if [ ! -f "$SQL_FILE" ]; then
  echo "ERROR: SQL dump not found at ${SQL_FILE}"
  exit 1
fi

echo "Dropping database if exists..."
mysql -h"$DB_HOST" -u"$DB_USER" $DB_PASS \
  -e "DROP DATABASE IF EXISTS ${DB_NAME};"

echo "Creating database..."
mysql -h"$DB_HOST" -u"$DB_USER" $DB_PASS \
  -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Importing SQL dump..."
mysql -h"$DB_HOST" -u"$DB_USER" $DB_PASS "$DB_NAME" < "$SQL_FILE"

echo "✅ Database ${DB_NAME} restored successfully."
