#!/usr/bin/env bash
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo 'Run as root'; exit 1; }
domain=${1:?Usage: add-laravel-site.sh domain site_user}
site_user=${2:?Specify a unique Linux user}
[[ $domain =~ ^[a-z0-9][a-z0-9.-]*\.[a-z]{2,63}$ && $domain != *..* ]] || { echo 'Invalid domain'; exit 1; }
[[ $site_user =~ ^[a-z][a-z0-9_]{0,30}$ ]] || { echo 'Invalid site user'; exit 1; }
[[ ! -e /etc/nginx/sites-available/$domain && ! -e /etc/php/8.4/fpm/pool.d/$site_user.conf ]] || { echo 'Site or PHP pool exists; refusing overwrite'; exit 1; }
id "$site_user" >/dev/null 2>&1 || useradd --create-home --home-dir "/var/www/$domain" --shell /bin/bash "$site_user"
chmod 750 "/var/www/$domain"
usermod -aG "$site_user" www-data
install -d -m 750 -o "$site_user" -g "$site_user" "/var/www/$domain/current/public"
if [[ ! -e /var/www/$domain/current/public/index.php && ! -e /var/www/$domain/current/public/index.html ]]; then
    printf '<!doctype html><html lang="es"><meta charset="utf-8"><title>En preparación</title><h1>Sitio en preparación</h1></html>\n' > "/var/www/$domain/current/public/index.html"
    chown "$site_user:$site_user" "/var/www/$domain/current/public/index.html"
    chmod 640 "/var/www/$domain/current/public/index.html"
fi
cat > "/etc/php/8.4/fpm/pool.d/$site_user.conf" <<EOF
[$site_user]
user=$site_user
group=$site_user
listen=/run/php/php8.4-$site_user.sock
listen.owner=www-data
listen.group=www-data
listen.mode=0660
pm=ondemand
pm.max_children=1
pm.process_idle_timeout=10s
pm.max_requests=100
catch_workers_output=yes
security.limit_extensions=.php
EOF
cat > "/etc/nginx/sites-available/$domain" <<EOF
server {
listen 80;
listen [::]:80;
server_name $domain www.$domain;
root /var/www/$domain/current/public;
index index.php index.html;
charset utf-8;
access_log /var/log/nginx/$domain.access.log;
error_log /var/log/nginx/$domain.error.log;
location / { try_files \$uri \$uri/ /index.php?\$query_string; }
location ~* ^/(?:storage|uploads)/.*\.php\$ { deny all; }
location ~ \.php\$ {
    try_files \$uri =404;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    fastcgi_param HTTP_PROXY "";
    fastcgi_pass unix:/run/php/php8.4-$site_user.sock;
}
location ~ /\.(?!well-known).* { deny all; }
}
EOF
ln -sf "/etc/nginx/sites-available/$domain" "/etc/nginx/sites-enabled/$domain"
nginx -t
php-fpm8.4 -t
systemctl restart php8.4-fpm nginx
echo "Site prepared at /var/www/$domain/current/public; DNS and TLS were not changed."
