#!/usr/bin/env bash
# BHCIS port forwarding runbook - start/stop/status for the Cloudflare tunnel.
# See docs/PORT_FORWARDING.md for the full notes.
set -euo pipefail

CLOUDFLARED="${CLOUDFLARED:-$(command -v cloudflared || echo "$HOME/.local/bin/cloudflared")}"
PORT="${PORT:-8000}"
SERVE_LOG="/tmp/opencode/serve.log"
TUNNEL_LOG="/tmp/opencode/tunnel.log"

usage() {
    echo "Usage: $0 {start|stop|status|rebuild}"
    echo "  start    - build checks, start backend + tunnel, print public URL"
    echo "  stop     - kill tunnel and backend"
    echo "  status   - show what is running right now"
    echo "  rebuild  - npm run build (run after frontend changes while sharing)"
}

die() {
    echo "ERROR: $1" >&2
    exit 1
}

check_backend_down() {
    if pgrep -f "artisan serve" > /dev/null; then
        die "backend already running, run '$0 stop' first"
    fi
}

check_tunnel_down() {
    if pgrep -f "cloudflared tunnel" > /dev/null; then
        die "tunnel already running, run '$0 stop' first"
    fi
}

check_ready_to_share() {
    if [ -f public/hot ]; then
        die "public/hot exists (Vite dev server was running), run '$0 stop' and kill any npm/vite processes first"
    fi
    if [ ! -f public/build/manifest.json ]; then
        die "no built assets, run 'npm run build' first"
    fi
}

start() {
    check_backend_down
    check_tunnel_down
    check_ready_to_share

    [ -x "$CLOUDFLARED" ] || die "cloudflared not found at $CLOUDFLARED, download it first"

    setsid nohup php artisan serve --port="$PORT" > "$SERVE_LOG" 2>&1 < /dev/null &
    disown
    echo "backend started on http://localhost:$PORT"

    sleep 3
    code=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:$PORT/login" || true)
    [ "$code" = "200" ] || [ "$code" = "302" ] || die "backend not responding (status=$code), see $SERVE_LOG"

    setsid nohup "$CLOUDFLARED" tunnel --url "http://localhost:$PORT" --no-autoupdate > "$TUNNEL_LOG" 2>&1 < /dev/null &
    disown

    for i in $(seq 1 15); do
        URL=$(grep -oE '[a-z0-9-]+\.trycloudflare\.com' "$TUNNEL_LOG" | head -1 || true)
        [ -n "$URL" ] && break
        sleep 1
    done
    [ -n "$URL" ] || die "tunnel did not get a URL, see $TUNNEL_LOG"

    echo "public URL: https://$URL"
    echo "verify on your phone with a hard refresh"
}

stop() {
    pkill -f "cloudflared tunnel" 2>/dev/null || true
    pkill -f "artisan serve" 2>/dev/null || true
    echo "stopped tunnel and backend"
}

status() {
    if pgrep -af "cloudflared tunnel" | grep -v pgrep; then
        URL=$(grep -oE '[a-z0-9-]+\.trycloudflare\.com' "$TUNNEL_LOG" | head -1 || true)
        echo "tunnel: running, public URL: https://${URL:-unknown}"
    else
        echo "tunnel: not running"
    fi

    if pgrep -af "artisan serve" | grep -v pgrep; then
        echo "backend: running"
    else
        echo "backend: not running"
    fi

    if [ -f public/hot ]; then
        echo "warning: public/hot exists, assets will point at the Vite dev server"
    elif [ -f public/build/manifest.json ]; then
        echo "assets: built (shared mode OK)"
    else
        echo "assets: not built, run '$0 rebuild'"
    fi
}

rebuild() {
    npm run build
}

case "${1:-}" in
    start)   start ;;
    stop)    stop ;;
    status)  status ;;
    rebuild) rebuild ;;
    *)       usage ;;
esac
