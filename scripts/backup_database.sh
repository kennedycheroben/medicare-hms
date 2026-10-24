#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Database Backup Script
# ==============================================================================
# Usage:
#   ./scripts/backup_database.sh [OPTIONS]
#
# Options:
#   -e, --env-file PATH    Path to .env file (default: .env in project root)
#   -o, --output-dir DIR   Directory to store backups (default: storage/app/backups)
#   -r, --retention DAYS   Days to retain backups before pruning (default: 14)
#   -h, --help             Display this help message
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${PROJECT_ROOT}/.env"
OUTPUT_DIR="${PROJECT_ROOT}/storage/app/backups"
RETENTION_DAYS=14

while [[ $# -gt 0 ]]; do
    case "$1" in
        -e|--env-file)
            ENV_FILE="$2"
            shift 2
            ;;
        -o|--output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        -r|--retention)
            RETENTION_DAYS="$2"
            shift 2
            ;;
        -h|--help)
            echo "Usage: $0 [-e .env_path] [-o output_dir] [-r retention_days]"
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "ERROR: Environment file not found at: ${ENV_FILE}" >&2
    exit 1
fi

# Extract DB credentials safely from env file without evaluating arbitrary bash
get_env_val() {
    local key="$1"
    grep -E "^${key}=" "${ENV_FILE}" | head -n 1 | cut -d '=' -f2- | tr -d '"' | tr -d "'" || true
}

DB_CONNECTION="$(get_env_val DB_CONNECTION)"
DB_HOST="$(get_env_val DB_HOST)"
DB_PORT="$(get_env_val DB_PORT)"
DB_DATABASE="$(get_env_val DB_DATABASE)"
DB_USERNAME="$(get_env_val DB_USERNAME)"
DB_PASSWORD="$(get_env_val DB_PASSWORD)"

if [[ "${DB_CONNECTION}" != "mysql" && -n "${DB_CONNECTION}" ]]; then
    echo "ERROR: Unsupported database connection '${DB_CONNECTION}'. This script supports 'mysql'." >&2
    exit 1
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [[ -z "${DB_DATABASE}" ]]; then
    echo "ERROR: DB_DATABASE is not configured in ${ENV_FILE}" >&2
    exit 1
fi

# Detect mysqldump binary
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-}"
if [[ -z "${MYSQLDUMP_BIN}" ]]; then
    if [[ -x "/opt/lampp/bin/mysqldump" ]]; then
        MYSQLDUMP_BIN="/opt/lampp/bin/mysqldump"
    elif command -v mysqldump &> /dev/null; then
        MYSQLDUMP_BIN="$(command -v mysqldump)"
    else
        echo "ERROR: 'mysqldump' utility is not found on PATH or /opt/lampp/bin/." >&2
        exit 1
    fi
fi

if ! command -v gzip &> /dev/null; then
    echo "ERROR: 'gzip' utility is not found on PATH." >&2
    exit 1
fi

# Create secure backup directory with restricted permissions
mkdir -p "${OUTPUT_DIR}"
chmod 700 "${OUTPUT_DIR}"

# Write security hardening files in case of web directory misconfiguration
if [[ ! -f "${OUTPUT_DIR}/.htaccess" ]]; then
    echo "Deny from all" > "${OUTPUT_DIR}/.htaccess"
fi
if [[ ! -f "${OUTPUT_DIR}/index.html" ]]; then
    touch "${OUTPUT_DIR}/index.html"
fi

TIMESTAMP="$(date +"%Y%m%d_%H%M%S")"
BACKUP_FILE="${OUTPUT_DIR}/${DB_DATABASE}_${TIMESTAMP}.sql.gz"

echo "=== Starting MediCare HMS Database Backup ==="
echo "Database:  ${DB_DATABASE}"
echo "Host:      ${DB_HOST}:${DB_PORT}"
echo "Binary:    ${MYSQLDUMP_BIN}"
echo "Target:    ${BACKUP_FILE}"

# Check for column-statistics support
EXTRA_DUMP_FLAGS=()
if "${MYSQLDUMP_BIN}" --help 2>&1 | grep -q -- '--column-statistics'; then
    EXTRA_DUMP_FLAGS+=("--column-statistics=0")
fi

# Build mysqldump command with secure password handling via MYSQL_PWD
export MYSQL_PWD="${DB_PASSWORD}"

"${MYSQLDUMP_BIN}" \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --single-transaction \
    --quick \
    --triggers \
    --default-character-set=utf8mb4 \
    "${EXTRA_DUMP_FLAGS[@]}" \
    "${DB_DATABASE}" | gzip -c > "${BACKUP_FILE}"

unset MYSQL_PWD

# Enforce strict file permissions: read/write by owner only
chmod 600 "${BACKUP_FILE}"

BACKUP_SIZE="$(du -h "${BACKUP_FILE}" | cut -f1)"
echo "=== Backup Completed Successfully ==="
echo "File: ${BACKUP_FILE} (${BACKUP_SIZE})"

# Prune old backups if retention days specified
if [[ "${RETENTION_DAYS}" -gt 0 ]]; then
    echo "Pruning backups older than ${RETENTION_DAYS} days in ${OUTPUT_DIR}..."
    find "${OUTPUT_DIR}" -name "${DB_DATABASE}_*.sql.gz" -type f -mtime +"${RETENTION_DAYS}" -delete || true
fi

echo "Done."
