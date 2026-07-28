#!/bin/bash
# Script de backup para Sistema de Gestión Escolar

set -e

# Configuración
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

# Crear directorio de backup
mkdir -p $BACKUP_DIR

log "Iniciando backup del Sistema de Gestión Escolar..."

# Backup de base de datos
log "Realizando backup de base de datos..."
if docker-compose exec -T database mysqldump -u root -p${DB_ROOT_PASSWORD:-SecureRootPassword123!} ${DB_NAME:-school_admin} > $BACKUP_DIR/db_backup_$DATE.sql; then
    log "Backup de base de datos completado: db_backup_$DATE.sql"
else
    error "Error en backup de base de datos"
fi

# Backup de archivos importantes
log "Realizando backup de archivos..."
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz \
    logs/ \
    uploads/ \
    config/ \
    .env \
    --exclude="*.log" \
    --exclude="*.tmp"

if [ $? -eq 0 ]; then
    log "Backup de archivos completado: files_backup_$DATE.tar.gz"
else
    error "Error en backup de archivos"
fi

# Backup completo del sistema
log "Realizando backup completo..."
tar -czf $BACKUP_DIR/full_backup_$DATE.tar.gz \
    --exclude="node_modules" \
    --exclude=".git" \
    --exclude="backups" \
    --exclude="vendor" \
    .

if [ $? -eq 0 ]; then
    log "Backup completo realizado: full_backup_$DATE.tar.gz"
else
    error "Error en backup completo"
fi

# Limpiar backups antiguos
log "Limpiando backups antiguos (más de $RETENTION_DAYS días)..."
find $BACKUP_DIR -name "*.sql" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Mostrar información de backups
log "Backups actuales:"
ls -lh $BACKUP_DIR/

log "Backup completado exitosamente!"

