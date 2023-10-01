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
    listen      80;
    listen [::]:80;
    listen      443 ssl;
    listen [::]:443 ssl;
    ssl_certificate     ssl/api.cpsc4900.local.crt;
    ssl_certificate_key ssl/api.cpsc4900.local.key;

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

A self-signed certificate for local testing may be generated like so:
```bash
openssl req -new -newkey ec -pkeyopt ec_paramgen_curve:prime256v1 -sha256 -days 3650 -nodes -x509 \
    -keyout api.cpsc4900.local.key -out api.cpsc4900.local.crt -config <(cat <<-EOF
    [ req ]
    distinguished_name = dn
    x509_extensions = san
    prompt = no
    
    [ dn ]
    CN = api.cpsc4900.local
    
    [ san ]
    subjectAltName = @sans
    
    [ sans ]
    DNS.1 = api.cpsc4900.local
EOF
)
```

### API Config

Copy `config.default.php` into `config.php` (do not modify the default file). Make the changes in the new file.

