#!/usr/bin/env sh
set -e

echo "Fixing /var/run/php and /var/log permissions..."
mkdir -p /var/run/php /var/log
chown -R www-data:www-data /var/run/php /var/log
chmod -R 777 /var/run/php /var/log

echo "Fixing permissions..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage/framework/views/livewire
mkdir -p /var/www/storage/framework/views/livewire/classes
chmod -R 777 /var/www/storage/framework/views/livewire
chown -R www-data:www-data /var/www/storage/framework/views
chmod -R 777 /var/www/storage/framework/views/livewire/classes

echo "Configuring PHP-FPM pool..."
cat > /usr/local/etc/php-fpm.d/www.conf <<'EOF'
[www]
listen = 9000
pm = dynamic
pm.max_children = 100
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 50
pm.process_idle_timeout = 30s
pm.max_requests = 1000
user = www-data
group = www-data
EOF

echo "PHP-FPM pool configured. Starting supervisor..."
exec supervisord -c /etc/supervisord.conf