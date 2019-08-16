What is Wikisource Export?
==========================

[![Build Status](https://travis-ci.org/wsexport/tool.svg?branch=master)](https://travis-ci.org/wsexport/tool)

Wikisource export is a tool for exporting Wikisource page in many formats like
epub or xhtml. The documentation can be found here:
https://wikisource.org/wiki/Wikisource:WSexport

Requirements
============
* PHP 7.2
* [Composer](http://getcomposer.org/)

Installation
============

1. Get the source code:

       git clone https://github.com/wsexport/tool.git
       cd tool

2. Install dependencies:

       composer install --no-dev

   This will create a `config.php` file that you can edit.

   In order to export to PDF, plain text, RTF, or Mobi formats
   you should also install [Calibre](https://calibre-ebook.com)
   so that the tool can use the `ebook-convert` command.

3. Create a database and database user
   and add these details to `config.php`.

4. Run `./bin/install.php` to initialize the database.

Composition
===========

This tool is split into independent parts:
* `utils` : api to interact with Wikisource and others things
* `book` : export tool in many formats like epub.

The tools can be used in two ways:
* http in the `http` folder
* command line in the `cli` folder (run `./cli/book.php`)


Tests
=====

Tests are located in the `tests/` directory, to run them:

```bash
$ make test
$ make integration-test # runs integration tests (slow)
```

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
