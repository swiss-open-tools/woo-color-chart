#!/bin/sh
# Idempotent demo setup, run by the "setup" service in docker-compose.yml.
set -eu
cd /var/www/html

echo "Waiting for WordPress files..."
i=0
until [ -f wp-config.php ]; do i=$((i+1)); [ "$i" -gt 60 ] && { echo "wp-config.php missing"; exit 1; }; sleep 2; done

if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$WP_URL" --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" --skip-email
fi

wp plugin is-installed woocommerce || wp plugin install woocommerce
wp plugin activate woocommerce woo-color-chart
wp rewrite structure '/%postname%/' --hard || true
wp option update woocommerce_currency CHF
wp option update woocommerce_default_country CH
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json || true

if [ -z "$(wp post list --post_type=product --name=demo-spray-400ml --field=ID)" ]; then
  wp eval-file /demo/demo-product.php
fi
echo "Demo ready: $WP_URL/product/demo-spray-400ml/"
