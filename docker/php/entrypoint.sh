# Generating an .env file on the fly from cloud-injected environment variables
cat <<EOF > /app/.env
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
EOF

# Starting the main process (PHP-FPM)
exec "$@"