# Re(Stock) Backend API

## Contributing

Create a branch named for the concept you are intending to work on.
See the `database-uml` branch as an example.

**Do not modify code directly on Github.com!**
This is incorrect and will cause all kinds of problems. Use the Git integration of your IDE.

Merge changes into the master branch only after discussion with the group and testing.

## Requirements

This list does not account for minimum versions, just the versions being used for development.

* PHP 8.2
* PHP-PDO package with MySQL
* Composer
* MySQL/MariaDB Server
* Web server (NGINX recommended)

## Getting Started

Read the [environment setup](docs/environment_setup.md) document.

## Updating

The software does not yet have an update path. The database will either be clobbered or manually updated.

### API Config

Copy `config.default.php` into `config.php` (do not modify the default file). Make the changes in the new file.

