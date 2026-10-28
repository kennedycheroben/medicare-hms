#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Deterministic cPanel Deployment Script
# ==============================================================================
# Usage:
#   ./scripts/cpanel_deploy.sh [OPTIONS]
#
# Options:
#   --app-dir DIR         Target Laravel application directory
#                         (default: /home/xqtrqexj/medicare-hms-app)
#   --web-root DIR        Target public document root
#                         (default: /home/xqtrqexj/medicare.kennedycheroben.co.ke)
#   --dry-run             Preview all planned operations without modifying files
#   --check-only          Run non-destructive pre-flight environment checks only
#   -h, --help            Show this help message
# ==============================================================================

set -euo pipefail

# Default configuration paths
APP_DIR="${APP_DIR:-/home/xqtrqexj/medicare-hms-app}"
WEB_ROOT="${WEB_ROOT:-/home/xqtrqexj/medicare.kennedycheroben.co.ke}"
DRY_RUN=0
CHECK_ONLY=0

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
        --dry-run)
            DRY_RUN=1
            shift 1
            ;;
        --check-only)
            CHECK_ONLY=1
            shift 1
            ;;
        -h|--help)
            echo "Usage: $0 [--app-dir DIR] [--web-root DIR] [--dry-run] [--check-only]"
            exit 0
            ;;
        *)
            echo "ERROR: Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOCK_FILE="${TMPDIR:-/tmp}/medicare_deploy.lock"

log_info() {
    echo -e "[INFO] $1"
}

log_warn() {
    echo -e "[WARN] $1" >&2
}

log_error() {
    echo -e "[ERROR] $1" >&2
}

log_success() {
    echo -e "[SUCCESS] $1"
}

# ==============================================================================
# 1. Pre-Flight Sanity Checks & Concurrency Lock
# ==============================================================================
log_info "=== MediCare HMS Deployment Pre-Flight Checks ==="
log_info "Repository Source:       ${REPO_DIR}"
log_info "Target Application Root: ${APP_DIR}"
log_info "Target Document Root:    ${WEB_ROOT}"

# Validate repository integrity
if [ ! -d "${REPO_DIR}/app" ] || [ ! -f "${REPO_DIR}/bootstrap/app.php" ] || [ ! -f "${REPO_DIR}/artisan" ]; then
    log_error "Source repository does not contain a complete Laravel application."
    exit 1
fi

# Validate production assets exist
if [ ! -f "${REPO_DIR}/public/build/manifest.json" ]; then
    log_error "Production Vite build manifest missing: ${REPO_DIR}/public/build/manifest.json"
    log_error "Run 'npm run build' before deploying."
    exit 1
fi

if [ ! -f "${REPO_DIR}/public/index.php" ]; then
    log_error "Front controller bridge missing: ${REPO_DIR}/public/index.php"
    exit 1
fi

# Validate PHP syntax on critical entry points if PHP is available
if command -v php >/dev/null 2>&1; then
    php -l "${REPO_DIR}/public/index.php" >/dev/null || { log_error "PHP syntax error in public/index.php"; exit 1; }
    php -l "${REPO_DIR}/bootstrap/app.php" >/dev/null || { log_error "PHP syntax error in bootstrap/app.php"; exit 1; }
    log_info "PHP syntax verified for front controller and application bootstrap."
fi

if [ "${CHECK_ONLY}" = "1" ]; then
    log_success "Pre-flight checks passed cleanly. (check-only mode)"
    exit 0
fi

# Concurrency lock protection to prevent overlapping deployments
if [ "${DRY_RUN}" = "0" ]; then
    exec 200>"${LOCK_FILE}"
    if command -v flock >/dev/null 2>&1; then
        if ! flock -n 200; then
            log_error "Another deployment is currently running (lock acquired on ${LOCK_FILE})."
            exit 1
        fi
    fi
fi

# ==============================================================================
# 2. Deploy Application Files to Private Directory (if Repo != APP_DIR)
# ==============================================================================
log_info "=== Synchronizing Laravel Application Code ==="
CANONICAL_REPO="$(cd "${REPO_DIR}" && pwd -P)"
if [ -d "${APP_DIR}" ]; then
    CANONICAL_APP="$(cd "${APP_DIR}" && pwd -P)"
else
    CANONICAL_APP="${APP_DIR}"
fi

