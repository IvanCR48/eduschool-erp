#!/bin/bash
# Script de despliegue en la nube para diferentes plataformas

set -e

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Función para mostrar ayuda
show_help() {
    echo "Uso: $0 <plataforma> [opciones]"
    echo ""
    echo "Plataformas disponibles:"
    echo "  railway    - Desplegar en Railway"
    echo "  render     - Desplegar en Render"
    echo "  heroku     - Desplegar en Heroku"
    echo "  fly        - Desplegar en Fly.io"
    echo "  digitalocean - Desplegar en DigitalOcean"
    echo ""
    echo "Ejemplos:"
    echo "  $0 railway"
    echo "  $0 render --domain mi-sistema.edu.ar"
    echo "  $0 heroku --app mi-sistema-admin"
}

# Desplegar en Railway
deploy_railway() {
    log "Desplegando en Railway..."
    
    if ! command -v railway &> /dev/null; then
        warning "Railway CLI no está instalado. Instalando..."
        npm install -g @railway/cli
    fi
    
    railway login
    railway init
    
    # Configurar variables de entorno
    railway variables set APP_ENV=production
    railway variables set APP_DEBUG=false
    railway variables set DB_NAME=school_admin
    
    railway up
    
    info "Despliegue completado en Railway"
    info "Tu aplicación estará disponible en: https://tu-app.railway.app"
}

# Desplegar en Render
deploy_render() {
    log "Desplegando en Render..."
    
    if [ ! -f "render.yaml" ]; then
        error "render.yaml no encontrado"
    fi
    
    info "Render requiere configuración manual:"
    info "1. Ve a https://render.com"
    info "2. Conecta tu repositorio GitHub"
    info "3. Selecciona 'Web Service'"
    info "4. Usa el archivo render.yaml para configuración"
    
    if [ -n "$DOMAIN" ]; then
        info "5. Configura el dominio personalizado: $DOMAIN"
    fi
}

# Desplegar en Heroku
deploy_heroku() {
    log "Desplegando en Heroku..."
    
    if ! command -v heroku &> /dev/null; then
        warning "Heroku CLI no está instalado. Instalando..."
        # Instrucciones de instalación para diferentes sistemas operativos
        info "Por favor, instala Heroku CLI desde: https://devcenter.heroku.com/articles/heroku-cli"
        exit 1
    fi
    
    # Crear aplicación si no existe
    if [ -n "$APP_NAME" ]; then
        heroku create $APP_NAME
    else
        heroku create
    fi
    
    # Configurar stack para contenedores
    heroku stack:set container
    
    # Configurar variables de entorno
    heroku config:set APP_ENV=production
    heroku config:set APP_DEBUG=false
    
    # Desplegar
    git push heroku main
    
    info "Despliegue completado en Heroku"
}

# Desplegar en Fly.io
deploy_fly() {
    log "Desplegando en Fly.io..."
    
    if ! command -v fly &> /dev/null; then
        warning "Fly CLI no está instalado. Instalando..."
        # Instrucciones de instalación
        info "Por favor, instala Fly CLI desde: https://fly.io/docs/hands-on/install-flyctl/"
        exit 1
    fi
    
    fly auth login
    fly launch --no-deploy
    
    # Configurar variables de entorno
    fly secrets set APP_ENV=production
    fly secrets set APP_DEBUG=false
    
    fly deploy
    
    info "Despliegue completado en Fly.io"
}

# Desplegar en DigitalOcean
deploy_digitalocean() {
    log "Configurando para DigitalOcean..."
    
    if [ ! -f ".do/app.yaml" ]; then
        error "Archivo .do/app.yaml no encontrado"
    fi
    
    info "DigitalOcean App Platform requiere configuración manual:"
    info "1. Ve a https://cloud.digitalocean.com/apps"
    info "2. Crea una nueva aplicación"
    info "3. Conecta tu repositorio GitHub"
    info "4. Usa el archivo .do/app.yaml para configuración"
}

# Función principal
main() {
    if [ $# -eq 0 ]; then
        show_help
        exit 1
    fi
    
    PLATFORM=$1
    shift
    
    # Parsear argumentos adicionales
    while [[ $# -gt 0 ]]; do
        case $1 in
            --domain)
                DOMAIN="$2"
                shift 2
                ;;
            --app)
                APP_NAME="$2"
                shift 2
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                warning "Argumento desconocido: $1"
                shift
                ;;
        esac
    done
    
    case $PLATFORM in
        "railway")
            deploy_railway
            ;;
        "render")
            deploy_render
            ;;
        "heroku")
            deploy_heroku
            ;;
        "fly")
            deploy_fly
            ;;
        "digitalocean")
            deploy_digitalocean
            ;;
        *)
            error "Plataforma no soportada: $PLATFORM"
            show_help
            exit 1
            ;;
    esac
}

# Ejecutar función principal
main "$@"

