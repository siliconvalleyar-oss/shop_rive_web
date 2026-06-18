# API ShopRive

## Autenticación

### POST /api/auth.php?action=login
```json
{ "email": "admin@shoprive.com", "password": "admin123" }
```
Respuesta:
```json
{ "success": true, "user": { "id": 1, "nombre": "Admin", "rol": "admin" } }
```

### POST /api/auth.php?action=register
```json
{ "nombre": "Nuevo", "email": "nuevo@mail.com", "password": "pass123" }
```

### GET /api/auth.php?action=session
Devuelve el usuario logueado o null.

### POST /api/auth.php?action=logout
Cierra la sesión.

---

## Carrito

### GET /api/cart.php
Lista items del carrito del usuario logueado.

### POST /api/cart.php?action=add
```json
{ "producto_id": 1, "cantidad": 1 }
```

### POST /api/cart.php?action=update
```json
{ "producto_id": 1, "cantidad": 2 }
```

### DELETE /api/cart.php?action=remove
```json
{ "producto_id": 1 }
```

---

## Chatbot

### POST /api/chat.php
```json
{ "mensaje": "Hola" }
```
Respuesta:
```json
{ "success": true, "respuesta": "¡Hola! Bienvenido a ShopRive...", "intencion": "saludo" }
```
