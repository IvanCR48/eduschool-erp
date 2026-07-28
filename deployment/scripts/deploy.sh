#!/bin/bash
# Script de despliegue automatizado para Sistema de Gestión Escolar

set -e

echo "🚀 Iniciando despliegue del Sistema de Gestión Escolar..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Verificar dependencias
check_dependencies() {
    log "Verificando dependencias..."
    
    if ! command -v docker &> /dev/null; then
        error "Docker no está instalado"
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose no está instalado"
    fi
    
    log "Dependencias verificadas ✓"
}

# Configurar variables de entorno
setup_environment() {
    log "Configurando variables de entorno..."
    
    if [ ! -f ".env" ]; then
        if [ -f "env.example" ]; then
            cp env.example .env
            warning "Archivo .env creado desde env.example. Por favor, edita las variables necesarias."
        else
            error "No se encontró archivo env.example"
        fi
    fi
    
    # Generar APP_KEY si no existe
    if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=your-secret-key-here" .env; then
        APP_KEY=$(openssl rand -base64 32)
        sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" .env
        log "APP_KEY generado automáticamente"
    fi
    
    log "Variables de entorno configuradas ✓"
}

# Construir y ejecutar contenedores
deploy_containers() {
    log "Construyendo y ejecutando contenedores..."
    
    # Detener contenedores existentes
    docker-compose down 2>/dev/null || true
    
    # Construir y ejecutar
    docker-compose up --build -d
    
    log "Contenedores desplegados ✓"
}

# Verificar salud del sistema
health_check() {
    log "Realizando verificación de salud..."
    
    # Esperar a que los servicios estén listos
    sleep 30
    
    # Verificar contenedores
    if ! docker-compose ps | grep -q "Up"; then
        error "Algunos contenedores no están ejecutándose"
    fi
    
    # Verificar base de datos
    if ! docker-compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD:-SecureRootPassword123!} -e "SELECT 1" > /dev/null 2>&1; then
        error "No se puede conectar a la base de datos"
    fi
    
    # Verificar aplicación web
    if ! curl -f http://localhost:${WEB_PORT:-8080}/health > /dev/null 2>&1; then
        error "La aplicación web no responde"
    fi
    
    log "Verificación de salud completada ✓"
}

# Configurar SSL (opcional)
setup_ssl() {
    if [ "$SETUP_SSL" = "true" ] && [ -n "$DOMAIN" ]; then
        log "Configurando SSL para dominio: $DOMAIN"
        
        # Instalar certbot si no existe
        if ! command -v certbot &> /dev/null; then
            apt-get update && apt-get install -y certbot
        fi
        
        # Generar certificado
        certbot certonly --webroot -w /var/www/html -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
        
        log "SSL configurado ✓"
    fi
}

# Configurar backup automático
setup_backup() {
    log "Configurando backup automático..."
    
    # Crear script de backup
    cat > /usr/local/bin/backup-sistema.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
mkdir -p $BACKUP_DIR

# Backup de base de datos
docker-compose exec -T database mysqldump -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} > $BACKUP_DIR/db_backup_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/logs /var/www/html/uploads

# Limpiar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

    chmod +x /usr/local/bin/backup-sistema.sh
    
    # Configurar cron job (preservar crontab existente)
    CRON_JOB="0 2 * * * /usr/local/bin/backup-sistema.sh"
    ( crontab -l 2>/dev/null | grep -v -F "$CRON_JOB"; echo "$CRON_JOB" ) | crontab -
    
    log "Backup automático configurado ✓"
}

# Configurar monitoreo
setup_monitoring() {
    log "Configurando monitoreo..."
    
    # Crear script de monitoreo
    cat > /usr/local/bin/monitor-sistema.sh << 'EOF'
#!/bin/bash
LOG_FILE="/var/log/sistema-monitor.log"

# Verificar contenedores
if ! docker-compose ps | grep -q "Up"; then
    echo "$(date): ERROR - Contenedores no están ejecutándose" >> $LOG_FILE
fi

# Verificar aplicación
if ! curl -f http://localhost:${WEB_PORT:-8080}/health > /dev/null 2>&1; then
    echo "$(date): ERROR - Aplicación no responde" >> $LOG_FILE
fi

# Verificar espacio en disco
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): WARNING - Uso de disco: ${DISK_USAGE}%" >> $LOG_FILE
fi
EOF

    chmod +x /usr/local/bin/monitor-sistema.sh
    
    # Configurar cron job (preservar crontab existente)
    CRON_JOB="*/5 * * * * /usr/local/bin/monitor-sistema.sh"
    ( crontab -l 2>/dev/null | grep -v -F "$CRON_JOB"; echo "$CRON_JOB" ) | crontab -
    
    log "Monitoreo configurado ✓"
}

# Función principal
main() {
    log "=== Sistema de Gestión Escolar - Despliegue Automatizado ==="
    
    check_dependencies
    setup_environment
    deploy_containers
    health_check
    
    if [ "$SETUP_SSL" = "true" ]; then
        setup_ssl
    fi
    
    if [ "$SETUP_BACKUP" = "true" ]; then
        setup_backup
    fi
    
    if [ "$SETUP_MONITORING" = "true" ]; then
        setup_monitoring
    fi
    
    log "=== Despliegue completado exitosamente ==="
    log "🌐 Aplicación disponible en: http://localhost:${WEB_PORT:-8080}"
    log "📊 Health check: http://localhost:${WEB_PORT:-8080}/health"
    log "📝 Logs: docker-compose logs -f"
    log "🛑 Detener: docker-compose down"
}

# Ejecutar función principal
main "$@"

