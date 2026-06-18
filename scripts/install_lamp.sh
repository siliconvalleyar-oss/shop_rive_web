#!/bin/bash
# ------------------------------------------------------------------
# install_lamp.sh - ShopRive LAMP stack installer (Arch / Manjaro)
# ------------------------------------------------------------------
# Usage:
#   export SHOPRIVE_DIR="/path/to/shoprive"
#   sudo ./install_lamp.sh
#
# If SHOPRIVE_DIR is not set, the script will prompt for it.
# ------------------------------------------------------------------
set -euo pipefail
RED='\033[0;31m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'

# --- Configuration ---
SHOPRIVE_DIR="${SHOPRIVE_DIR:-}"
if [ -z "$SHOPRIVE_DIR" ]; then
  SCRIPT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
  read -r -p "Ruta del proyecto (default: $SCRIPT_DIR): " INPUT
  SHOPRIVE_DIR="${INPUT:-$SCRIPT_DIR}"
fi

if [ ! -f "$SHOPRIVE_DIR/index.php" ]; then
  echo -e "${RED}Error: $SHOPRIVE_DIR no contiene un proyecto ShopRive (index.php no encontrado)${NC}"
  exit 1
fi

echo -e "${CYAN}=============================================${NC}"
echo -e "${CYAN} Instalando LAMP (Apache + PHP)${NC}"
echo -e "${CYAN} Proyecto: $SHOPRIVE_DIR${NC}"
echo -e "${CYAN}=============================================${NC}"

# Apache
sudo pacman -S --needed --noconfirm apache

# PHP y módulos
sudo pacman -S --needed --noconfirm php php-apache php-gd php-intl php-xml php-mbstring php-curl php-zip

# Configurar PHP para Apache
for EXT in gd curl mbstring zip; do
  sudo sed -i "s/;extension=${EXT}/extension=${EXT}/" /etc/php/php.ini 2>/dev/null || true
done

# Configurar Apache para PHP
sudo sed -i 's/#LoadModule php_module/LoadModule php_module/' /etc/httpd/conf/httpd.conf 2>/dev/null || true
sudo sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/httpd/conf/httpd.conf

# Habilitar mod_rewrite
sudo sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /etc/httpd/conf/httpd.conf 2>/dev/null || true

# Crear vhost
sudo mkdir -p /etc/httpd/conf/extra
sudo tee /etc/httpd/conf/extra/shoprive.conf > /dev/null <<VHOST
Listen 8080
<VirtualHost *:8080>
    DocumentRoot "${SHOPRIVE_DIR}"
    <Directory "${SHOPRIVE_DIR}">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    <Directory "${SHOPRIVE_DIR}/data">
        Require all denied
    </Directory>
    <Directory "${SHOPRIVE_DIR}/config">
        Require all denied
    </Directory>
    ErrorLog "/var/log/httpd/shoprive-error.log"
    CustomLog "/var/log/httpd/shoprive-access.log" common
</VirtualHost>
VHOST

# Incluir vhost en httpd.conf (evitar duplicados)
grep -q 'conf/extra/shoprive.conf' /etc/httpd/conf/httpd.conf ||
  echo 'Include conf/extra/shoprive.conf' | sudo tee -a /etc/httpd/conf/httpd.conf

# Iniciar Apache
sudo systemctl enable --now httpd 2>/dev/null || sudo systemctl restart httpd

# Inicializar base de datos
echo ""
echo -e "${GREEN}Inicializando base de datos...${NC}"
php "$SHOPRIVE_DIR/scripts/setup_db.php"

echo ""
echo -e "${GREEN}=============================================${NC}"
echo -e "${GREEN} Instalación completada${NC}"
echo -e "${GREEN}=============================================${NC}"
echo -e "Apache: ${CYAN}http://localhost:8080${NC}"
echo ""
echo -e "Usuarios por defecto:"
echo -e "  admin@shoprive.com / 123456 (Admin)"
echo -e "  user@shoprive.com  / 123456 (Demo)"
echo ""
echo -e "Para cambiar el puerto, editar /etc/httpd/conf/extra/shoprive.conf"
