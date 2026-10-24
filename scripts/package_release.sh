#!/usr/bin/env bash
# ==============================================================================
# MediCare Hospital Management System — Release Packaging Script
# ==============================================================================
# Usage:
#   ./scripts/package_release.sh [OPTIONS]
#
# Options:
#   -o, --output-dir DIR   Directory to write release archive (default: /tmp)
#   -n, --name NAME        Archive base name (default: medicare-hms-deploy)
#   --skip-build           Skip npm run build (requires pre-compiled assets)
#   -h, --help             Display this help message
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

OUTPUT_DIR="/tmp"
ARCHIVE_NAME="medicare-hms-deploy"
SKIP_BUILD=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        -o|--output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        -n|--name)
            ARCHIVE_NAME="$2"
            shift 2
            ;;
        --skip-build)
            SKIP_BUILD=true
            shift 1
            ;;
        -h|--help)
            echo "Usage: $0 [-o output_dir] [-n archive_name] [--skip-build]"
            exit 0
            ;;
        *)
            echo "ERROR: Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

cd "${PROJECT_ROOT}"

echo "=== MediCare HMS Release Packaging ==="
echo "Project root: ${PROJECT_ROOT}"

# Step 1: Build production assets if not skipped
if [[ "${SKIP_BUILD}" != "true" ]]; then
    echo "Compiling production frontend assets..."
    if command -v npm &> /dev/null; then
        npm run build
    else
        echo "ERROR: npm is not available on PATH to build assets. Use --skip-build if already built." >&2
        exit 1
    fi
fi

# Step 2: Validate asset build presence
if [[ ! -f "${PROJECT_ROOT}/public/build/manifest.json" ]]; then
    echo "ERROR: Missing compiled assets in public/build/manifest.json." >&2
    echo "Run 'npm run build' before packaging." >&2
    exit 1
fi

mkdir -p "${OUTPUT_DIR}"
TIMESTAMP="$(date +"%Y%m%d_%H%M%S")"
ARCHIVE_PATH="${OUTPUT_DIR}/${ARCHIVE_NAME}_${TIMESTAMP}.tar.gz"

echo "Creating release archive: ${ARCHIVE_PATH}..."

# Step 3: Create tar archive with strict exclusion of secrets, VCS, and dev artifacts
tar --create \
    --gzip \
    --file="${ARCHIVE_PATH}" \
    --exclude='.git*' \
    --exclude='.env' \
    --exclude='.env.production' \
    --exclude='.env.local' \
    --exclude='.env.testing' \
    --exclude='.env.backup*' \
    --exclude='.env.*.local' \
    --exclude-ignore-recursive='.gitignore' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='phpunit*.xml' \
    --exclude='.phpunit.result.cache' \
    --exclude='storage/app/backups*' \
    --exclude='storage/app/dist*' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='*.tar.gz' \
    --exclude='*.zip' \
    --exclude='*.pem' \
    --exclude='*.key' \
    app \
    bootstrap \
    config \
    database \
    public \
    resources \
    routes \
    scripts \
    storage \
    artisan \
    composer.json \
    composer.lock \
    package.json \
    package-lock.json \
    .env.production.example \
    DEPLOYMENT_RUNBOOK.md \
    ROADMAP.md \
    README.md

# Step 4: Verify archive contents
echo "Verifying archive security and completeness..."
ARCHIVE_ENTRIES="$(tar -tzf "${ARCHIVE_PATH}")"

# Forbidden patterns check
FORBIDDEN_PATTERNS=(
    "\.env$"
    "\.env\.local"
    "\.git/"
    "node_modules/"
    "^tests/"
    "phpunit\.xml"
    "storage/logs/.*\.log$"
    "\.pem$"
    "\.key$"
)

for pattern in "${FORBIDDEN_PATTERNS[@]}"; do
    if echo "${ARCHIVE_ENTRIES}" | grep -qE "${pattern}"; then
        echo "ERROR: Security violation! Forbidden pattern '${pattern}' detected in package archive:" >&2
        echo "${ARCHIVE_ENTRIES}" | grep -E "${pattern}" >&2
        rm -f "${ARCHIVE_PATH}"
        exit 1
    fi
done

# Required files check
REQUIRED_FILES=(
    "artisan"
    "public/index.php"
    "public/build/manifest.json"
    "bootstrap/app.php"
    ".env.production.example"
    "DEPLOYMENT_RUNBOOK.md"
)

for req in "${REQUIRED_FILES[@]}"; do
    if ! echo "${ARCHIVE_ENTRIES}" | grep -qF "${req}"; then
        echo "ERROR: Verification failed! Required file '${req}' is missing from package archive." >&2
        rm -f "${ARCHIVE_PATH}"
        exit 1
    fi
done

# Enforce secure permissions on the generated archive (chmod 600)
chmod 600 "${ARCHIVE_PATH}"

ARCHIVE_SIZE="$(du -h "${ARCHIVE_PATH}" | cut -f1)"
ARCHIVE_SHA="$(sha256sum "${ARCHIVE_PATH}" | cut -d ' ' -f1)"

echo "=== Release Archive Verified Successfully ==="
echo "Path:   ${ARCHIVE_PATH}"
echo "Size:   ${ARCHIVE_SIZE}"
echo "SHA256: ${ARCHIVE_SHA}"
echo "Status: Package meets Truehost and cPanel deployment specifications."
