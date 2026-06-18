# ShopRive - Plan de escalabilidad

## Prioridad Alta

- [ ] **Registro sin MySQL**: Implementar SQLite como fallback para registro/login cuando MariaDB no está disponible
- [ ] **Catálogo desde DB**: Sincronizar productos entre `setup_db.sql` y el array JS automáticamente vía API
- [ ] **Panel admin completo**: CRUD de productos, usuarios y pedidos desde `admin/`
- [ ] **Búsqueda de productos**: Input de búsqueda en el header con filtrado dinámico
- [ ] **Paginación de productos**: Cargar más productos con scroll infinito o paginación

## Prioridad Media

- [ ] **Más animaciones .riv**: Agregar loaders, spinners, animaciones de transición entre páginas
- [ ] **Modo oscuro/claro**: Toggle con persistencia en localStorage
- [ ] **Carrito persistente multi-sesión**: Vincular carrito localStorage al usuario cuando inicia sesión
- [ ] **Notificaciones push**: Para ofertas y recordatorios de carrito abandonado
- [ ] **Galería de imágenes por producto**: Múltiples vistas del producto con thumbnails
- [ ] **Tests unitarios**: JS con Vitest, PHP con PHPUnit

## Prioridad Baja

- [ ] **SEO**: Meta tags dinámicos, Open Graph, sitemap.xml
- [ ] **PWA**: Service worker, manifest.json, instalable como app
- [ ] **i18n**: Soporte multi-idioma (es/en)
- [ ] **Modo vendedor**: Permitir que usuarios registrados publiquen sus productos
- [ ] **Integración Mercado Pago**: Pasarela de pago real
- [ ] **Estadísticas y dashboard**: Gráficos de ventas, usuarios, productos populares
- [ ] **Docker**: Contenedor para deploy fácil con docker-compose
- [ ] **CDN para .riv**: Servir animaciones desde CDN en vez de local
- [ ] **Modo mantenimiento**: Toggle desde admin para mostrar página de mantenimiento
- [ ] **Auditoría de accesibilidad**: WCAG compliance
