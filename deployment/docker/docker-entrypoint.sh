#!/bin/bash
# Script de entrada para el contenedor Docker

set -e

echo "🚀 Iniciando Sistema de Gestión Escolar..."

# Función para logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Esperar a que la base de datos esté lista
wait_for_db() {
    log "Esperando conexión a la base de datos..."
    while ! mysql -h database -u ${DB_USER} -p${DB_PASS} -e "SELECT 1" > /dev/null 2>&1; do
        log "Esperando base de datos..."
        sleep 2
    done
    log "Base de datos conectada ✓"
}

# Configurar permisos
setup_permissions() {
    log "Configurando permisos..."
    chown -R www:www /var/www/html
    chmod -R 755 /var/www/html
    chmod -R 777 /var/www/html/logs
    chmod -R 777 /var/www/html/backups
    chmod -R 777 /var/www/html/uploads
    log "Permisos configurados ✓"
}

# Crear directorios necesarios
create_directories() {
    log "Creando directorios necesarios..."
    mkdir -p /var/www/html/logs
    mkdir -p /var/www/html/backups
    mkdir -p /var/www/html/uploads
    mkdir -p /var/log/nginx
    mkdir -p /var/log/supervisor
    log "Directorios creados ✓"
}

# Configurar base de datos si es necesario
setup_database() {
    log "Verificando base de datos..."
    
    # Verificar si las tablas existen
    if ! mysql -h database -u ${DB_USER} -p${DB_PASS} ${DB_NAME} -e "SHOW TABLES" | grep -q "usuarios"; then
        log "Inicializando base de datos..."
        mysql -h database -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < /var/www/html/database/sistema_completo.sql
        log "Base de datos inicializada ✓"
    else
        log "Base de datos ya inicializada ✓"
    fi
}

# Configurar SSL si está disponible
setup_ssl() {
    if [ -f "/etc/nginx/ssl/cert.pem" ] && [ -f "/etc/nginx/ssl/key.pem" ]; then
        log "Configurando SSL..."
        # SSL ya configurado en nginx.conf
        log "SSL configurado ✓"
    else
        log "SSL no configurado (usando HTTP)"
    fi
}

# Función principal
main() {
    log "=== Sistema de Gestión Escolar - Inicialización ==="
    
    create_directories
    setup_permissions
    wait_for_db
    setup_database
    setup_ssl
    
    log "=== Inicialización completada ==="
    log "🌐 Sistema disponible en puerto 80"
    log "📊 Health check: /health"
    
    # Ejecutar comando principal
    exec "$@"
}

# Ejecutar función principal
main "$@"

