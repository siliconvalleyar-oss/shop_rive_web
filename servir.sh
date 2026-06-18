#!/bin/bash
# Servidor de desarrollo para ShopRive usando PHP de XAMPP
# No necesita sudo

DIR="$(cd "$(dirname "$0")" && pwd)"
PORT=${1:-8080}
LIBDIR="/tmp/xampp_libs"
PHP="/opt/lampp/bin/php"

# Preparar libcrypt.so.1 si no existe
if [ ! -f "$LIBDIR/libcrypt.so.1" ]; then
    echo "Preparando librerías..."
    mkdir -p "$LIBDIR"
    # Descargar libxcrypt-compat si no está cacheado
    PKG="/tmp/libxcrypt-compat.pkg.tar.zst"
    if [ ! -f "$PKG" ]; then
        curl -sL "https://archlinux.org/packages/core/x86_64/libxcrypt-compat/download/" -o "$PKG"
    fi
    mkdir -p /tmp/libcrypt_extract
    bsdtar -xf "$PKG" -C /tmp/libcrypt_extract 2>/dev/null
    cp /tmp/libcrypt_extract/usr/lib/libcrypt.so.1* "$LIBDIR/"
    rm -rf /tmp/libcrypt_extract
fi

export LD_LIBRARY_PATH="$LIBDIR:/opt/lampp/lib"

echo "============================================="
echo " ShopRive - Servidor de desarrollo"
echo "============================================="
echo "PHP: $($PHP -v 2>&1 | head -1)"
echo ""
echo "Servidor en http://localhost:$PORT"
echo "Presioná Ctrl+C para detener"
echo "============================================="

cd "$DIR"
$PHP -S "0.0.0.0:$PORT" -t "$DIR"
