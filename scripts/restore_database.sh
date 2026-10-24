#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Database Restore Script
# ==============================================================================
# Usage:
#   ./scripts/restore_database.sh --file <backup.sql.gz> --target-db <database_name> --confirm [OPTIONS]
#
# Required Options:
#   -f, --file PATH        Path to .sql or .sql.gz backup file
#   -t, --target-db NAME   Target database to restore into (must be disposable/specified)
#   --confirm              Explicit safety acknowledgment to perform the restore
#
# Optional Options:
#   -e, --env-file PATH    Path to .env file for database credentials (default: .env)
#   -h, --help             Display this help message
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

BACKUP_FILE=""
TARGET_DB=""
CONFIRMED=false
ENV_FILE="${PROJECT_ROOT}/.env"

while [[ $# -gt 0 ]]; do
    case "$1" in
        -f|--file)
            BACKUP_FILE="$2"
            shift 2
            ;;
        -t|--target-db)
            TARGET_DB="$2"
            shift 2
            ;;
        --confirm)
            CONFIRMED=true
            shift 1
            ;;
        -e|--env-file)
            ENV_FILE="$2"
            shift 2
            ;;
        -h|--help)
            echo "Usage: $0 --file <backup.sql.gz> --target-db <target_database> --confirm [-e .env_path]"
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

if [[ -z "${BACKUP_FILE}" ]]; then
    echo "ERROR: Missing required option --file <path_to_backup_file>" >&2
    exit 1
fi

if [[ -z "${TARGET_DB}" ]]; then
    echo "ERROR: Missing required option --target-db <target_database_name>" >&2
    exit 1
fi

if [[ ! "${TARGET_DB}" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "ERROR: Unsafe target database identifier '${TARGET_DB}'. Database names must only contain alphanumeric characters and underscores." >&2
    exit 1
fi

if [[ "${CONFIRMED}" != "true" ]]; then
    echo "ERROR: Database restore aborted. You must supply '--confirm' to proceed with database restoration." >&2
    exit 1
fi

if [[ ! -f "${BACKUP_FILE}" ]]; then
    echo "ERROR: Backup file does not exist: ${BACKUP_FILE}" >&2
    exit 1
fi

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "ERROR: Environment file not found at: ${ENV_FILE}" >&2
    exit 1
fi

# Extract DB credentials safely
get_env_val() {
    local key="$1"
    grep -E "^${key}=" "${ENV_FILE}" | head -n 1 | cut -d '=' -f2- | tr -d '"' | tr -d "'" || true
}

DB_HOST="$(get_env_val DB_HOST)"
DB_PORT="$(get_env_val DB_PORT)"
DB_USERNAME="$(get_env_val DB_USERNAME)"
DB_PASSWORD="$(get_env_val DB_PASSWORD)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

# Detect mysql binary
MYSQL_BIN="${MYSQL_BIN:-}"
if [[ -z "${MYSQL_BIN}" ]]; then
    if [[ -x "/opt/lampp/bin/mysql" ]]; then
        MYSQL_BIN="/opt/lampp/bin/mysql"
    elif command -v mysql &> /dev/null; then
        MYSQL_BIN="$(command -v mysql)"
    else
        echo "ERROR: 'mysql' client utility is not found on PATH or /opt/lampp/bin/." >&2
        exit 1
    fi
fi

# Validate backup file integrity if gzipped
if [[ "${BACKUP_FILE}" == *.gz ]]; then
    echo "Verifying archive integrity: ${BACKUP_FILE}..."
    if ! gzip -t "${BACKUP_FILE}"; then
        echo "ERROR: Backup archive is corrupted or invalid." >&2
        exit 1
    fi
    RESTORE_CMD="gunzip -c \"${BACKUP_FILE}\""
else
    RESTORE_CMD="cat \"${BACKUP_FILE}\""
fi

echo "=== Starting MediCare HMS Database Restore ==="
echo "Source File: ${BACKUP_FILE}"
echo "Target DB:   ${TARGET_DB}"
echo "Host:        ${DB_HOST}:${DB_PORT}"

export MYSQL_PWD="${DB_PASSWORD}"

# Ensure target database exists
"${MYSQL_BIN}" --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME}" -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Execute restore
eval "${RESTORE_CMD}" | "${MYSQL_BIN}" --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME}" "${TARGET_DB}"

TABLE_COUNT="$("${MYSQL_BIN}" --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USERNAME}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${TARGET_DB}';")"

unset MYSQL_PWD

echo "=== Database Restore Completed Successfully ==="
echo "Target Database '${TARGET_DB}' contains ${TABLE_COUNT} tables."
