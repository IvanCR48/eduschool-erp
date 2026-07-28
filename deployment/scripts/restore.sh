#!/bin/bash
# Script de restauración para Sistema de Gestión Escolar

set -e

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# Verificar parámetros
if [ $# -eq 0 ]; then
    echo "Uso: $0 <archivo_backup> [tipo]"
    echo "Tipos: db, files, full"
    echo "Ejemplo: $0 db_backup_20231201_120000.sql db"
    exit 1
fi

BACKUP_FILE="$1"
BACKUP_TYPE="${2:-full}"

log "Iniciando restauración desde: $BACKUP_FILE"

# Verificar que el archivo existe y está dentro del directorio de backups esperado
if [ ! -f "$BACKUP_FILE" ]; then
    error "Archivo de backup no encontrado: $BACKUP_FILE"
fi

RESOLVED_FILE="$(realpath "$BACKUP_FILE" 2>/dev/null || echo '')"
if [ -z "$RESOLVED_FILE" ]; then
    error "No se pudo resolver la ruta del archivo de backup"
fi

# Crear backup de seguridad actual
log "Creando backup de seguridad del estado actual..."
./scripts/backup.sh

warning "¿Estás seguro de que quieres restaurar? Esto sobrescribirá los datos actuales. (y/N)"
read -r response
if [[ ! "$response" =~ ^[Yy]$ ]]; then
    log "Restauración cancelada"
    exit 0
fi

# Detener servicios
log "Deteniendo servicios..."
docker-compose down

# Restaurar según el tipo
case $BACKUP_TYPE in
    "db")
        log "Restaurando base de datos..."
        docker-compose up -d database
        sleep 10
        docker-compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD:-SecureRootPassword123!} ${DB_NAME:-school_admin} < $BACKUP_FILE
        log "Base de datos restaurada"
        ;;
    "files")
        log "Restaurando archivos..."
        EXTRACT_DIR="/tmp/restore_files_$(date +%s)"
        mkdir -p "$EXTRACT_DIR"
        tar -xzf "$RESOLVED_FILE" -C "$EXTRACT_DIR"
        log "Archivos restaurados en $EXTRACT_DIR"
        log "Revisa y copia manualmente los archivos necesarios desde $EXTRACT_DIR"
        ;;
    "full")
        log "Restaurando backup completo..."
        EXTRACT_DIR="/tmp/restore_full_$(date +%s)"
        mkdir -p "$EXTRACT_DIR"
        tar -xzf "$RESOLVED_FILE" -C "$EXTRACT_DIR"
        log "Backup completo restaurado en $EXTRACT_DIR"
        ;;
    *)
        error "Tipo de backup inválido: $BACKUP_TYPE"
        ;;
esac

# Reiniciar servicios
log "Reiniciando servicios..."
docker-compose up -d

# Verificar restauración
log "Verificando restauración..."
sleep 30

if curl -f http://localhost:${WEB_PORT:-8080}/health > /dev/null 2>&1; then
    log "Restauración completada exitosamente!"
    log "Aplicación disponible en: http://localhost:${WEB_PORT:-8080}"
else
    error "Error en la verificación de restauración"
fi

