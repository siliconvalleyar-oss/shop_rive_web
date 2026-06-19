# ShopRive - Plan de escalabilidad

## Prioridad Alta — Crítico para producción

- [x] **Base de datos autónoma**: Migrado a JSON file DB (sin MySQL, sin SQLite). Correr `php scripts/setup_db.php`
- [x] **Catálogo desde DB**: Productos servidos desde `api/products.php` con fallback offline
- [x] **API REST con router**: Front controller `api/index.php` con rutas REST y backward compatibility
- [x] **Manejo de errores centralizado**: ErrorHandler + Logger con archivos rotativos en `data/logs/`
- [x] **Protección CSRF**: Token por sesión validado en POST/PUT/DELETE
- [x] **Rate limiting**: IP-based en `data/rate_limits.json` (60 req/min)
- [x] **Validación de input**: Clase Validator con reglas encadenables
- [x] **Separación JS en módulos**: 10 módulos ES6 en `js/modules/` con state management observable

- [ ] **Migrar de SimpleDB (JSON) a MySQL/PostgreSQL**: Los archivos JSON no soportan concurrencia. Con 2+ usuarios escribiendo simultáneamente se corrompen. CRÍTICO.
- [ ] **Reemplazar mail() por servicio transaccional**: `mail()` de PHP cae en spam o no llega. Usar SendGrid, Resend, o SMTP.
- [ ] **Sesiones en Redis/Memcached**: Las sesiones en filesystem no escalan con múltiples servidores.
- [ ] **Panel admin completo**: CRUD de productos, usuarios y pedidos desde `admin/`
- [ ] **Búsqueda de productos**: Input de búsqueda en el header con filtrado dinámico
- [ ] **Paginación de productos**: Cargar más productos con scroll infinito o paginación

## Prioridad Media

- [ ] **Login/Register con CSRF**: Los formularios HTML actuales (`login.html`, `register.html`) no envían el token CSRF. Agregar campo oculto.
- [ ] **Mejor manejo de errores en frontend**: Timeouts en fetch(), detección de offline, reintento automático
- [ ] **Carrito persistente multi-sesión**: Vincular carrito localStorage al usuario cuando inicia sesión
- [ ] **Dashboard de logs desde admin**: Ver logs de errores, rate limiting, actividad en el panel admin
- [ ] **Tests de integración para API**: Testear todos los endpoints con datos reales
- [ ] **Tests unitarios**: JS con Vitest, PHP con PHPUnit
- [ ] **Notificaciones push**: Para ofertas y recordatorios de carrito abandonado
- [ ] **Galería de imágenes por producto**: Múltiples vistas del producto con thumbnails
- [ ] **Más animaciones .riv**: Agregar loaders, spinners, animaciones de transición entre páginas

## Prioridad Baja

- [ ] **Migrar a TypeScript**: Tipado estático para el frontend
- [ ] **CI/CD pipeline**: GitHub Actions para lint, tests, deploy automático
- [ ] **SEO**: Meta tags dinámicos, Open Graph, sitemap.xml
- [ ] **PWA**: Service worker, manifest.json, instalable como app
- [ ] **i18n**: Soporte multi-idioma (es/en)
- [ ] **Modo vendedor**: Permitir que usuarios registrados publiquen sus productos
- [ ] **Integración Mercado Pago**: Pasarela de pago real
- [ ] **Estadísticas y dashboard**: Gráficos de ventas, usuarios, productos populares
- [ ] **Docker**: Contenedor para deploy fácil con docker-compose
- [ ] **Documentación de API**: Swagger/OpenAPI para los endpoints REST
- [ ] **CDN para .riv**: Servir animaciones desde CDN en vez de local
- [ ] **Modo mantenimiento**: Toggle desde admin para mostrar página de mantenimiento
- [ ] **Auditoría de accesibilidad**: WCAG compliance