if [ "${CANONICAL_REPO}" != "${CANONICAL_APP}" ]; then
    log_info "Repository is external to application directory; copying application files..."
    APP_COMPONENTS=("app" "bootstrap" "config" "database" "resources" "routes" "scripts")
    APP_FILES=("artisan" "composer.json" "composer.lock" "package.json")

    for dir in "${APP_COMPONENTS[@]}"; do
        if [ -d "${REPO_DIR}/${dir}" ]; then
            if [ "${DRY_RUN}" = "1" ]; then
                log_info "[DRY-RUN] Would sync directory: ${REPO_DIR}/${dir} -> ${APP_DIR}/${dir}"
            else
                /bin/mkdir -p "${APP_DIR}/${dir}"
                /bin/cp -R "${REPO_DIR}/${dir}/." "${APP_DIR}/${dir}/"
            fi
        fi
    done

    for file in "${APP_FILES[@]}"; do
        if [ -f "${REPO_DIR}/${file}" ]; then
            if [ "${DRY_RUN}" = "1" ]; then
                log_info "[DRY-RUN] Would sync file: ${REPO_DIR}/${file} -> ${APP_DIR}/${file}"
            else
                /bin/cp "${REPO_DIR}/${file}" "${APP_DIR}/${file}"
            fi
        fi
    done

    # Ensure required writable storage and cache directories exist in APP_DIR
    REQUIRED_APP_DIRS=(
        "storage/app/public"
        "storage/framework/cache/data"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
    )
    for sub in "${REQUIRED_APP_DIRS[@]}"; do
        if [ "${DRY_RUN}" = "1" ]; then
            log_info "[DRY-RUN] Would ensure directory exists: ${APP_DIR}/${sub}"
        else
            /bin/mkdir -p "${APP_DIR}/${sub}"
        fi
    done
else
    log_info "Repository root is already the application directory (${APP_DIR}). In-place deploy."
fi

# Safety invariant: Never touch existing .env
if [ -f "${APP_DIR}/.env" ]; then
    log_info "Preserving existing production .env file."
elif [ -f "${REPO_DIR}/.env.production.example" ]; then
    if [ "${DRY_RUN}" = "1" ]; then
        log_warn "[DRY-RUN] No .env found in ${APP_DIR}. Would provision template from .env.production.example"
    else
        log_warn "No .env found in ${APP_DIR}. Provisioning safe initial template from .env.production.example"
        /bin/cp "${REPO_DIR}/.env.production.example" "${APP_DIR}/.env"
        /bin/chmod 600 "${APP_DIR}/.env"
    fi
fi

# ==============================================================================
# 3. Synchronize Public Web Root Assets
# ==============================================================================
log_info "=== Synchronizing Public Document Root Assets ==="
if [ "${DRY_RUN}" = "1" ]; then
    log_info "[DRY-RUN] Would ensure public web directories exist: ${WEB_ROOT}/admin-assets, ${WEB_ROOT}/assets, ${WEB_ROOT}/build"
else
    /bin/mkdir -p "${WEB_ROOT}/admin-assets" "${WEB_ROOT}/assets" "${WEB_ROOT}/build"
fi

# Front controller, favicon, robots
PUBLIC_SINGLE_FILES=("index.php" "favicon.ico" "robots.txt")
for file in "${PUBLIC_SINGLE_FILES[@]}"; do
    if [ -f "${REPO_DIR}/public/${file}" ]; then
        if [ "${DRY_RUN}" = "1" ]; then
            log_info "[DRY-RUN] Would copy ${REPO_DIR}/public/${file} -> ${WEB_ROOT}/${file}"
        else
            /bin/cp "${REPO_DIR}/public/${file}" "${WEB_ROOT}/${file}"
        fi
    fi
done

# Frontend asset directories
PUBLIC_DIRS=("admin-assets" "assets" "build")
for dir in "${PUBLIC_DIRS[@]}"; do
    if [ -d "${REPO_DIR}/public/${dir}" ]; then
        if [ "${DRY_RUN}" = "1" ]; then
            log_info "[DRY-RUN] Would sync directory: ${REPO_DIR}/public/${dir} -> ${WEB_ROOT}/${dir}"
        else
            /bin/cp -R "${REPO_DIR}/public/${dir}/." "${WEB_ROOT}/${dir}/"
        fi
    fi
done

# Preserve existing server .htaccess (MultiPHP / CloudLinux handler)
if [ -f "${WEB_ROOT}/.htaccess" ]; then
    log_info "Preserving existing server-specific .htaccess (PHP handler protected)."
else
    if [ "${DRY_RUN}" = "1" ]; then
        log_info "[DRY-RUN] Would create initial ${WEB_ROOT}/.htaccess from public/.htaccess"
    else
        log_info "Creating initial ${WEB_ROOT}/.htaccess from public/.htaccess"
        /bin/cp "${REPO_DIR}/public/.htaccess" "${WEB_ROOT}/.htaccess"
    fi
fi

# ==============================================================================
# 4. Defensive Public Storage Symlink Verification & Repair
# ==============================================================================
log_info "=== Configuring Public Storage Symlink ==="
STORAGE_SRC="${APP_DIR}/storage/app/public"
STORAGE_DEST="${WEB_ROOT}/storage"

# Ensure storage source directory exists
if [ ! -d "${STORAGE_SRC}" ] && [ "${DRY_RUN}" = "0" ]; then
    /bin/mkdir -p "${STORAGE_SRC}"
fi

