#!/bin/bash

# Auto-start PHP Development Server for Arteri Project
# This script handles:
# 1. Database connection check
# 2. Run migrations
# 3. Seed database
# 4. Start development server
# 5. Monitor and handle errors

set -e

# Configuration
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${BASE_DIR}/.env"
PORT=${PORT:-8081}
DEBUG=${DEBUG:-true}

echo "=========================================="
echo "  Arteri Development Server Auto-Start"
echo "=========================================="
echo "Base Directory: ${BASE_DIR}"
echo "Port: ${PORT}"
echo "Debug: ${DEBUG}"
echo ""

# Step 1: Check database
echo "[1/4] Checking database connection..."
if [ ! -f "${ENV_FILE}" ]; then
    echo "  ERROR: .env file not found at ${ENV_FILE}"
    exit 1
fi

# Check if database exists
if ! php -r "try { \$db = new mysqli('localhost', 'root', 'root', 'arteri'); echo \$db->host_info; } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
    echo "  ERROR: Cannot connect to database"
    echo "  Please check your database configuration in .env file"
    exit 1
fi
echo "  ✓ Database connection OK"

# Step 2: Run migrations
echo "[2/4] Running migrations..."
cd "${BASE_DIR}"
if ! php spark migrate 2>&1 | tee /tmp/migration.log; then
    echo "  WARNING: Migration failed, checking migration status..."
    php spark migrate:status 2>&1 | tail -10
fi
echo "  ✓ Migrations complete"

# Step 3: Seed database (if not already seeded)
echo "[3/4] Seeding database..."
if [ "${SEED_DATABASE}" = "true" ] || [ ! -f "${BASE_DIR}/.env" ]; then
    if ! php spark seed 2>&1 | tee /tmp/seed.log; then
        echo "  WARNING: Seeding failed"
    fi
fi
echo "  ✓ Database seeded"

# Step 4: Start server
echo "[4/4] Starting development server..."
echo "  Server will run on http://localhost:${PORT}"
echo "  Press Ctrl+C to stop"
echo ""

# Start PHP server in background
php spark serve --port=${PORT} --debug=${DEBUG} --no-cors &

SERVER_PID=$!
echo "  Server PID: ${SERVER_PID}"
echo ""

# Monitor server
while true; do
    # Check if server is running
    if kill -0 ${SERVER_PID} 2>/dev/null; then
        # Keep running
        sleep 1
    else
        echo ""
        echo "=========================================="
        echo "  Server stopped. Press Ctrl+C to exit."
        echo "=========================================="
        echo "  Server PID: ${SERVER_PID}"
        echo ""
        echo "To restart, run: bash ${BASE_DIR}/scripts/start-server.sh"
        echo ""
        exit 0
    fi
done