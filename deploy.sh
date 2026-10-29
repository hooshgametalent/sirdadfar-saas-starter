#!/usr/bin/env bash
# Server install / update script for sirdadfar-saas-starter (Ubuntu / Debian).
#
# Usage:
#   sudo ./deploy.sh            # install Docker if needed, clone or update, build and start
#   sudo ./deploy.sh update     # pull latest code and rebuild
#   sudo ./deploy.sh logs       # follow application logs
#   sudo ./deploy.sh status     # show container status
#   sudo ./deploy.sh stop       # stop the application
#
# Overridable environment variables:
#   REPO_URL  (default: https://github.com/hooshgametalent/sirdadfar-saas-starter.git)
#   BRANCH    (default: main)
#   APP_DIR   (default: /opt/sirdadfar-saas-starter)
#   APP_PORT  (default: value in .env, otherwise 3000)

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/hooshgametalent/sirdadfar-saas-starter.git}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/opt/sirdadfar-saas-starter}"
HEALTH_TIMEOUT="${HEALTH_TIMEOUT:-120}"

log() { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[deploy]\033[0m %s\n' "$*" >&2; }
die() {
  printf '\033[1;31m[deploy]\033[0m %s\n' "$*" >&2
  exit 1
}

USE_SUDO=0
require_root() {
  if [ "$(id -u)" -ne 0 ]; then
    if command -v sudo >/dev/null 2>&1; then
      USE_SUDO=1
    else
      die "Run this script as root or install sudo."
    fi
  fi
}

# Run a command as root (directly, or through sudo when not root).
as_root() {
  if [ "$USE_SUDO" -eq 1 ]; then
    sudo "$@"
  else
    "$@"
  fi
}

install_base_packages() {
  local missing=()
  command -v git >/dev/null 2>&1 || missing+=(git)
  command -v curl >/dev/null 2>&1 || missing+=(curl)
  if [ "${#missing[@]}" -gt 0 ]; then
    command -v apt-get >/dev/null 2>&1 || die "Please install: ${missing[*]}"
    log "Installing packages: ${missing[*]}"
    as_root apt-get update -y
    as_root apt-get install -y "${missing[@]}" ca-certificates
  fi
}

install_docker() {
  if command -v docker >/dev/null 2>&1; then
    log "Docker is already installed: $(docker --version)"
  else
    log "Installing Docker Engine (get.docker.com)..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    as_root sh /tmp/get-docker.sh
    rm -f /tmp/get-docker.sh
    as_root systemctl enable --now docker >/dev/null 2>&1 || true
  fi

  if ! as_root docker compose version >/dev/null 2>&1; then
    log "Installing docker compose plugin..."
    command -v apt-get >/dev/null 2>&1 || die "Install the docker compose plugin manually."
    as_root apt-get update -y
    as_root apt-get install -y docker-compose-plugin
  fi
}

compose() {
  if [ -n "${APP_PORT:-}" ]; then
    (cd "$APP_DIR" && as_root env "APP_PORT=$APP_PORT" docker compose "$@")
  else
    (cd "$APP_DIR" && as_root docker compose "$@")
  fi
}

sync_repo() {
  if [ -d "$APP_DIR/.git" ]; then
    log "Updating code in $APP_DIR (branch $BRANCH)..."
    as_root git -C "$APP_DIR" fetch --prune origin "$BRANCH"
    as_root git -C "$APP_DIR" checkout -B "$BRANCH" "origin/$BRANCH"
  else
    log "Cloning $REPO_URL (branch $BRANCH) into $APP_DIR..."
    as_root mkdir -p "$(dirname "$APP_DIR")"
    as_root git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
  fi

  if [ ! -f "$APP_DIR/.env" ] && [ -f "$APP_DIR/.env.example" ]; then
    log "Creating .env from .env.example"
    as_root cp "$APP_DIR/.env.example" "$APP_DIR/.env"
  fi
}

app_port() {
  local port="${APP_PORT:-}"
  if [ -z "$port" ] && [ -f "$APP_DIR/.env" ]; then
    port="$(grep -E '^APP_PORT=' "$APP_DIR/.env" | tail -n 1 | cut -d= -f2 | tr -d '"'"'"' ' || true)"
  fi
  printf '%s' "${port:-3000}"
}

wait_for_health() {
  local port url elapsed=0
  port="$(app_port)"
  url="http://127.0.0.1:${port}/"
  log "Waiting for the app at $url ..."
  until curl -fsS -o /dev/null "$url"; do
    if [ "$elapsed" -ge "$HEALTH_TIMEOUT" ]; then
      warn "The app did not respond within ${HEALTH_TIMEOUT}s. Recent logs:"
      compose logs --tail=100 web || true
      exit 1
    fi
    sleep 3
    elapsed=$((elapsed + 3))
  done

  local ip
  ip="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
  log "Done. The app is running:"
  log "  http://${ip:-SERVER_IP}:${port}/"
}

up() {
  log "Building image and starting containers..."
  compose up -d --build --remove-orphans
  as_root docker image prune -f >/dev/null 2>&1 || true
  wait_for_health
}

usage() {
  sed -n '2,15p' "$0" | sed 's/^# \{0,1\}//'
}

main() {
  local cmd="${1:-install}"
  require_root

  case "$cmd" in
    install | deploy)
      install_base_packages
      install_docker
      sync_repo
      up
      ;;
    update)
      [ -d "$APP_DIR/.git" ] || die "App is not installed in $APP_DIR. Run: $0 install"
      sync_repo
      up
      ;;
    logs)
      compose logs -f --tail=200 web
      ;;
    status)
      compose ps
      ;;
    stop)
      compose down
      log "Stopped."
      ;;
    -h | --help | help)
      usage
      ;;
    *)
      usage
      die "Unknown command: $cmd"
      ;;
  esac
}

main "$@"
