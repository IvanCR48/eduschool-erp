# 🚀 Guía de Despliegue — Sistema de Gestión Escolar
### Servidor de producción: `tecnica2.cabrasoft.ong`

> [!IMPORTANT]
> Completá esta guía **en orden**. Cada sección depende de la anterior.

---

## 1. Requisitos del servidor Linux

Antes de empezar, verificá que el servidor tenga instalado:

| Componente | Versión mínima | Comando para verificar |
|---|---|---|
| PHP | 8.1+ | `php -v` |
| MySQL / MariaDB | 8.0+ | `mysql --version` |
| Apache o Nginx | cualquiera | `apache2 -v` o `nginx -v` |
| Composer | 2.x | `composer --version` |
| OpenSSL | cualquiera | `openssl version` |

Si falta algo en Debian/Ubuntu:
```bash
sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-gd php8.1-curl php8.1-zip \
     mysql-server apache2 composer unzip -y
```

---

## 2. Archivos a subir al servidor

### ✅ Subir estos archivos/carpetas
Subí **todo el contenido** de `c:\xampp\htdocs\SistemaAdmin\` al servidor, **excepto** lo que está en la sección siguiente.

Método recomendado:
```bash
# Desde tu PC, usando SCP o rsync:
rsync -avz --exclude='.env' --exclude='vendor/' \
      c:/xampp/htdocs/SistemaAdmin/ usuario@tecnica2.cabrasoft.org:/var/www/html/
```

### ❌ NO subir estos archivos
| Archivo / Carpeta | Motivo |
|---|---|
| `.env` | No existe localmente; se crea en el servidor |
| `vendor/` | Se regenera con Composer en el servidor |

> [!CAUTION]
> `config/google_oauth.local.php` está en `.gitignore` (no se sube con Git).
> **Subilo manualmente** por SCP o FTP — contiene las credenciales OAuth reales.

---

## 3. Configurar el entorno en el servidor

Una vez que los archivos estén en el servidor, conectate por SSH:
```bash
ssh usuario@tecnica2.cabrasoft.org
cd /var/www/html
```

### 3.1 Crear el archivo `.env`
```bash
cp env.example .env
nano .env
```

Editá los siguientes valores con los datos reales de tu servidor:

```bash
# Base de datos
DB_HOST=localhost           # o la IP de tu servidor MySQL
DB_PORT=3306
DB_NAME=school_admin
DB_USER=tu_usuario_mysql    # ← cambiá esto
DB_PASS=tu_contraseña       # ← cambiá esto

# Aplicación
APP_ENV=production
APP_DEBUG=false
APP_KEY=                    # ← ver sección 3.2

# Rutas (ya configuradas correctamente)
APP_BASE_PATH=/
APP_URL=https://tecnica2.cabrasoft.org

# Seguridad
SESSION_LIFETIME=120
MAX_LOGIN_ATTEMPTS=5
BACKUP_ENCRYPTION_KEY=      # ← ver sección 3.2
BACKUP_DOWNLOAD_SECRET=     # ← ver sección 3.2
```

### 3.2 Generar claves secretas
```bash
# APP_KEY
echo "APP_KEY=$(openssl rand -base64 32)" >> .env

# Claves de backup
echo "BACKUP_ENCRYPTION_KEY=$(openssl rand -base64 32)" >> .env
echo "BACKUP_DOWNLOAD_SECRET=$(openssl rand -base64 32)" >> .env
```

O generá cada valor por separado y pegalo en el `.env`:
```bash
openssl rand -base64 32
```

---

## 4. Instalar dependencias de Composer

```bash
cd /var/www/html
composer install --no-dev --optimize-autoloader
```

---

## 5. Importar la base de datos

### 5.1 Crear el usuario y la base de datos en MySQL
```bash
mysql -u root -p
```
```sql
CREATE DATABASE IF NOT EXISTS school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tu_usuario_mysql'@'localhost' IDENTIFIED BY 'tu_contraseña';
GRANT ALL PRIVILEGES ON school_admin.* TO 'tu_usuario_mysql'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.2 Importar el esquema
```bash
mysql -u tu_usuario_mysql -p school_admin < database/school_admin.sql
```

Si el archivo es grande y tarda:
```bash
mysql -u tu_usuario_mysql -p --max_allowed_packet=256M school_admin < database/school_admin.sql
```

---

## 6. Configurar permisos de archivos

Ejecutá el script de instalación (ya está actualizado para hacer todo automáticamente):
```bash
bash install.sh
```

