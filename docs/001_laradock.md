# 0.1. Entorno con Laradock

**Referencia teórica:** [Instalación y preparación del entorno](https://2daw-carlosiii.github.io/marcapersonalFP_REA/documentos/0613_Servidor/00_instalacionEntorno.html) · [Laradock](https://laradock.io/)

Tener Laradock con PHP 8.4, Node 22, Xdebug y MariaDB, el mismo entorno en el que se ejecutan todos los comandos de los apuntes. Se parte de una máquina Debian con Docker instalado y utilizable sin `sudo` (pasos 1 y 2 de la referencia teórica).

## Ejercicios

1. **Descargar Laradock.**

    ```bash
    cd ~/Documentos
    mkdir laravel
    cd laravel/
    git clone https://github.com/Laradock/laradock.git
    ```

2. **Fichero de configuración.**

    ```bash
    cd laradock && cp .env.example .env && cd ..
    ```

3. **PHP, base de datos y Node.** En `laradock/.env`:

    ```ini
    PHP_VERSION=8.4
    PMA_DB_ENGINE=mariadb
    WORKSPACE_INSTALL_NODE=true
    WORKSPACE_NODE_VERSION=22
    ```

4. **Depuración: Xdebug y errores en pantalla.** En `laradock/.env`:

    ```ini
    WORKSPACE_INSTALL_XDEBUG=true
    PHP_FPM_INSTALL_XDEBUG=true
    WORKSPACE_XDEBUG_PORT=9003
    PHP_FPM_XDEBUG_PORT=9003
    ```

    Obtener la IP del bridge de Docker (el campo `Gateway`, normalmente `172.17.0.1`) y fijarla en `laradock/.env`:

    ```bash
    docker network inspect bridge | grep Gateway
    ```

    ```ini
    DOCKER_HOST_IP=172.17.0.1
    ```

    En **los dos** ficheros `laradock/php-fpm/xdebug.ini` y `laradock/workspace/xdebug.ini`:

    ```ini
    xdebug.client_host="dockerhost"
    xdebug.discover_client_host=1
    xdebug.client_port=9003
    xdebug.idekey=vsc
    xdebug.mode=debug
    xdebug.start_with_request=trigger

    xdebug.cli_color=0
    xdebug.output_dir="~/xdebug/phpstorm/tmp/profiling"

    xdebug.var_display_max_children=-1
    xdebug.var_display_max_data=-1
    xdebug.var_display_max_depth=-1
    ```

    En `laradock/php-fpm/laravel.ini`, cambiar `display_errors=Off` por `display_errors=On` (solo en desarrollo).

5. **Ajustes de MariaDB.**

    - En `laradock/mariadb/my.cnf`, `innodb_log_file_size` a `256M`.
    - Solo si el contenedor falla con `mysqld: not found`: en `laradock/mariadb/Dockerfile`, sustituir `CMD ["mysqld"]` por `CMD ["mariadbd"]`.

6. **Levantar el entorno.**

    ```bash
    cd laradock
    docker compose up -d nginx php-fpm workspace mariadb phpmyadmin
    ```

    Si los contenedores ya estaban levantados y se ha cambiado `.env`, `xdebug.ini` o `laravel.ini`:

    ```bash
    docker compose up -d --build workspace php-fpm
    ```

## Comprobación

Desde la carpeta `laradock`:

```bash
docker compose ps
docker compose exec workspace php --version        # 8.4.x
docker compose exec workspace composer --version
docker compose exec workspace node --version       # 22.x
docker compose exec workspace npm --version
docker compose exec workspace getent hosts dockerhost
docker compose exec php-fpm php -r 'var_dump(ini_get("display_errors"));'   # string(1) "1"
```

phpMyAdmin queda en <http://localhost:8081>.

> **Nota.** La base de datos de la aplicación se crea en [6.1](./RA6_1_migraciones.md), y el proyecto y su sitio de nginx, en [5.1](./RA5_1_instalacion.md). En el Bloque 5, Laravel solo usa SQLite para sus tablas internas.

---

**Siguiente:** [0.2. Git y GitHub](./002_git.md)
