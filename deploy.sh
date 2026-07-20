#!/bin/bash
set -e
cd "$(dirname "$0")"

PHP=/usr/local/php84/bin/php

echo "==> Menarik update dari GitHub..."
git pull origin main

echo "==> Menjalankan migration (kalau ada)..."
$PHP artisan migrate --force

echo "==> Refresh config cache (wajib setiap .env berubah)..."
$PHP artisan config:clear
$PHP artisan config:cache

echo "==> Rebuild frontend..."
PATH="/usr/local/php84/bin:$PATH" npm run build

echo "==> Selesai. Commit aktif sekarang:"
git log --oneline -1
