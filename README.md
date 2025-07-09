# WS Export

**WS Export** is a tool for exporting [Wikisource](https://en.wikipedia.org/wiki/Wikisource) books to many formats, such as EPUB or PDF.
For more information, see the following links:

* Tool URL: https://ws-export.wmcloud.org
* User documentation: https://wikisource.org/wiki/Wikisource:WS_Export
* Issue tracker: https://phabricator.wikimedia.org/tag/ws_export/

The rest of this readme is aimed at developers and people running WS Export locally (e.g. for batch downloading).

[![CI](https://github.com/wikimedia/ws-export/actions/workflows/ci.yml/badge.svg)](https://github.com/wikimedia/ws-export/actions/workflows/ci.yml)

## Requirements

* PHP 7.3 or above
* [Composer](http://getcomposer.org/)
* The `fc-list` command
* The following fonts (optional, but required for development):
  * `fonts-freefont-ttf`
  * `fonts-linuxlibertine`
  * `fonts-dejavu-core`
  * `fonts-gubbi`
  * `fonts-opendyslexic`

## Development installation (without Docker)

This section deals with a full installation of WS Export on the local development machine. For Docker installation instructions, see [below](#docker-developer-environment).

1. Get the source code:

   ```console
   git clone https://github.com/wikimedia/ws-export.git
   cd ws-export
   ```

2. Install dependencies:

   ```console
   composer install
   ```

   A few PHP extensions are required; if these are not installed, Composer will let you know
   and you will need to install them with your operating system's package manager.
   For example, in Debian-based Linux distributions:

   ```console
   apt install php-sqlite3 php-zip php-curl php-sysvsem
   ```

   Then create a `.env.local` file:

   ```console
   cp .env .env.local
   ```

   Edit `.env.local` and set `APP_ENV=dev` and `APP_SECRET` to a random string of your choice.

3. [Install Symfony CLI](https://symfony.com/download#step-1-install-symfony-cli)
   and start the development web server with:

   ```console
   symfony server:start -d
   ```

4. Open your web browser to e.g. <http://localhost:8000>
   and the basic operations should be working with the following limitations:
   * you can only export to EPUB format; and
   * you must check the 'Exclude credits' option,
     to avoid querying the database for usernames.

   Continue on for setting up more exporting and development options.

5. Install optional dependencies:

   **Calibre:**
   In order to export to PDF, plain text, RTF, or Mobi formats
   you must install [Calibre](https://calibre-ebook.com)
   so that the tool can use the `ebook-convert` command.

   **epubcheck:**
   To validate exported ebooks (with the [`./bin/console app:check` command](#appcheck)),
   you must install [epubcheck](https://github.com/w3c/epubcheck).
   If it is installed at a location other than `/usr/bin/epubcheck`, then
   set the `EPUBCHECK_JAR` environment variable to the correct path.

   **Fonts:**
   To run the integration tests, also install
   the `fonts-linuxlibertine` package.
   You can also install any other fonts you want to use for exporting books.

6. WS Export uses two database connections:
   firstly, to its own database to store download statistics;
   and secondly, it connects to the Wikimedia database replicas
   to fetch information about Wikisource contributors
   for the credits list that can be included at the end of exported books.

   For the statistics recording database you need to create the database
   and add connection details to `.env.local`:

   ```console
   DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name
   ```

7. Then create the database tables with:

   ```
   ./bin/console doctrine:migrations:migrate`
   ```

8. To connect to the Wikimedia replicas, this tool uses
   the [Toolforge Bundle](https://github.com/wikimedia/ToolforgeBundle),
   and connects to [multiple databases](https://github.com/wikimedia/ToolforgeBundle#replicas-connection-manager).

   Set the replicas' credentials in your `.env.local` file
   according to the Bundle's documentation.

9. Establish an SSH tunnel to the replicas:

  ```console
  ./bin/console toolforge:ssh
  ```

10. At this point, you should be able to use all of the functionality
    of WS Export, via both the web interface and the CLI.

## CLI Usage

### app:check

Run epubcheck on books. With no options set, this will check 10 random books from English Wikisource. Note that the random 10 will be cached (for repeatability) unless you use <info>--nocache</info>.

```console
app:check [-l|--lang LANG] [--nocache] [-t|--title TITLE] [-c|--count COUNT] [-s|--namespaces NAMESPACES]
```

* `--lang` `-l` — Wikisource language code.
  Default: 'en'
* `--nocache` — Do not cache anything (re-fetch all data).
* `--title` `-t` — Wiki page name of a single work to check.
* `--count` `-c` — How many random pages to check. Ignored if <info>--title</info> is used.
  Default: 10
* `--namespaces` `-s` — Pipe-delimited namespace IDs. Ignored if <info>--title</info> is used.

### app:export

Export a book.

```console
app:export [-l|--lang LANG] [-t|--title TITLE] [-f|--format FORMAT] [-p|--path PATH] [--nocache] [--nocredits]
```

* `--lang` `-l` — Wikisource language code.
* `--title` `-t` — Wiki page name of the work to export. Required
* `--format` `-f` — Export format. One of: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9
  Default: 'epub-3'
* `--path` `-p` — Filesystem path to export to.
  Default: '[CWD]'
* `--nocache` — Do not cache anything (re-fetch all data).
* `--nocredits` — Do not include the credits list in the exported ebook.

### app:opds

Generate an OPDS file.

```console
app:opds [-l|--lang LANG] [-c|--category CATEGORY]
```

* `--lang` `-l` — Wikisource language code.
* `--category` `-c` — Category name to export.

### app:queue

Process the queue.

```console
app:queue
```


## Tests

Run `composer install` to install dependencies required for testing.

Make sure the test database is created and migrations are up-to-date:

```console
./bin/console doctrine:database:create --env=test
./bin/console doctrine:migrations:migrate --env=test --no-interaction
```

You only need to run the first command once, and the second one only
when new migrations are created.

Tests are located in the `tests/` directory, to run them:

```console
./bin/phpunit --exclude-group integration
./bin/phpunit --group integration # runs integration tests (slow)
```

You can also run code linting etc. with `composer test`.

## Docker Developer Environment

Wikisource export can also be run for development using Docker Compose. _(beta, only tested on linux)_

The default environment provides PHP, Apache, Calibre, Epubcheck and a MariaDB database.

### Docker requirements

You'll need a locally running Docker and Docker Compose:

* [Docker installation instructions][docker-install]
* [Docker Compose installation instructions][docker-compose]

[docker-install]: https://docs.docker.com/install/
[docker-compose]: https://docs.docker.com/compose/install/

---

### Quickstart

Modify or create `.env.local`. This config uses the database container defaults.

```console
DATABASE_URL=mysql://root:@database:3306/wsexport
```

Do the same for the test database at `.env.test.local`, but giving a different database name:

```console
DATABASE_URL=mysql://root:@database:3306/wsexport_test
```

Make sure you cd into `./docker`

```console
cd ./docker 
```

Run the following command to add your user ID and group ID to your `.env` file:

```console
echo "WS_DOCKER_UID=$(id -u)
WS_DOCKER_GID=$(id -g)" >> ./.env
```

Optionally, set the port in `.env` (default is 8888):

```console
WS_EXPORT_PORT=18000
```

Start the environment and install

```console
# -d is detached mode - runs containers in the background:
docker-compose build && docker-compose up -d
docker-compose exec wsexport composer install
docker-compose exec wsexport ./bin/console doctrine:migrations:migrate --no-interaction
```

Wikisource Export should be up at <http://localhost:8888/> (or the configured port)

### Cache

Go to `/refresh` to clear the cache

### Setup Xdebug

Xdebug is disabled by default.
If you need to enable it, you can do so via an environment variable,
by creating a `./docker/docker-compose.override.yml` file with the following content:

```yaml
version: '3.7'
services:
  wsexport:
    environment:
     - XDEBUG_MODE=debug
```

#### Visual Studio Code

Add the following configuration to your `launch.json`

```json
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

## Licence

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

The PNG version in `resources/Wikisource-logo.svg.png`
is [the Commons auto-generated 160px raster version](https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Wikisource-logo.svg/160px-Wikisource-logo.svg.png)
of the (original, non-optimized) SVG file.
