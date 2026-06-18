#!/usr/bin/env bash
# ------------------------------------------------------------------
# servir.sh - ShopRive development server
# ------------------------------------------------------------------
# Starts PHP's built-in server on port 8080 (or custom port).
# Works on Linux, macOS, and Windows (Git Bash / WSL).
# Does NOT require Apache, Nginx, or XAMPP.
#
# Usage:
#   ./servir.sh          # port 8080
#   ./servir.sh 3000     # port 3000
# ------------------------------------------------------------------
set -euo pipefail

DIR="$(cd "$(dirname "$0")" && pwd)"
PORT="${1:-8080}"

# --- Find PHP binary ---
PHP=""
for CANDIDATE in \
  /opt/lampp/bin/php \
  /usr/bin/php \
  /usr/local/bin/php \
  /usr/sbin/php \
  /snap/bin/php \
  "C:/xampp/php/php.exe" \
  "C:/php/php.exe"; do
  if [ -x "$CANDIDATE" ]; then
    PHP="$CANDIDATE"
    break
  fi
done

# Fallback: try `which php`
if [ -z "$PHP" ]; then
  PHP="$(command -v php 2>/dev/null || true)"
fi

if [ -z "$PHP" ] || ! "$PHP" -v >/dev/null 2>&1; then
  echo "Error: PHP no encontrado. Instalá PHP 8+ y asegurate de que esté en PATH."
  echo ""
  echo "  Linux (Debian/Ubuntu):  sudo apt install php-cli php-mbstring php-curl php-gd php-zip php-intl php-xml"
  echo "  Linux (Arch/Manjaro):   sudo pacman -S php php-mbstring php-curl php-gd php-zip php-intl php-xml"
  echo "  macOS (Homebrew):       brew install php"
  echo "  Windows:                Descargá PHP desde https://windows.php.net/download/"
  exit 1
fi

echo "============================================="
echo " ShopRive - Servidor de desarrollo"
echo "============================================="
echo " PHP:   $("$PHP" -v 2>&1 | head -1)"
echo " Puerto: $PORT"
echo " Directorio: $DIR"
echo ""
echo " Servidor: http://localhost:$PORT"
echo " Admin:    http://localhost:$PORT/admin/"
echo ""
echo " Presioná Ctrl+C para detener"
echo "============================================="

cd "$DIR"
exec "$PHP" -S "0.0.0.0:$PORT" -t "$DIR"
