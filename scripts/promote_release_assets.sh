#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Safe Asset Promotion Script
# ==============================================================================
# Usage:
#   ./scripts/promote_release_assets.sh [OPTIONS]
#
# Options:
#   --app-dir DIR          Application root directory (default: /home/xqtrqexj/medicare-hms-app)
#   --web-root DIR         Public web document root (default: /home/xqtrqexj/medicare.kennedycheroben.co.ke)
#   --confirm-replace      Explicitly approve replacing existing destination files
#   --dry-run              Inspect and display change manifest without modifying files
#   -h, --help             Display this help message
# ==============================================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/home/xqtrqexj/medicare-hms-app}"
WEB_ROOT="${WEB_ROOT:-/home/xqtrqexj/medicare.kennedycheroben.co.ke}"
ALLOW_REPLACE="${ALLOW_REPLACE:-0}"
DRY_RUN=0

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
        --confirm-replace)
            ALLOW_REPLACE=1
            shift 1
            ;;
        --dry-run)
            DRY_RUN=1
            shift 1
            ;;
        -h|--help)
            echo "Usage: $0 [--app-dir DIR] [--web-root DIR] [--confirm-replace] [--dry-run]"
            exit 0
            ;;
        *)
            echo "ERROR: Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="${WEB_ROOT%/*}/backup_webroot_${TIMESTAMP}"
STAGING_DIR="${WEB_ROOT%/*}/staging_webroot_${TIMESTAMP}"

# 1. Pre-flight sanity checks
if [ ! -d "${APP_DIR}/public" ]; then
    echo "ERROR: Application public directory not found at: ${APP_DIR}/public" >&2
    exit 1
fi

if [ ! -d "${WEB_ROOT}" ]; then
    if [ "${DRY_RUN}" = "1" ]; then
        echo "[DRY-RUN] Would create destination web root: ${WEB_ROOT}"
    else
        echo "Creating destination web root: ${WEB_ROOT}"
        mkdir -p "${WEB_ROOT}"
    fi
fi

# 2. Prepare staging directory
if [ "${DRY_RUN}" = "0" ]; then
    mkdir -p "${STAGING_DIR}"
fi

# Helper cleanup trap
cleanup() {
    if [ -d "${STAGING_DIR}" ]; then
        rm -rf "${STAGING_DIR}"
    fi
}
trap cleanup EXIT

# 3. Stage managed assets from application build
MANAGED_ITEMS=("admin-assets" "assets" "build" "index.php" "favicon.ico" "robots.txt")

for item in "${MANAGED_ITEMS[@]}"; do
    if [ -e "${APP_DIR}/public/${item}" ]; then
        if [ "${DRY_RUN}" = "0" ]; then
            cp -r "${APP_DIR}/public/${item}" "${STAGING_DIR}/${item}"
        fi
    else
        echo "WARNING: Expected public asset ${APP_DIR}/public/${item} not found in application build."
    fi
done

# 4. Handle .htaccess preservation safely
# Truehost cPanel automatically generates PHP handlers:
# e.g., "# php -- BEGIN cPanel-generated handler ... # php -- END cPanel-generated handler"
if [ -f "${WEB_ROOT}/.htaccess" ]; then
    if [ "${DRY_RUN}" = "0" ]; then
        # Preserve existing .htaccess as base
        cp "${WEB_ROOT}/.htaccess" "${STAGING_DIR}/.htaccess"

        # Check if Laravel front controller rewrite block is present
        if ! grep -q "RewriteEngine On" "${STAGING_DIR}/.htaccess"; then
            echo -e "\n# --- Laravel Front Controller Directives (Preserving cPanel Handlers - Added ${TIMESTAMP}) ---" >> "${STAGING_DIR}/.htaccess"
            cat "${APP_DIR}/public/.htaccess" >> "${STAGING_DIR}/.htaccess"
        fi
    fi
elif [ -f "${APP_DIR}/public/.htaccess" ]; then
    if [ "${DRY_RUN}" = "0" ]; then
        cp "${APP_DIR}/public/.htaccess" "${STAGING_DIR}/.htaccess"
    fi
fi

ALL_STAGED_ITEMS=("${MANAGED_ITEMS[@]}" ".htaccess")

# 5. Inspection & Collision Analysis
echo "=== MediCare HMS Destination Inspection & Safety Audit ==="
echo "Application Source: ${APP_DIR}/public"
echo "Target Web Root:    ${WEB_ROOT}"

COLLISIONS=0
REPLACEMENTS=()

for item in "${ALL_STAGED_ITEMS[@]}"; do
    TARGET_PATH="${WEB_ROOT}/${item}"

    if [ ! -e "${TARGET_PATH}" ]; then
        echo "  [NEW]         ${item} (does not exist in destination; safe to create)"
    elif [ -d "${TARGET_PATH}" ]; then
        if [ "${DRY_RUN}" = "0" ] && [ -d "${STAGING_DIR}/${item}" ]; then
            if diff -qr "${STAGING_DIR}/${item}" "${TARGET_PATH}" >/dev/null 2>&1; then
                echo "  [UNCHANGED]   ${item}/ (identical to staged release)"
            else
                echo "  [COLLISION]   ${item}/ (existing directory differs from staged release)"
                COLLISIONS=$((COLLISIONS + 1))
                REPLACEMENTS+=("${item}")
            fi
        else
            echo "  [COLLISION]   ${item}/ (existing directory)"
            COLLISIONS=$((COLLISIONS + 1))
            REPLACEMENTS+=("${item}")
        fi
    elif [ -f "${TARGET_PATH}" ]; then
        if [ "${DRY_RUN}" = "0" ] && [ -f "${STAGING_DIR}/${item}" ]; then
            if cmp -s "${STAGING_DIR}/${item}" "${TARGET_PATH}"; then
                echo "  [UNCHANGED]   ${item} (identical file content)"
            else
                echo "  [COLLISION]   ${item} (existing file differs from staged release)"
                COLLISIONS=$((COLLISIONS + 1))
                REPLACEMENTS+=("${item}")
            fi
        else
            echo "  [COLLISION]   ${item} (existing file)"
            COLLISIONS=$((COLLISIONS + 1))
            REPLACEMENTS+=("${item}")
        fi
    else
        echo "  [ANOMALY]     ${item} (unexpected node type: neither file nor directory)"
        echo "ERROR: Destination ${TARGET_PATH} is an unhandled node type. Human review required." >&2
        exit 1
    fi
done

# Check for unmanaged/foreign files in destination web root
if [ -d "${WEB_ROOT}" ]; then
    while IFS= read -r -d $'\0' entry; do
        entry_name="$(basename "${entry}")"
        # Skip standard known entries
        if [[ "${entry_name}" =~ ^(\.|\.\.|admin-assets|assets|build|index\.php|favicon\.ico|robots\.txt|\.htaccess|storage|cgi-bin|\.well-known)$ ]]; then
            continue
        fi
        echo "  [PRESERVED]   ${entry_name} (unmanaged file/directory in web root; will be preserved untouched)"
    done < <(find "${WEB_ROOT}" -mindepth 1 -maxdepth 1 -print0 2>/dev/null)
fi

echo "========================================================="

if [ "${DRY_RUN}" = "1" ]; then
    echo "[DRY-RUN] Inspection completed. ${COLLISIONS} item(s) would require replacement."
    exit 0
fi

# 6. Safety Gate: Require explicit operator approval before replacing differing destinations
if [ "${COLLISIONS}" -gt 0 ] && [ "${ALLOW_REPLACE}" != "1" ]; then
    echo "" >&2
    echo "SAFETY STOP: ${COLLISIONS} destination item(s) already exist and differ from the staged release:" >&2
    for r in "${REPLACEMENTS[@]}"; do
        echo "  - ${r}" >&2
    done
    echo "" >&2
    echo "To protect existing configurations and prevent accidental clobbering, silent replacement is blocked." >&2
    echo "To review and explicitly approve replacing these destination items with the staged release:" >&2
    echo "  1. Review the diff or inspect the items listed above." >&2
    echo "  2. Rerun with ALLOW_REPLACE=1 or pass --confirm-replace:" >&2
    echo "     ALLOW_REPLACE=1 $0 --app-dir \"${APP_DIR}\" --web-root \"${WEB_ROOT}\"" >&2
    exit 1
fi

# 7. Pre-Sync Backup (Only executed when changes are actually approved)
PRESERVED_BACKUPS=()
if [ "$(ls -A "${WEB_ROOT}" 2>/dev/null)" ]; then
    echo "Creating full defensive backup of web root to: ${BACKUP_DIR}..."
    cp -a "${WEB_ROOT}" "${BACKUP_DIR}"
    PRESERVED_BACKUPS+=("${BACKUP_DIR}")
    echo "Full web root backup created at: ${BACKUP_DIR}"
fi

# 8. Safe Promotion of Approved Staged Items
echo "Promoting staged items to web root: ${WEB_ROOT}..."
for item in "${ALL_STAGED_ITEMS[@]}"; do
    if [ ! -e "${STAGING_DIR}/${item}" ]; then
        continue
    fi

    DEST_ITEM="${WEB_ROOT}/${item}"

    if [ -d "${STAGING_DIR}/${item}" ]; then
        if [ -d "${DEST_ITEM}" ]; then
            # Safe replacement of managed directory: atomic rename/swap
            # Retain previous live directory for immediate rollback safety; do NOT delete immediately.
            PREV_BACKUP="${DEST_ITEM}_prev_${TIMESTAMP}"
            mv "${DEST_ITEM}" "${PREV_BACKUP}"
            cp -a "${STAGING_DIR}/${item}" "${DEST_ITEM}"
            PRESERVED_BACKUPS+=("${PREV_BACKUP}")
            echo "  - Promoted directory: ${item}/ (previous version preserved at: ${PREV_BACKUP})"
        else
            cp -a "${STAGING_DIR}/${item}" "${DEST_ITEM}"
            echo "  - Promoted directory: ${item}/ (new)"
        fi
    else
        # File copy with permission preservation
        cp -a "${STAGING_DIR}/${item}" "${DEST_ITEM}"
        echo "  - Promoted file: ${item}"
    fi
done

echo ""
echo "========================================================="
echo "PROMOTION SUMMARY & BACKUP RETENTION POLICY"
echo "========================================================="
echo "SUCCESS: Web document root safely updated."
echo "Existing cPanel handlers preserved in .htaccess."
echo ""
echo "RETAINED BACKUPS FOR ROLLBACK:"
for b in "${PRESERVED_BACKUPS[@]}"; do
    echo "  - ${b}"
done
echo ""
echo "IMPORTANT: All backups are permanently preserved until a human operator"
echo "independently verifies the live application and explicitly approves cleanup."
echo "To roll back immediately if any issue is detected:"
if [ -d "${BACKUP_DIR:-}" ]; then
    echo "  cp -a \"${BACKUP_DIR}/.\" \"${WEB_ROOT}/\""
fi
echo "========================================================="
exit 0
