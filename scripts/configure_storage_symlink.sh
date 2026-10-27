#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Safe Storage Symlink & Permissions
# ==============================================================================
# Usage:
#   ./scripts/configure_storage_symlink.sh [OPTIONS]
#
# Options:
#   --app-dir DIR                 Application root directory (default: /home/xqtrqexj/medicare-hms-app)
#   --web-root DIR                Public web document root (default: /home/xqtrqexj/medicare.kennedycheroben.co.ke)
#   --confirm-stale-symlink       Explicitly approve replacing an unexpected or broken symlink
#   --confirm-dir-migration       Explicitly approve backing up an existing physical directory
#   --skip-chmod                  Skip directory/file permission updates
#   -h, --help                    Display this help message
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/home/xqtrqexj/medicare-hms-app}"
WEB_ROOT="${WEB_ROOT:-/home/xqtrqexj/medicare.kennedycheroben.co.ke}"
CONFIRM_STALE_SYMLINK="${CONFIRM_STALE_SYMLINK:-0}"
CONFIRM_DIR_MIGRATION="${CONFIRM_DIR_MIGRATION:-0}"
SKIP_CHMOD=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --app-dir)
            APP_DIR="$2"
            shift 2
            ;;
        --web-root)
            WEB_ROOT="$2"
            shift 2
            ;;
        --confirm-stale-symlink)
            CONFIRM_STALE_SYMLINK=1
            shift 1
            ;;
        --confirm-dir-migration)
            CONFIRM_DIR_MIGRATION=1
            shift 1
            ;;
        --skip-chmod)
            SKIP_CHMOD=1
            shift 1
            ;;
        -h|--help)
            echo "Usage: $0 [--app-dir DIR] [--web-root DIR] [--confirm-stale-symlink] [--confirm-dir-migration]"
            exit 0
            ;;
        *)
            echo "ERROR: Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

STORAGE_TARGET="${APP_DIR}/storage/app/public"
STORAGE_LINK="${WEB_ROOT}/storage"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# 1. Least-Privilege Permissions Configuration
# On cPanel shared hosting (suPHP, PHP-FPM, or LiteSpeed lsapi), PHP runs with
# the user's own UID (xqtrqexj). Group write permissions (e.g. 775 or 664) are
# not only unnecessary, but trigger HTTP 500 "Directory is writable by group"
# errors under suPHP security policies.
#
# Least-privilege standard:
#   - Directories: 755 (u=rwx,go=rx)
#   - Files:       644 (u=rw,go=r)
#   - Environment: 600 (u=rw,go=)
if [ "${SKIP_CHMOD}" = "0" ] && [ -d "${APP_DIR}/storage" ] && [ -d "${APP_DIR}/bootstrap/cache" ]; then
    echo "Applying least-privilege permissions (755 directories / 644 files)..."
    find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod 755 {} +
    find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type f -exec chmod 644 {} +
    if [ -f "${APP_DIR}/.env" ]; then
        chmod 600 "${APP_DIR}/.env"
    fi
fi

# 2. Validate source storage target exists
if [ ! -d "${STORAGE_TARGET}" ]; then
    echo "Creating public storage target directory: ${STORAGE_TARGET}..."
    mkdir -p "${STORAGE_TARGET}"
    chmod 755 "${STORAGE_TARGET}"
fi

# 3. Evaluate destination state at ${STORAGE_LINK}
echo "=== MediCare HMS Storage Link Evaluation ==="
echo "Target:      ${STORAGE_TARGET}"
echo "Destination: ${STORAGE_LINK}"

