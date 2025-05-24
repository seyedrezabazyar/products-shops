#!/bin/bash
export NODE_PATH=/var/www/html/products-shops/node_modules
export PLAYWRIGHT_BROWSERS_PATH=/var/www/.cache/ms-playwright
cd /var/www/html/products-shops
/usr/local/bin/node "$@"
