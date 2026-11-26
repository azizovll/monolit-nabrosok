#!/usr/bin/env bash
# Локальный сервер сайта: http://127.0.0.1:8800/  (админка /panel/)
cd "$(dirname "$0")/.." || exit 1
exec bash tools/php.sh -S 127.0.0.1:${PORT:-8800} -t site tools/dev-router.php
