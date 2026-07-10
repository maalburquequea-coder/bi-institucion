#!/bin/bash
set -e

# Generar .env desde las variables de entorno de Render
cat > /var/www/html/.env <<EOF
DATABASE_URL=${DATABASE_URL}
SMTP_PASS=${SMTP_PASS}
GEMINI_API_KEY=${GEMINI_API_KEY}
BASE_URL=${BASE_URL}
APP_NAME="${APP_NAME:-BI Educativo}"
EOF

# Asegurar permisos de uploads
chown -R www-data:www-data /var/www/html/uploads/
chmod -R 755 /var/www/html/uploads/

exec apache2-foreground