if [ -L "${STORAGE_LINK}" ]; then
    CURRENT_DEST=$(readlink "${STORAGE_LINK}" || true)
    CURRENT_CANONICAL=$(readlink -f "${STORAGE_LINK}" 2>/dev/null || true)
    EXPECTED_CANONICAL=$(readlink -f "${STORAGE_TARGET}" 2>/dev/null || true)

    if [ -n "${CURRENT_CANONICAL}" ] && [ "${CURRENT_CANONICAL}" = "${EXPECTED_CANONICAL}" ] && [ -d "${STORAGE_LINK}" ]; then
        echo "STATUS: [HEALTHY] Symlink is correctly configured and pointing to target."
        echo "Link target: ${CURRENT_DEST}"
        exit 0
    elif [ -e "${STORAGE_LINK}" ]; then
        # Symlink is valid but points to an UNEXPECTED active destination
        echo "STATUS: [UNEXPECTED TARGET] Symlink points to an alternate active destination:" >&2
        echo "  Current target:  ${CURRENT_DEST} (${CURRENT_CANONICAL})" >&2
        echo "  Expected target: ${STORAGE_TARGET} (${EXPECTED_CANONICAL})" >&2
        echo "" >&2
        echo "SAFETY STOP: Automatic replacement of an active symlink target is blocked to prevent breaking other paths." >&2
        if [ "${CONFIRM_STALE_SYMLINK}" != "1" ]; then
            echo "To explicitly approve replacing this symlink, re-run with:" >&2
            echo "  CONFIRM_STALE_SYMLINK=1 $0 --app-dir \"${APP_DIR}\" --web-root \"${WEB_ROOT}\"" >&2
            exit 1
        fi
        echo "Explicit operator confirmation provided (CONFIRM_STALE_SYMLINK=1). Replacing symlink..."
        rm "${STORAGE_LINK}"
        ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    else
        # Broken / dangling symlink (target does not exist)
        echo "STATUS: [BROKEN SYMLINK] Destination is a dangling link pointing to non-existent path: ${CURRENT_DEST}" >&2
        if [ "${CONFIRM_STALE_SYMLINK}" != "1" ]; then
            echo "SAFETY STOP: Stale symlink detected. To explicitly approve removing the stale link and pointing to ${STORAGE_TARGET}, re-run with:" >&2
            echo "  CONFIRM_STALE_SYMLINK=1 $0 --app-dir \"${APP_DIR}\" --web-root \"${WEB_ROOT}\"" >&2
            exit 1
        fi
        echo "Explicit operator confirmation provided (CONFIRM_STALE_SYMLINK=1). Recreating symlink..."
        rm "${STORAGE_LINK}"
        ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    fi

elif [ -d "${STORAGE_LINK}" ]; then
    # Destination is a physical directory (NOT a symlink)
    echo "STATUS: [PHYSICAL DIRECTORY] Destination is an existing physical directory (not a symlink)." >&2
    echo "SAFETY STOP: Replacing a physical directory can cause media upload data loss." >&2
    if [ "${CONFIRM_DIR_MIGRATION}" != "1" ]; then
        echo "To explicitly approve backing up this directory and replacing it with the symlink:" >&2
        echo "  1. Check for any uploaded patient documents or files in: ${STORAGE_LINK}" >&2
        echo "  2. Rerun with CONFIRM_DIR_MIGRATION=1:" >&2
        echo "     CONFIRM_DIR_MIGRATION=1 $0 --app-dir \"${APP_DIR}\" --web-root \"${WEB_ROOT}\"" >&2
        exit 1
    fi
    BACKUP_STORAGE="${STORAGE_LINK}_dir_backup_${TIMESTAMP}"
    echo "Backing up physical storage directory to: ${BACKUP_STORAGE}..."
    mv "${STORAGE_LINK}" "${BACKUP_STORAGE}"
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    echo "IMPORTANT: Review ${BACKUP_STORAGE} and migrate any uploaded media into ${STORAGE_TARGET}/."

elif [ -e "${STORAGE_LINK}" ]; then
    # Regular file or special node
    echo "ERROR: Destination ${STORAGE_LINK} exists as an unexpected file/special node (not directory or symlink)." >&2
    echo "Stopping for manual review." >&2
    exit 1

else
    # Destination does not exist; safe to create
    echo "Creating public media storage symlink: ${STORAGE_LINK} -> ${STORAGE_TARGET}..."
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
fi

# 4. Final Verification
if [ -L "${STORAGE_LINK}" ] && [ -d "${STORAGE_LINK}" ]; then
    VERIFIED_REAL=$(readlink -f "${STORAGE_LINK}")
    EXPECTED_REAL=$(readlink -f "${STORAGE_TARGET}")
    if [ "${VERIFIED_REAL}" = "${EXPECTED_REAL}" ]; then
        echo "SUCCESS: Storage symlink verified: ${STORAGE_LINK} -> $(readlink "${STORAGE_LINK}")"
        exit 0
    else
        echo "ERROR: Storage symlink points to unexpected target: ${VERIFIED_REAL}" >&2
        exit 1
    fi
else
    echo "ERROR: Storage symlink verification failed. Target inaccessible." >&2
    exit 1
fi
