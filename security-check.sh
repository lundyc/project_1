#!/bin/bash
# Security Quick Check Script
# Run this to verify security fixes are properly applied

echo "🔒 Analytics Desk Security Verification"
echo "========================================"
echo ""

# Check 1: Environment Configuration
echo "✓ Checking environment configuration..."
if [ -f ".env" ]; then
    if grep -q "YOUR_STRONG" .env; then
        echo "  ❌ WARNING: Default passwords found in .env file!"
    else
        echo "  ✅ .env file configured"
    fi
else
    echo "  ⚠️  No .env file found (using config.php fallback)"
fi

# Check 2: Production Mode
echo ""
echo "✓ Checking production mode..."
APP_ENV=$(grep -r "APP_ENV" .env 2>/dev/null | cut -d'=' -f2)
if [ "$APP_ENV" = "production" ]; then
    echo "  ✅ APP_ENV set to production"
else
    echo "  ⚠️  APP_ENV not set to production (current: ${APP_ENV:-not set})"
fi

# Check 3: Config File Security
echo ""
echo "✓ Checking config.php..."
if grep -q "D&DNdtopmoff7!29" config/config.php; then
    echo "  ❌ CRITICAL: Old password still in config.php!"
else
    echo "  ✅ No hardcoded credentials found"
fi

# Check 4: WebSocket Secret
echo ""
echo "✓ Checking WebSocket configuration..."
if [ -f "scripts/match-session-server.js" ]; then
    WS_SECRET=$(grep -r "MATCH_SESSION_SECRET" .env 2>/dev/null | cut -d'=' -f2)
    if [ -z "$WS_SECRET" ] || [ "$WS_SECRET" = "YOUR_STRONG_WEBSOCKET_SECRET_HERE" ]; then
        echo "  ⚠️  WebSocket secret not configured"
    else
        echo "  ✅ WebSocket secret configured"
    fi
else
    echo "  ℹ️  WebSocket server disabled (*.js.disabled)"
fi

# Check 5: File Permissions
echo ""
echo "✓ Checking file permissions..."
if [ -f "config/config.php" ]; then
    PERMS=$(stat -c "%a" config/config.php 2>/dev/null || stat -f "%p" config/config.php 2>/dev/null)
    if [ "$PERMS" = "600" ] || [ "$PERMS" = "640" ]; then
        echo "  ✅ config.php permissions secure"
    else
        echo "  ⚠️  config.php permissions: $PERMS (recommended: 600 or 640)"
    fi
fi

# Check 6: Database Indexes
echo ""
echo "✓ Checking database indexes..."
DB_USER=$(grep -r "DB_USER" .env 2>/dev/null | cut -d'=' -f2)
DB_NAME=$(grep -r "DB_NAME" .env 2>/dev/null | cut -d'=' -f2)
DB_PASS=$(grep -r "DB_PASS" .env 2>/dev/null | cut -d'=' -f2)

if [ ! -z "$DB_USER" ] && [ ! -z "$DB_NAME" ] && [ ! -z "$DB_PASS" ]; then
    # Check if we can connect using .env password
    echo "  Checking for performance indexes..."
    INDEXES=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW INDEX FROM events WHERE Key_name = 'idx_events_match_second'" 2>/dev/null | wc -l)
    if [ "$INDEXES" -gt 1 ]; then
        echo "  ✅ Performance indexes applied"
    else
        echo "  ⚠️  Performance indexes not found. Run: mysql < sql/04-02-2026\\ -\\ performance-indexes.sql"
    fi
else
    echo "  ℹ️  Database credentials not in .env, skipping index check"
fi

# Check 7: Rate Limit Table
echo ""
echo "✓ Checking rate_limit_attempts table..."
if [ ! -z "$DB_USER" ] && [ ! -z "$DB_NAME" ] && [ ! -z "$DB_PASS" ]; then
    TABLE_EXISTS=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'rate_limit_attempts'" 2>/dev/null | wc -l)
    if [ "$TABLE_EXISTS" -gt 1 ]; then
        echo "  ✅ Rate limiting table exists"
    else
        echo "  ❌ rate_limit_attempts table missing!"
    fi
fi

# Check 8: Log Directory Permissions
echo ""
echo "✓ Checking log directory..."
if [ -d "storage/logs" ]; then
    if [ -w "storage/logs" ]; then
        echo "  ✅ Log directory writable"
    else
        echo "  ❌ Log directory not writable!"
    fi
else
    echo "  ❌ Log directory missing!"
fi

# Check 9: Git Security
echo ""
echo "✓ Checking git configuration..."
if git check-ignore config/config.php > /dev/null 2>&1; then
    echo "  ✅ config.php ignored by git"
else
    echo "  ⚠️  config.php NOT ignored by git!"
fi

if git check-ignore .env > /dev/null 2>&1; then
    echo "  ✅ .env ignored by git"
else
    echo "  ⚠️  .env NOT ignored by git!"
fi

# Check 10: Security Headers
echo ""
echo "✓ Checking security headers implementation..."
if grep -q "get_csp_nonce" app/lib/security_headers.php; then
    echo "  ✅ CSP nonce implementation found"
else
    echo "  ❌ CSP nonce implementation missing!"
fi

# Summary
echo ""
echo "========================================"
echo "Security Check Complete"
echo ""
echo "Next steps:"
echo "1. Review any ⚠️  warnings above"
echo "2. Fix any ❌ critical issues immediately"
echo "3. Apply database migrations if needed"
echo "4. Rotate database password if old credential found"
echo "5. Configure .env with strong secrets"
echo ""
echo "For detailed fixes, see: SECURITY_FIXES_2026-02-04.md"
