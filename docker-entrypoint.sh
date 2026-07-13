#!/bin/bash
set -e

# Generar .env desde las variables de entorno de Render
cat > /var/www/html/.env <<EOF
DATABASE_URL=${DATABASE_URL}
SMTP_HOST=${SMTP_HOST:-smtp.gmail.com}
SMTP_PORT=${SMTP_PORT:-587}
SMTP_SECURE=${SMTP_SECURE:-tls}
SMTP_USER=${SMTP_USER}
SMTP_PASS=${SMTP_PASS}
MAIL_FROM=${MAIL_FROM}
GEMINI_API_KEY=${GEMINI_API_KEY}
BASE_URL=${BASE_URL}
APP_NAME="${APP_NAME:-BI Educativo}"
EOF

# Importar schema y datos si la BD está vacía
if [ -n "$DATABASE_URL" ]; then
    TABLES=$(psql "$DATABASE_URL" -t -c \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='roles';" \
        2>/dev/null | tr -d ' \n' || echo "0")
    if [ "$TABLES" = "0" ] || [ -z "$TABLES" ]; then
        echo "[entrypoint] Importando schema PostgreSQL..."
        psql "$DATABASE_URL" -f /var/www/html/database/schema_pg.sql
        echo "[entrypoint] Importando datos..."
        psql "$DATABASE_URL" -f /var/www/html/database/data_pg.sql
        echo "[entrypoint] Base de datos lista."
    else
        echo "[entrypoint] Schema ya existe, omitiendo importacion."
    fi
fi

# Asegurar permisos de uploads
chown -R www-data:www-data /var/www/html/uploads/
chmod -R 755 /var/www/html/uploads/

exec apache2-foreground
