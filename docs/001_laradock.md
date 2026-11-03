---
title: 0.1. Entorno con Laradock
parent: Bloque 0. Entorno y forma de trabajo
nav_order: 1
---

# 0.1. Entorno con Laradock
{: .no_toc }

{: .nota }
> **Referencia teórica:** [Instalación del entorno](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/00_instalacionEntorno.html) · [Laradock](https://laradock.io/)

1. TOC
{:toc}

## Objetivo

Tener un entorno PHP 8.4 + MariaDB reproducible, con el mismo `workspace` para Composer, Artisan, Node y los tests.

## Estructura de carpetas

```
~/Documentos/laravel/
├── laradock/              # clon de Laradock
├── curso-laravel/         # estos apuntes (plantillas y tests)
└── gestion-obras/         # la aplicación (se crea en 5.1)
```

Laradock monta `~/Documentos/laravel` en `/var/www` dentro de los contenedores, así que desde el `workspace` los materiales del curso están en `/var/www/curso-laravel/docs/materiales`.

## Ejercicios

### Ejercicio 1. Laradock

Si el entorno ya está instalado según la referencia teórica, basta con comprobar que en `laradock/.env` están estos valores:

```ini
PHP_VERSION=8.4
WORKSPACE_INSTALL_NODE=true
WORKSPACE_NODE_VERSION=22
PMA_DB_ENGINE=mariadb
```

Arranque de los servicios:

```bash
cd ~/Documentos/laravel/laradock
docker compose up -d nginx php-fpm workspace mariadb phpmyadmin
```

### Ejercicio 2. Los apuntes junto a la aplicación

```bash
cd ~/Documentos/laravel
git clone git@github.com:pdawgabriel-hub/curso-laravel.git
```

### Ejercicio 3. Base de datos del proyecto

Se crea una base de datos y un usuario propios (los datos de `root` están en `laradock/.env`):

```bash
docker compose exec mariadb mariadb -uroot -proot -e "
  CREATE DATABASE gestion_obras CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'gestion'@'%' IDENTIFIED BY 'gestion';
  GRANT ALL PRIVILEGES ON gestion_obras.* TO 'gestion'@'%';"
```

phpMyAdmin queda en <http://localhost:8081> (servidor `mariadb`).

### Ejercicio 4. Sitio de nginx y dominio local

```bash
cp nginx/sites/laravel.conf.example nginx/sites/gestion-obras.conf
```

En `gestion-obras.conf`:

```nginx
server_name gestion-obras.test;
root /var/www/gestion-obras/public;
```

```bash
docker compose restart nginx
echo "127.0.0.1 gestion-obras.test" | sudo tee -a /etc/hosts
```

### Ejercicio 5. Entrar al workspace

Todos los comandos de `composer`, `php artisan` y `npm` de los apuntes se ejecutan dentro del `workspace`:

```bash
docker compose exec --user=laradock --workdir /var/www workspace bash
```

{: .importante }
`--user=laradock` evita que los ficheros generados pertenezcan a `root`, lo que impediría a `php-fpm` escribir en `storage/`.

## Comprobación

Dentro del `workspace`:

```bash
php --version        # 8.4.x
composer --version
node --version       # 22.x
php -m | grep -E 'pdo_sqlite|pdo_mysql|gd'
```

`pdo_sqlite` es necesario porque los tests usan SQLite en memoria, y `gd` lo usa dompdf en el Bloque 9.
