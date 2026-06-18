# ShopRive 🛒

E-commerce con animaciones Rive interactivas (botones animados, showcases 3D de productos), carrito de compras, chatbot autónomo y panel administrador.

## Stack

| Capa | Tecnología |
|------|-----------|
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Animaciones | Rive (.riv) con runtime WebGL |
| Backend | PHP 8+ |
| Base de datos | MariaDB / MySQL |
| Servidor | Apache (httpd) puerto 8080 |

## Requisitos

- Manjaro / Arch Linux
- Bash
- Conexión a internet (para descargar paquetes)

## Instalación rápida

```bash
cd ~/src/web
sudo ./scripts/install_lamp.sh
```

Esto instala Apache, MariaDB, PHP, crea la base de datos e importa el schema.

## Usuarios por defecto

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Admin | admin@shoprive.com | admin123 |
| Usuario | user@shoprive.com | admin123 |

## Servidor de desarrollo (sin Apache)

```bash
python3 serve.py
# http://localhost:8080
```

## Estructura

```
web/
├── index.php           # Página principal
├── api/                # Endpoints REST (chat, auth, cart)
├── auth/               # Login / Register / Logout
├── admin/              # Panel de administración
├── config/             # Conexión a BD
├── css/                # Estilos
├── js/                 # JavaScript + Rive
├── assets/riv/         # Animaciones .riv
├── scripts/            # Instalación y DB
└── docs/               # Documentación
```

## Funcionalidades

- Carrusel animado con Rive
- 8 productos con animaciones
- Carrito de compras lateral
- Sistema de usuarios (visitor / user / admin)
- Chatbot autónomo con base de conocimiento
- Panel admin para gestionar productos
- Diseño responsive
- Animaciones CSS + Rive en toda la UI
