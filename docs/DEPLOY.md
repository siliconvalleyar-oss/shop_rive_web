# Despliegue ShopRive

## Producción (Apache)

```bash
# 1. Instalar dependencias
sudo ./scripts/install_lamp.sh

# 2. Verificar Apache
sudo systemctl status httpd
curl http://localhost:8080

# 3. Verificar DB
sudo mysql -u root -e "USE shoprive; SHOW TABLES;"
```

## Desarrollo (Python)

```bash
python3 serve.py
```

## Respaldar base de datos

```bash
sudo mysqldump -u root shoprive > backup_$(date +%Y%m%d).sql
```

## Restaurar base de datos

```bash
sudo mysql -u root shoprive < backup_20260101.sql
```

## Logs

- Apache: `sudo journalctl -u httpd -f`
- PHP errors: `sudo tail -f /var/log/httpd/shoprive-error.log`