O manualmente si preferís:
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
find . -name '*.sh' -exec chmod +x {} \;
chmod -R 775 logs/ backups/ public/logs/ admin/logs/ uploads/
sudo chown -R www-data:www-data logs/ backups/ public/logs/ admin/logs/ reports/ uploads/
```

---

## 7. Configurar el servidor web

### Opción A: Apache (más común en hosting compartido)

Creá o editá el VirtualHost:
```bash
sudo nano /etc/apache2/sites-available/tecnica2.conf
```

```apache
<VirtualHost *:80>
    ServerName tecnica2.cabrasoft.org
    DocumentRoot /var/www/html
    DirectoryIndex index.php

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tecnica2_error.log
    CustomLog ${APACHE_LOG_DIR}/tecnica2_access.log combined

    # Redirigir HTTP → HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName tecnica2.cabrasoft.org
    DocumentRoot /var/www/html

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/tecnica2.cabrasoft.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/tecnica2.cabrasoft.org/privkey.pem

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Activar:
```bash
sudo a2ensite tecnica2.conf
sudo a2enmod rewrite ssl
sudo systemctl reload apache2
```

### Opción B: Nginx

```nginx
server {
    listen 80;
    server_name tecnica2.cabrasoft.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name tecnica2.cabrasoft.org;
    root /var/www/html;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/tecnica2.cabrasoft.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tecnica2.cabrasoft.org/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(env|git|htaccess) {
        deny all;
    }

    # Bloquear acceso a carpetas sensibles
    location ~ ^/(src|config|database|tests|backups|logs)(/|$) {
        deny all;
    }

    # Prevenir ejecución de scripts PHP en el directorio de cargas
    location ~* ^/uploads/.*\.php$ {
        deny all;
    }
}
```

---

## 8. Certificado SSL (HTTPS)

Si todavía no tenés SSL, usá Let's Encrypt (gratis):
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d tecnica2.cabrasoft.org
```

Para Nginx:
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d tecnica2.cabrasoft.org
```

El certificado se renueva automáticamente cada 90 días.

---

## 9. Verificar Google OAuth

> [!IMPORTANT]
> Confirmá que en [Google Cloud Console](https://console.cloud.google.com) →
> **APIs & Services → Credentials → tu cliente OAuth 2.0** tenés registrada:
>
> `https://tecnica2.cabrasoft.org/public/google_callback.php`

Si falta, agregala en "Authorized redirect URIs" y guardá.

---

## 10. Verificación final

Ejecutá estas comprobaciones desde el servidor:

```bash
# PHP puede conectarse a la base de datos
php -r "new PDO('mysql:host=localhost;dbname=school_admin', 'usuario', 'contraseña'); echo 'DB OK';"

# Los directorios escribibles tienen el propietario correcto
ls -la logs/ backups/

# El .env está presente y tiene APP_URL correcto
grep "APP_URL" .env

# El archivo de OAuth tiene las credenciales
cat config/google_oauth.local.php
```

Luego abrí en el navegador:
- `https://tecnica2.cabrasoft.org` → debe mostrar el login
- `https://tecnica2.cabrasoft.org/public/login.php` → idem
- Probá login con usuario admin (credenciales del SQL)
- Probá login con Google

---

## 11. Credenciales iniciales del sistema

> [!CAUTION]
> **Cambiá estas contraseñas inmediatamente después del primer login.**

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `admin123` | Administrador |
| `director` | `director123` | Directivo |
| `preceptor` | `preceptor123` | Preceptor |

---

## ✅ Checklist de despliegue

- [ ] Servidor con PHP 8.1+, MySQL 8.0+, Apache/Nginx
- [ ] Archivos subidos al servidor (sin `vendor/`, sin backups viejos)
- [ ] `config/google_oauth.local.php` subido manualmente
- [ ] `.env` creado y configurado con datos reales
- [ ] `APP_KEY`, `BACKUP_ENCRYPTION_KEY`, `BACKUP_DOWNLOAD_SECRET` generados
- [ ] Composer ejecutado (`composer install --no-dev`)
- [ ] Base de datos importada (`school_admin.sql`)
- [ ] Permisos configurados (`install.sh` o manual)
- [ ] VirtualHost Apache/Nginx configurado
- [ ] SSL activo (Let's Encrypt)
- [ ] Google OAuth: `redirect_uri` registrada en Google Cloud Console ✅
- [ ] Login con admin funciona
- [ ] Login con Google funciona
- [ ] Contraseñas por defecto cambiadas

