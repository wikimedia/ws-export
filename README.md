What is Wikisource Export?
==========================

![CI](https://github.com/wsexport/tool/workflows/CI/badge.svg)

Wikisource export is a tool for exporting Wikisource page in many formats like
epub or xhtml. The documentation can be found here:
https://wikisource.org/wiki/Wikisource:WSexport

Requirements
============
* PHP 7.2
* [Composer](http://getcomposer.org/)
* The `fc-list` command

Installation
============

1. Get the source code:

       git clone https://github.com/wsexport/tool.git
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

4. Run `./bin/compose app:install` to initialize the database.

Tests
=====

Run `composer install` to install dependencies required for testing.
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
DATABASE_URL=mysql://root:@database:3306/wsexport?serverVersion=5.7
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
```

```bash
docker-compose exec wsexport composer install
```



```bash
docker-compose exec wsexport ./bin/console app:install
```

Wikisource Export should be up at http://localhost:8888/


### Setup Xdebug

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
