#!/usr/bin/env bash
# Server install / update script for the Laravel freight platform (Ubuntu / Debian).
#
# Usage:
#   sudo ./deploy.sh            # install Docker if needed, clone or update, build and start
#   sudo ./deploy.sh update     # back up, pull latest code, rebuild and migrate
#   sudo ./deploy.sh admin      # create the first admin (asks for mobile and password)
#   sudo ./deploy.sh backup     # dump MySQL and the private documents into backups/
#   sudo ./deploy.sh artisan ARGS...  # run php artisan inside the app container
#   sudo ./deploy.sh logs       # follow application logs
#   sudo ./deploy.sh status     # show container status
#   sudo ./deploy.sh stop       # stop the application
#
# Overridable environment variables:
#   REPO_URL  (default: https://github.com/hooshgametalent/sirdadfar-saas-starter.git)
#   BRANCH    (default: main)
#   APP_DIR   (default: /opt/sirdadfar-saas-starter)
#   APP_PORT  (default: value in .env, otherwise 8080)
#   ADMIN_MOBILE, ADMIN_PASSWORD, ADMIN_NAME  (used by install and admin)

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/hooshgametalent/sirdadfar-saas-starter.git}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/opt/sirdadfar-saas-starter}"
HEALTH_TIMEOUT="${HEALTH_TIMEOUT:-180}"

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
  command -v openssl >/dev/null 2>&1 || missing+=(openssl)
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
    if curl -fsSL https://get.docker.com -o /tmp/get-docker.sh; then
      as_root sh /tmp/get-docker.sh
      rm -f /tmp/get-docker.sh
    else
      # get.docker.com may be blocked (e.g. some Iranian servers): use the distro packages.
      warn "get.docker.com is not reachable. Installing docker.io from the OS apt repository..."
      command -v apt-get >/dev/null 2>&1 || die "Install Docker manually."
      as_root apt-get update -y
      as_root apt-get install -y docker.io
    fi
    as_root systemctl enable --now docker >/dev/null 2>&1 || true
  fi

  if ! as_root docker compose version >/dev/null 2>&1; then
    log "Installing docker compose plugin..."
    command -v apt-get >/dev/null 2>&1 || die "Install the docker compose plugin manually."
    as_root apt-get update -y
    # docker-compose-plugin comes from Docker's apt repo (get.docker.com),
    # docker-compose-v2 from Ubuntu's own repo (used together with docker.io).
    as_root apt-get install -y docker-compose-plugin ||
      as_root apt-get install -y docker-compose-v2 ||
      die "Could not install Docker Compose v2. Install docker-compose-plugin or docker-compose-v2 manually."
    as_root docker compose version >/dev/null 2>&1 || die "'docker compose' is still not available."
  fi
}

compose() {
  if [ -n "${APP_PORT:-}" ]; then
    (cd "$APP_DIR" && as_root env "APP_PORT=$APP_PORT" docker compose "$@")
  else
    (cd "$APP_DIR" && as_root docker compose "$@")
  fi
}

env_file() { printf '%s/.env' "$APP_DIR"; }

# Print a value from .env (surrounding quotes removed).
get_env() {
  as_root grep -E "^$1=" "$(env_file)" 2>/dev/null | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true
}

# Set KEY=VALUE in .env, replacing an existing line or appending a new one.
# Values written here are generated (hex or base64), so they need no quoting.
set_env() {
  local key="$1" value="$2" file
  file="$(env_file)"
  if as_root grep -qE "^${key}=" "$file"; then
    as_root sed -i "s|^${key}=.*|${key}=${value}|" "$file"
  else
    printf '%s=%s\n' "$key" "$value" | as_root tee -a "$file" >/dev/null
  fi
}

random_secret() {
  if command -v openssl >/dev/null 2>&1; then
    openssl rand -hex 24
  else
    head -c 24 /dev/urandom | od -An -tx1 | tr -d ' \n'
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
}

# Create .env and replace placeholder database passwords with random ones.
prepare_env() {
  if [ ! -f "$(env_file)" ]; then
    [ -f "$APP_DIR/.env.example" ] || die ".env.example is missing in $APP_DIR"
    log "Creating .env from .env.example"
    as_root cp "$APP_DIR/.env.example" "$(env_file)"
    as_root chmod 600 "$(env_file)"
  fi

  local key value
  for key in DB_PASSWORD DB_ROOT_PASSWORD; do
    value="$(get_env "$key")"
    case "$value" in
      "" | change-me | change-me-root)
        log "Generating a random $key in .env"
        set_env "$key" "$(random_secret)"
        ;;
    esac
  done
}

# APP_KEY is generated by Laravel itself inside the freshly built image.
ensure_app_key() {
  if [ -n "$(get_env APP_KEY)" ]; then
    return 0
  fi
  log "Generating APP_KEY..."
  local key
  key="$(compose run --rm --no-deps -T app php artisan key:generate --show | tr -d '\r' | tail -n 1)"
  case "$key" in
    base64:*) set_env APP_KEY "$key" ;;
    *) die "Could not generate APP_KEY (got: $key)" ;;
  esac
}

