# Generating an .env file on the fly from cloud-injected environment variables
cat <<EOF > /app/.env
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
EOF

# Automatic database initialization using native psql client
if [ -n "$DB_HOST" ] && [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
    echo "Checking database state..."
    
    # We check if the games table already exists in the public schema
    TABLE_EXISTS=$(PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT TO_REGCLASS('public.games');" 2>/dev/null | tr -d '[:space:]')
    
    if [ -z "$TABLE_EXISTS" ] || [ "$TABLE_EXISTS" = "" ]; then
        echo "Database is empty. Importing structure and data from init.sql..."
        PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" -d "$DB_NAME" -f /app/docker/db/init/init.sql
        echo "Database initialization completed successfully."
    else
        echo "Database is already initialized."
    fi
fi

# Starting the main process (PHP-FPM)
exec "$@"