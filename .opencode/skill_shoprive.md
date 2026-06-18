---
name: shoprive
description: Proyecto ShopRive - E-commerce con Rive, PHP, MySQL y chatbot autónomo
---

# ShopRive Skill

## Estructura del proyecto

```
web/
├── index.php              # Página principal (PHP)
├── .opencode/
│   └── skill_shoprive.md  # Este skill
├── config/
│   └── database.php       # Conexión a MySQL
├── api/
│   ├── chat.php           # Chatbot API (autónomo)
│   ├── auth.php           # Login/Register API
│   └── cart.php           # Carrito API
├── auth/
│   ├── login.php          # Login
│   ├── register.php       # Registro
│   └── logout.php         # Cerrar sesión
├── admin/
│   ├── index.php          # Panel admin
│   └── productos.php      # Gestionar productos
├── css/
│   └── style.css          # Estilos + animaciones
├── js/
│   └── main.js            # Lógica frontend + Rive
├── assets/
│   └── riv/               # Archivos .riv animados
├── scripts/
│   ├── install_lamp.sh    # Instalar Apache+MySQL+PHP
│   └── setup_db.sql       # Schema de base de datos
├── docs/
│   ├── API.md             # Documentación de APIs
│   └── DEPLOY.md          # Despliegue
├── README.md
├── .gitignore
└── serve.py               # Servidor dev (Python)
```

## Stack
- **Frontend**: HTML5, CSS3, JS vanilla, Rive para animaciones
- **Backend**: PHP 8+ 
- **Base de datos**: MariaDB/MySQL
- **Servidor**: Apache (httpd) en puerto 8080
- **Chatbot**: PHP + MySQL (reglas por patrón)

## Roles de usuario
- `visitante` - Navega productos, usa el chat
- `usuario` - Login, carrito persistente, compras
- `admin` - Panel de administración, gestiona productos

## Comandos útiles
```bash
# Servidor de desarrollo (Python)
python3 serve.py

# Servidor Apache
sudo systemctl start httpd

# Base de datos
sudo systemctl start mariadb

# Servicio systemd
systemctl --user start shoprive.service
```

## Base de datos
- Host: localhost
- Puerto: 3306
- DB: shoprive
- User: shoprive
- Pass: shoprive2026

## Chatbot
El chatbot usa una tabla `chatbot_conocimiento` con patrones (regex) y respuestas.
Endpoint: `POST /api/chat.php` con JSON `{"mensaje": "..."}`

## Flujo de trabajo
1. Editar PHP/JS/CSS en `~/src/web/`
2. Ver cambios en `http://localhost:8080`
3. Para DB: `sudo mysql -u root shoprive`
