# 🚀 Despliegue - Documento Unificado

Este documento consolida la guía de despliegue del proyecto.

## Índice
- Requisitos y extensiones
- Configuración local (con y sin Docker)
- Variables de entorno
- Despliegue en la nube (Railway, Render, Heroku, Fly.io)
- Seguridad en despliegue (SSL, firewall, backups)
- Monitoreo/Mantenimiento y Troubleshooting

---

## 1) Requisitos
- PHP 8.1+, MySQL 8+, RAM ≥ 1GB recomendada
- Extensiones: pdo_mysql, mbstring, openssl, json, curl, zip, gd

## 2) Configuración Local
- Docker: `docker-compose up --build -d` y revisar `docker-compose.yml`
- Manual: `composer install --no-dev --optimize-autoloader`, importar BD, permisos mínimos

## 3) Variables de Entorno (ejemplo)
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sistema_admin_eest2
DB_USER=usuario
DB_PASS=seguro
APP_ENV=production
APP_DEBUG=false
```

## 4) Despliegue en la Nube
- Railway (inicial): CLI, variables, `railway up`
- Render/Fly.io/Heroku: usar Dockerfile y configurar env vars/health checks

## 5) Seguridad en Despliegue
- SSL/TLS con Let’s Encrypt (certbot) y HSTS
- Firewall (80/443/22), Fail2ban
- Backups automáticos y prueba de restauración

## 6) Monitoreo y Mantenimiento
- Health checks, logs de app/sistema, actualizaciones periódicas
- Troubleshooting: conexión BD, errores 500, SSL

---

Nota: Mantener este documento como fuente única para despliegue. Actualizar aquí nuevos proveedores/pasos.


