#!/bin/bash
#
# Keeps the Inertia SSR server (php artisan inertia:start-ssr) running.
# Run every minute via cron — restarts the server if it's not responding.
# Self-locating (uses its own path), so it works unmodified on any
# deployment of this app as long as it stays at the repo root.
#
# Crontab line:
#   * * * * * /path/to/this/app/ssr-watchdog.sh >> /path/to/this/app/storage/logs/ssr-watchdog.log 2>&1

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR" || exit 1

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://127.0.0.1:13714/health)

if [ "$HTTP_CODE" != "200" ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S'): SSR not responding (HTTP ${HTTP_CODE:-none}), restarting..."

    # Clear out any half-dead process still holding the port before
    # starting a fresh one.
    pkill -f "node.*bootstrap/ssr/ssr" 2>/dev/null
    sleep 1

    nohup php artisan inertia:start-ssr >> storage/logs/ssr.log 2>&1 &
    disown
fi