if [ -L "${STORAGE_DEST}" ]; then
    CURRENT_TARGET="$(readlink "${STORAGE_DEST}" || true)"
    if [ -d "${STORAGE_DEST}" ] && [ "${CURRENT_TARGET}" = "${STORAGE_SRC}" ]; then
        log_info "Storage symlink is healthy: ${STORAGE_DEST} -> ${STORAGE_SRC}"
    elif [ ! -e "${STORAGE_DEST}" ]; then
        # Broken/dangling symlink
        log_warn "Detected broken/dangling storage symlink pointing to non-existent target: ${CURRENT_TARGET}"
        if [ "${DRY_RUN}" = "1" ]; then
            log_info "[DRY-RUN] Would remove broken symlink and re-point to ${STORAGE_SRC}"
        else
            /bin/rm -f "${STORAGE_DEST}"
            /bin/ln -s "${STORAGE_SRC}" "${STORAGE_DEST}"
            log_info "Repaired broken storage symlink: ${STORAGE_DEST} -> ${STORAGE_SRC}"
        fi
    else
        log_warn "Storage symlink exists and points to alternate destination: ${CURRENT_TARGET}"
        log_info "Preserving existing valid symlink target."
    fi
elif [ -d "${STORAGE_DEST}" ]; then
    log_warn "Destination ${STORAGE_DEST} is a physical directory (preserving user media)."
elif [ ! -e "${STORAGE_DEST}" ]; then
    if [ "${DRY_RUN}" = "1" ]; then
        log_info "[DRY-RUN] Would create storage symlink: ${STORAGE_DEST} -> ${STORAGE_SRC}"
    else
        /bin/ln -s "${STORAGE_SRC}" "${STORAGE_DEST}"
        log_info "Created storage symlink: ${STORAGE_DEST} -> ${STORAGE_SRC}"
    fi
fi

# ==============================================================================
# 5. Apply Least-Privilege Permissions
# ==============================================================================
log_info "=== Applying Least-Privilege Permissions ==="
if [ "${DRY_RUN}" = "1" ]; then
    log_info "[DRY-RUN] Would set 755 on web root asset directories and 644 on public files."
    log_info "[DRY-RUN] Would set 755 on app storage/cache directories and 600 on .env."
else
    /bin/chmod 755 "${WEB_ROOT}/admin-assets" "${WEB_ROOT}/assets" "${WEB_ROOT}/build" 2>/dev/null || true
    /bin/chmod 644 "${WEB_ROOT}/index.php" "${WEB_ROOT}/favicon.ico" "${WEB_ROOT}/robots.txt" 2>/dev/null || true
    if [ -d "${APP_DIR}/storage" ]; then
        /bin/chmod -R 755 "${APP_DIR}/storage" 2>/dev/null || true
    fi
    if [ -d "${APP_DIR}/bootstrap/cache" ]; then
        /bin/chmod -R 755 "${APP_DIR}/bootstrap/cache" 2>/dev/null || true
    fi
    if [ -f "${APP_DIR}/.env" ]; then
        /bin/chmod 600 "${APP_DIR}/.env" 2>/dev/null || true
    fi
fi

# ==============================================================================
# 6. Record Deployed Commit SHA & Release Tracking
# ==============================================================================
COMMIT_SHA="unknown"
if command -v git >/dev/null 2>&1 && git -C "${REPO_DIR}" rev-parse HEAD >/dev/null 2>&1; then
    COMMIT_SHA="$(git -C "${REPO_DIR}" rev-parse HEAD)"
fi

if [ "${DRY_RUN}" = "1" ]; then
    log_info "[DRY-RUN] Would write commit SHA [${COMMIT_SHA}] to ${APP_DIR}/.deployed_commit and ${WEB_ROOT}/.deployed_commit"
else
    cat <<EOF > "${APP_DIR}/.deployed_commit"
COMMIT_SHA=${COMMIT_SHA}
DEPLOYED_AT=${TIMESTAMP}
DEPLOYED_BY=$(whoami 2>/dev/null || echo "cpanel")
REPO_SOURCE=${REPO_DIR}
EOF
    /bin/cp "${APP_DIR}/.deployed_commit" "${WEB_ROOT}/.deployed_commit"
    log_info "Recorded deployment commit SHA: ${COMMIT_SHA}"
fi

# ==============================================================================
# 7. Post-Deployment Verification Summary
# ==============================================================================
log_info "=== Post-Deployment Verification ==="
if [ "${DRY_RUN}" = "1" ]; then
    log_success "Dry run preview completed successfully. Zero files were modified."
else
    if [ -f "${WEB_ROOT}/index.php" ] && [ -f "${WEB_ROOT}/build/manifest.json" ]; then
        log_success "Deployment completed successfully! Live commit: ${COMMIT_SHA}"
    else
        log_error "Deployment completed with warnings: critical files missing."
        exit 1
    fi
fi

exit 0