app_port() {
  local port="${APP_PORT:-}"
  if [ -z "$port" ]; then
    port="$(get_env APP_PORT | tr -d "' ")"
  fi
  printf '%s' "${port:-8080}"
}

wait_for_health() {
  local port url elapsed=0
  port="$(app_port)"
  url="http://127.0.0.1:${port}/up"
  log "Waiting for the app at $url ..."
  until curl -fsS -o /dev/null "$url"; do
    if [ "$elapsed" -ge "$HEALTH_TIMEOUT" ]; then
      warn "The app did not respond within ${HEALTH_TIMEOUT}s. Recent logs:"
      compose logs --tail=100 app || true
      exit 1
    fi
    sleep 3
    elapsed=$((elapsed + 3))
  done

  local ip
  ip="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
  log "Health check: $(curl -fsS -o /dev/null -w 'HTTP %{http_code}' "$url")"
  log "Done. The app is running:"
  log "  Site:  http://${ip:-SERVER_IP}:${port}/"
  log "  Admin: http://${ip:-SERVER_IP}:${port}/admin"
}

build_and_start() {
  log "Building the image..."
  compose build
  ensure_app_key
  log "Starting containers (migrations and base data run on start)..."
  compose up -d --remove-orphans
  # Remove only this project's old (dangling) images, never other projects' images.
  as_root docker image prune -f --filter "label=app.sirdadfar.project=sirdadfar-saas-starter" >/dev/null 2>&1 || true
  wait_for_health
}

# Create (or keep) the admin account from ADMIN_MOBILE / ADMIN_PASSWORD.
# The values are passed to a one-off container only and are not saved in .env.
create_admin() {
  local mobile="${ADMIN_MOBILE:-}" password="${ADMIN_PASSWORD:-}" name="${ADMIN_NAME:-}"

  if [ -z "$mobile" ] && [ -t 0 ]; then
    read -r -p "Admin mobile (09xxxxxxxxx): " mobile
  fi
  if [ -z "$password" ] && [ -t 0 ]; then
    read -r -s -p "Admin password (at least 8 characters): " password
    printf '\n'
  fi
  if [ -z "$mobile" ] || [ -z "$password" ]; then
    warn "ADMIN_MOBILE / ADMIN_PASSWORD not given; skipping admin creation. Run later: sudo ./deploy.sh admin"
    return 0
  fi
  [[ "$mobile" =~ ^09[0-9]{9}$ ]] || die "ADMIN_MOBILE must be 11 digits and start with 09."
  [ "${#password}" -ge 8 ] || die "ADMIN_PASSWORD must be at least 8 characters."

  local args=(run --rm -T -e "ADMIN_MOBILE=$mobile" -e "ADMIN_PASSWORD=$password")
  if [ -n "$name" ]; then
    args+=(-e "ADMIN_NAME=$name")
  fi
  compose "${args[@]}" app php artisan db:seed --force --class='Database\Seeders\AdminUserSeeder'
}

backup() {
  local dir stamp
  dir="$APP_DIR/backups"
  stamp="$(date +%Y%m%d-%H%M%S)"
  as_root mkdir -p "$dir"
  as_root chmod 700 "$dir"

  log "Backing up MySQL to $dir/db-$stamp.sql.gz ..."
  # The variables are expanded inside the mysql container.
  # shellcheck disable=SC2016
  compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --no-tablespaces "$MYSQL_DATABASE"' |
    gzip | as_root tee "$dir/db-$stamp.sql.gz" >/dev/null

  log "Backing up private documents to $dir/storage-$stamp.tar.gz ..."
  compose exec -T app tar -C /var/www/html/storage -czf - app/private |
    as_root tee "$dir/storage-$stamp.tar.gz" >/dev/null

  log "Backup finished."
}

is_running() {
  [ -n "$(compose ps -q --status running mysql 2>/dev/null || true)" ] &&
    [ -n "$(compose ps -q --status running app 2>/dev/null || true)" ]
}

usage() {
  sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'
}

main() {
  local cmd="${1:-install}"
  require_root

  case "$cmd" in
    install | deploy)
      install_base_packages
      install_docker
      sync_repo
      prepare_env
      build_and_start
      create_admin
      ;;
    update)
      [ -d "$APP_DIR/.git" ] || die "App is not installed in $APP_DIR. Run: $0 install"
      if is_running; then
        backup
      else
        warn "Containers are not running; skipping the pre-update backup."
      fi
      sync_repo
      prepare_env
      build_and_start
      ;;
    admin)
      create_admin
      ;;
    backup)
      backup
      ;;
    artisan)
      shift
      compose exec app php artisan "$@"
      ;;
    logs)
      compose logs -f --tail=200 app
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
