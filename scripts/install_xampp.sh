#!/bin/bash
set -euo pipefail

echo "============================================="
echo " Instalando XAMPP en Manjaro"
echo "============================================="

if [ -d /opt/lampp ]; then
  echo "XAMPP ya está instalado en /opt/lampp"
  echo "Ejecutá: sudo /opt/lampp/lampp start"
  exit 0
fi

echo ""
echo "Seleccioná el método:"
echo "1) Desde AUR (recomendado para Manjaro)"
echo "2) Descarga oficial desde apachefriends.org"
echo ""
read -p "Opción (1/2): " opt

case $opt in
  1)
    echo "Instalando desde AUR..."
    if which yay &>/dev/null; then
      yay -S --noconfirm xampp
    elif which paru &>/dev/null; then
      paru -S --noconfirm xampp
    else
      echo "No tenés yay ni paru. Instalando yay..."
      sudo pacman -S --needed --noconfirm base-devel git
      git clone https://aur.archlinux.org/yay.git /tmp/yay
      cd /tmp/yay && makepkg -si --noconfirm
      yay -S --noconfirm xampp
    fi
    ;;
  2)
    echo "Buscando última versión..."
    URL=$(curl -sL "https://www.apachefriends.org/es/download.html" | grep -oP 'xampp-linux-x64-\d+\.\d+\.\d+-\d+-installer\.run' | head -1)
    if [ -z "$URL" ]; then
      echo "No se pudo obtener la última versión. Usando versión 8.2.12..."
      URL="xampp-linux-x64-8.2.12-0-installer.run"
    fi
    FULL_URL="https://sourceforge.net/projects/xampp/files/XAMPP%20Linux/${URL//xampp-linux-x64-/}/$URL/download"
    cd /tmp
    echo "Descargando: $URL"
    wget -q --show-progress "$FULL_URL" -O xampp-installer.run
    chmod +x xampp-installer.run
    echo "Ejecutando instalador..."
    sudo ./xampp-installer.run
    ;;
  *)
    echo "Opción inválida"
    exit 1
    ;;
esac

echo ""
echo "============================================="
echo " XAMPP instalado"
echo "============================================="
echo "Iniciar:   sudo /opt/lampp/lampp start"
echo "Detener:   sudo /opt/lampp/lampp stop"
echo "Admin DB:  http://localhost/phpmyadmin"
echo ""
echo "Para vincular ShopRive:"
echo "  sudo ln -s /home/bee/src/web /opt/lampp/htdocs/shoprive"
echo "  http://localhost/shoprive"
