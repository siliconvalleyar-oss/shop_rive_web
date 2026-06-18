#!/bin/bash
set -euo pipefail

echo "============================================="
echo " Instalando LAMP (Apache + MariaDB + PHP)"
echo "============================================="

# Apache
sudo pacman -S --needed --noconfirm apache

# MariaDB
sudo pacman -S --needed --noconfirm mariadb
sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql 2>/dev/null || true
sudo systemctl enable --now mariadb

# PHP y módulos
sudo pacman -S --needed --noconfirm php php-apache php-mysqli php-gd php-intl php-xml php-mbstring php-curl php-zip

# Configurar PHP para Apache
sudo sed -i 's/;extension=mysqli/extension=mysqli/' /etc/php/php.ini
sudo sed -i 's/;extension=gd/extension=gd/' /etc/php/php.ini
sudo sed -i 's/;extension=curl/extension=curl/' /etc/php/php.ini
sudo sed -i 's/;extension=mbstring/extension=mbstring/' /etc/php/php.ini
sudo sed -i 's/;extension=zip/extension=zip/' /etc/php/php.ini

# Configurar Apache para PHP
sudo sed -i 's/#LoadModule php_module/LoadModule php_module/' /etc/httpd/conf/httpd.conf 2>/dev/null || true
sudo sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/httpd/conf/httpd.conf

# Crear vhost para la web
sudo mkdir -p /etc/httpd/conf/extra
sudo tee /etc/httpd/conf/extra/shoprive.conf > /dev/null <<'VHOST'
Listen 8080
<VirtualHost *:8080>
    DocumentRoot "/home/bee/src/web"
    <Directory "/home/bee/src/web">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "/var/log/httpd/shoprive-error.log"
    CustomLog "/var/log/httpd/shoprive-access.log" common
</VirtualHost>
VHOST

echo 'Include conf/extra/shoprive.conf' | sudo tee -a /etc/httpd/conf/httpd.conf

# Iniciar Apache
sudo systemctl enable --now httpd

# Crear DB y configurar
echo ""
echo "============================================="
echo " Configurando base de datos"
echo "============================================="
echo "Creando base de datos shoprive..."
sudo mysql -u root <<< "CREATE DATABASE IF NOT EXISTS shoprive;"
sudo mysql -u root <<< "CREATE USER IF NOT EXISTS 'shoprive'@'localhost' IDENTIFIED BY 'shoprive2026';"
sudo mysql -u root <<< "GRANT ALL PRIVILEGES ON shoprive.* TO 'shoprive'@'localhost';"
sudo mysql -u root <<< "FLUSH PRIVILEGES;"

# Importar schema
echo "Importando schema..."
sudo mysql -u root shoprive < /home/bee/src/web/scripts/setup_db.sql

echo ""
echo "============================================="
echo " Instalación completada"
echo "============================================="
echo "Apache: http://localhost:8080"
echo ""
echo "Base de datos:"
echo "  Usuario: shoprive"
echo "  Password: shoprive2026"
echo "  DB: shoprive"
