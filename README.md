WS Export
=========

![CI](https://github.com/wikimedia/ws-export/workflows/CI/badge.svg)

WS Export is a tool for exporting Wikisource books to many formats, such as EPUB or PDF.
The documentation can be found here:
https://wikisource.org/wiki/Wikisource:WS_Export

Requirements
============
* PHP 7.3 or 7.4
* [Composer](http://getcomposer.org/)
* The `fc-list` command

Installation
============

1. Get the source code:

       git clone https://github.com/wikimedia/ws-export.git
       cd tool

2. Install dependencies:

       composer install --no-dev

   Then create a `.env.local` file.

   * In order to export to PDF, plain text, RTF, or Mobi formats
     you should also install [Calibre](https://calibre-ebook.com)
     so that the tool can use the `ebook-convert` command.
   * To run the integration tests (or just to validate exported ebooks)
     you should also install
     [epubcheck](https://github.com/w3c/epubcheck).
     If it's not installed at `/usr/bin/epubcheck` then
     set the `EPUBCHECK_JAR` environment variable.

3. Create a mysql database and database user
   and add these details to `.env.local`.

4. Create the database with `./bin/console doctrine:database:create`

5. Run the migrations with `./bin/console doctrine:migrations:migrate`

6. This tool uses the [Toolforge Bundle](https://github.com/wikimedia/ToolforgeBundle), and it connects to [multiple databases](https://github.com/wikimedia/ToolforgeBundle#replicas-connection-manager).
  * Set replicas credentials in the `.env.local` file.

  * Establish an SSH tunnel to the replicas (only necessary on local environments)
```bash
$ ./php bin/console toolforge:ssh
```
  * Bind address for docker enviroments
```bash
$ php bin/console toolforge:ssh --bind-address=0.0.0.0
```

Tests
=====

Run `composer install` to install dependencies required for testing.

Make sure the test database is created and migrations are up-to-date:
```bash
$ ./bin/console doctrine:migrations:database:create --env=test
$ ./bin/console doctrine:migrations:migrate --env=test --no-interaction
```

You only need to run the first command once, and the second one only
when new migrations are created.

Tests are located in the `tests/` directory, to run them:

```bash
$ ./bin/phpunit --exclude-group integration
$ ./bin/phpunit --group integration # runs integration tests (slow)
```

You can also run code linting etc. with `composer test`.

Docker Developer Environment
============================

Wikisource export can also be run for development using Docker Compose. _(beta, only tested on linux)_

The default environment provides PHP, Apache, Calibre, Epubcheck and a MariaDB database.

### Requirements

You'll need a locally running Docker and Docker Compose:

  - [Docker installation instructions][docker-install]
  - [Docker Compose installation instructions][docker-compose]

[docker-install]: https://docs.docker.com/install/
[docker-compose]: https://docs.docker.com/compose/install/

---

### Quickstart

Modify or create `.env.local`. This config uses the database container defaults.
```
DATABASE_URL=mysql://root:@database:3306/wsexport
```

Do the same for the test database at `.env.test.local`, but giving a different database name:
```
DATABASE_URL=mysql://root:@database:3306/wsexport_test
```

Make sure you cd into `./docker`

```bash
cd ./docker 
```

Run the following command to add your user ID and group ID to your `.env` file:

```bash
echo "WS_DOCKER_UID=$(id -u)
WS_DOCKER_GID=$(id -g)" >> ./.env
```

Start the environment and install

```bash
# -d is detached mode - runs containers in the background:
docker-compose build && docker-compose up -d
docker-compose exec wsexport composer install
docker-compose exec wsexport ./bin/console doctrine:migrations:migrate --no-interaction
```

Wikisource Export should be up at http://localhost:8888/

### Cache
Go to `/refresh` to clear the cache

### Setup Xdebug
Xdebug is disabled by default. If you need to enable it you can do so via an env variable by creating a `./docker/docker-compose.override.yml` file with the following content
```
version: '3.7'
services:
  wsexport:
    environment:
     - XDEBUG_MODE=debug
```

#### Visual Studio Code

Add the following configuration to your `launch.json`
```
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9000,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

You need to install the [php-xdebug-ext]

[php-xdebug-ext]: https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug


Licence
=======

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <http://www.gnu.org/licenses/>.

### The Wikisource logo

The Wikisource logo is included as `public/img/Wikisource-logo.svg`,
as an optimized form of the logo
found at [commons.wikimedia.org/wiki/File:Wikisource-logo.svg](https://commons.wikimedia.org/wiki/File:Wikisource-logo.svg)
and subject to the licence restrictions specified on that page.
