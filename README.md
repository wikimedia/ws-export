What is Wikisource Export?
==========================

Wikisource export is a tool for exporting Wikisource page in many formats like epub or xhtml. The documentation can be found here : http://wikisource.org/wiki/Wikisource:WSexport

Installation
============

You have to download the files and use it with PHP 5.5 or more.

You have to create a temp folder in the root of directories.

This tool depends on [Composer](http://getcomposer.org/) to install some dependencies. The easiest way to use it is to run the Wsexport Tool main directory:

```
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

In order to use pdf, txt, rtf and mobi formats you should install Calibre in order to allow the tool to use the `ebook-convert` command.
Composition
===========

This tool is split into independent parts:
* `utils` : api to interact with Wikisource and others things
* `book` : export tool in many formats like epub.

The tools can be used in two ways:
* http in the `http` folder
* command line in the `cli` folder

Licence
=======

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
