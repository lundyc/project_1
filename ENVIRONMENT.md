# Environment Variables Documentation

This document describes all environment variables used by the Analytics Desk application.

## Required Environment Variables

### Database Configuration
- `DB_HOST` - Database server hostname (default: `127.0.0.1`)
- `DB_PORT` - Database server port (default: `3306`)
- `DB_NAME` - Database name (default: `project_1`)
- `DB_USER` - Database username (default: `project_1`)
- `DB_PASS` - Database password (**REQUIRED** - no default)
- `DB_CHARSET` - Database character set (default: `utf8mb4`)

### Application Configuration
- `APP_ENV` - Application environment: `local`, `staging`, or `production` (default: `production`)
  - Controls error display behavior
  - Set to `local` to enable detailed error messages
- `APP_DEBUG` - Enable debug mode: `true` or `false` (default: `false`)

### Session Configuration
- `SESSION_NAME` - Session cookie name (default: `analytics_session`)
- `SESSION_LIFETIME` - Session lifetime in seconds (default: `86400` = 24 hours)
  - Set to `0` for "until browser closes"

## Optional Environment Variables

### Feature Flags
- `PHASE_3_VIDEO_LAB_ENABLED` - Enable video clip generation: `true` or `false` (default: from config)
- `ANNOTATIONS_ENABLED` - Enable video annotations: `true` or `false` (default: from config)

### Clip Worker (Python)
- `CLIP_WORKER_POLL_INTERVAL` - Job polling interval in seconds (default: `5`)
- `CLIP_WORKER_DRY_RUN` - Dry run mode: `1` or `0` (default: `0`)
- `CLIP_WORKER_ALLOW_OUTPUT_OVERRIDE` - Allow output path override: `1` or `0` (default: `0`)

## Setup Instructions

### Development Environment

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Update `.env` with your local credentials:
   ```dotenv
   DB_HOST=127.0.0.1
   DB_NAME=project_1
   DB_USER=project_1
   DB_PASS=your_database_password
   APP_ENV=local
   APP_DEBUG=true
   ```

3. Copy `config/config.example.php` to `config/config.php`:
   ```bash
   cp config/config.example.php config/config.php
   ```

4. Update `config/config.php` with your credentials (fallback for when env vars aren't set)

### Production Environment

1. Set environment variables in your web server configuration:

   **Apache (.htaccess or VirtualHost):**
   ```apache
   SetEnv DB_HOST "your-db-host"
   SetEnv DB_NAME "your-db-name"
   SetEnv DB_USER "your-db-user"
   SetEnv DB_PASS "your-secure-password"
   SetEnv APP_ENV "production"
   SetEnv APP_DEBUG "false"
   ```

   **Nginx (fastcgi_params or location block):**
   ```nginx
   fastcgi_param DB_HOST "your-db-host";
   fastcgi_param DB_NAME "your-db-name";
   fastcgi_param DB_USER "your-db-user";
   fastcgi_param DB_PASS "your-secure-password";
   fastcgi_param APP_ENV "production";
   fastcgi_param APP_DEBUG "false";
   ```

   **Systemd Service (for Python worker):**
   ```ini
   [Service]
   Environment="DB_HOST=your-db-host"
   Environment="DB_NAME=your-db-name"
   Environment="DB_USER=your-db-user"
   Environment="DB_PASS=your-secure-password"
   ```

2. **Never commit** `.env` or `config/config.php` to version control

3. Rotate credentials regularly and use strong passwords

## Security Best Practices

1. **Strong Passwords**: Use passwords with 20+ characters, mixed case, numbers, and symbols
2. **Environment Separation**: Use different credentials for development, staging, and production
3. **Least Privilege**: Grant database users only the permissions they need
4. **Regular Rotation**: Change database passwords every 90 days
5. **Backup Protection**: Ensure backups are encrypted and stored securely
6. **Access Logging**: Monitor database access logs for suspicious activity

## Troubleshooting

### Database Connection Fails
- Verify `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` are correct
- Check database server is running: `systemctl status mysql`
- Verify user has access: `mysql -u DB_USER -p -e "SHOW DATABASES;"`

### Environment Variables Not Loading
- **PHP**: Check `phpinfo()` for loaded environment variables
- **Apache**: Verify `AllowOverride All` is set for .htaccess files
- **Nginx**: Ensure `fastcgi_param` directives are in correct location block
- **CLI**: Source `.env` manually or use `export $(cat .env | xargs)`

### Sessions Not Working
- Check session cookie flags in browser DevTools
- Verify `SESSION_NAME` doesn't conflict with other applications
- Ensure web server has write access to session directory
- Check `session.save_path` in `php.ini`

## Migration from Old Config

If upgrading from hardcoded credentials in `config.php`:

1. Extract credentials from `config/config.php`
2. Add them to `.env` file
3. Verify application still works
4. Remove hardcoded credentials from `config/config.php`
5. Add `config/config.php` to `.gitignore`
6. Commit changes (credentials are now safe)
