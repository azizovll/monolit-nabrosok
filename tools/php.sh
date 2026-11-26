#!/usr/bin/env bash
# PHP с нужными расширениями без правки php.ini: tools/php.sh <script> [args]
exec php -d extension_dir=C:/php/ext -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd -d extension=fileinfo -d extension=intl -d extension=exif -d memory_limit=512M "$@"
