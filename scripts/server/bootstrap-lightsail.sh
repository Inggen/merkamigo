#!/usr/bin/env bash
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo 'Run as root'; exit 1; }
. /etc/os-release
[[ "$ID" == ubuntu && "$VERSION_ID" == 24.04 ]] || { echo 'Requires Ubuntu 24.04'; exit 1; }
[[ ! -e /var/lib/inggen-web-bootstrap.done ]] || { echo 'Already configured; do not overwrite live sites.'; exit 0; }
export DEBIAN_FRONTEND=noninteractive
if ! swapon --show=NAME --noheadings | grep -q .; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
printf 'vm.swappiness=10\n' > /etc/sysctl.d/90-small-web-server.conf
sysctl -p /etc/sysctl.d/90-small-web-server.conf
apt-get -o DPkg::Lock::Timeout=120 update -qq
apt-get -o DPkg::Lock::Timeout=120 install -y --no-install-recommends ca-certificates curl unzip software-properties-common
add-apt-repository -y ppa:ondrej/php >/dev/null
apt-get update -qq
mkdir -p /etc/mysql/mariadb.conf.d
cat > /etc/mysql/mariadb.conf.d/60-small-web-server.cnf <<'EOF'
[mysqld]
bind-address=127.0.0.1
innodb_buffer_pool_size=64M
max_connections=15
performance_schema=OFF
table_open_cache=128
tmp_table_size=8M
max_heap_table_size=8M
EOF
apt-get install -y --no-install-recommends nginx mariadb-server php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-sqlite3 php8.4-opcache php8.4-bz2 certbot python3-certbot-nginx ffmpeg
cat > /etc/php/8.4/fpm/conf.d/99-web-server.ini <<'EOF'
expose_php=Off
memory_limit=128M
upload_max_filesize=32M
post_max_size=40M
max_execution_time=60
opcache.enable=1
opcache.memory_consumption=48
opcache.interned_strings_buffer=4
opcache.max_accelerated_files=16000
opcache.validate_timestamps=1
EOF
if [[ -e /etc/php/8.4/fpm/pool.d/www.conf ]]; then
    mv /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.disabled
fi
cat > /etc/nginx/conf.d/00-web-server.conf <<'EOF'
server_tokens off;
client_max_body_size 40m;
EOF
cat > /etc/nginx/sites-available/default <<'EOF'
server { listen 80 default_server; listen [::]:80 default_server; server_name _; return 444; }
EOF
script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
for domain in merkamigo.com inggen.com sittego.com; do
    bash "$script_dir/add-laravel-site.sh" "$domain" "${domain%%.*}"
done
nginx -t
php-fpm8.4 -t
systemctl enable nginx mariadb php8.4-fpm
systemctl restart mariadb php8.4-fpm nginx
if [[ ! -e /root/.config/merkamigo/database.env ]]; then
    install -d -m 700 /root/.config/merkamigo
    db_password=$(openssl rand -hex 24)
    mariadb <<EOF
CREATE DATABASE IF NOT EXISTS merkamigo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'merkamigo'@'localhost' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON merkamigo.* TO 'merkamigo'@'localhost';
EOF
    (umask 077; printf 'DB_CONNECTION=mysql\nDB_HOST=localhost\nDB_PORT=3306\nDB_DATABASE=merkamigo\nDB_USERNAME=merkamigo\nDB_PASSWORD=%s\n' "$db_password" > /root/.config/merkamigo/database.env)
    unset db_password
fi
cat > /etc/systemd/system/merkamigo-queue.service <<'EOF'
[Unit]
Description=Merkamigo queue worker
After=network.target mariadb.service
ConditionPathExists=/var/www/merkamigo.com/current/artisan
[Service]
User=merkamigo
Group=merkamigo
WorkingDirectory=/var/www/merkamigo.com/current
ExecStart=/usr/bin/php8.4 artisan queue:work database --sleep=3 --tries=3 --timeout=60 --memory=96 --max-time=3600
Restart=always
RestartSec=5
Nice=5
TimeoutStopSec=90
[Install]
WantedBy=multi-user.target
EOF
cat > /etc/systemd/system/merkamigo-schedule.service <<'EOF'
[Unit]
Description=Merkamigo scheduled tasks
ConditionPathExists=/var/www/merkamigo.com/current/artisan
[Service]
Type=oneshot
User=merkamigo
Group=merkamigo
WorkingDirectory=/var/www/merkamigo.com/current
ExecStart=/usr/bin/php8.4 artisan schedule:run
EOF
cat > /etc/systemd/system/merkamigo-schedule.timer <<'EOF'
[Unit]
Description=Run Merkamigo scheduler every minute
[Timer]
OnCalendar=*-*-* *:*:00
Persistent=false
[Install]
WantedBy=timers.target
EOF
systemctl daemon-reload
touch /var/lib/inggen-web-bootstrap.done
echo 'BASE_SERVER_READY; queue and scheduler remain disabled until migration.'
php8.4 --version | head -1
free -m
df -h /
