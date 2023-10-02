#!/bin/bash

cd /var/www/html

# WordPressの初期設定
wp core install \
  --url="http://localhost:8080" \
  --title="Test Blog" \
  --admin_user='wordpress' \
  --admin_password='wordpress' \
  --admin_email='info@example.com' \
  --allow-root

# 一般設定
wp language core install --allow-root --activate ja
wp option update --allow-root timezone_string 'Asia/Tokyo'
wp option update --allow-root date_format 'Y-m-d'
wp option update --allow-root time_format 'H:i'

# パーマリンク設定
wp option update permalink_structure --allow-root /%postname%/

# 不要なプラグインを削除
wp plugin delete --allow-root hello.php
wp plugin delete --allow-root akismet

# PS OpenRPA プラグインの使用
wp plugin activate ps_openrpa
