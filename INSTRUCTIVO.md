# ShopRive - Instructivo

## Iniciar el servidor

```bash
./servir.sh          # usa puerto 8080
./servir.sh 8081     # usa otro puerto
```

Abrir http://localhost:8080 (o el puerto elegido) en el navegador.

## Requisitos

- XAMPP instalado en /opt/lampp (incluye PHP 8.2)
- El servidor PHP embebido no necesita MySQL; el chatbot funciona con base de conocimiento integrada.

## Chatbot

El asistente responde consultas sobre:
- Productos y catálogo
- Precios
- Envíos
- Medios de pago
- Garantía y cambios
- Horarios de atención
- Contacto

## Usuarios de prueba

| Rol    | Email              | Contraseña |
|--------|--------------------|------------|
| Admin  | admin@shoprive.com | admin123   |
| Usuario| user@shoprive.com  | admin123   |

## Notas

- Si el puerto 8080 está ocupado, usar `./servir.sh 8081`.
- Sin MySQL: el carrito y la sesión no persisten entre recargas.
- Para funcionalidad completa, instalar LAMP con `scripts/install_lamp.sh` (requiere sudo).
