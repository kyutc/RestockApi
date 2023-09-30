# Re(Stock) Backend API

## Requirements

This list does not account for minimum versions, just the versions being used for development.

* PHP 8.2
* PHP-PDO package with MySQL
* Composer
* MySQL/MariaDB Server
* Web server (NGINX recommended)

## Installation

TODO, but:

Import database.sql into the database.

Use composer to install all the dependencies. oauth2-server is not actually used.

Configure the web server to have `/api/v1/` point to `api.php`

Hope for the best.

## Updating

The software does not yet have an update path. The database will either be clobbered or manually updated.

## Configuration

### NGINX Config

Example config. Note: HTTPS is *mandatory* in a production environment.
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.cpsc4900.local;

    root /var/www/api.cpsc4900.local/api/v1/public;
    index index.html index.php;

    location /api/v1 {
        try_files $uri /api.php?q=$uri&$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

### API Config

Copy `config.default.php` into `config.php` (do not modify the default file). Make the changes in the new file.

