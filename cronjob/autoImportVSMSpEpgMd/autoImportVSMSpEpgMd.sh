#!/bin/bash
cd /var/www/html/AMS/VSM/epgBannerSpMd/
logFile=log/`date '+%Y-%m-%d'`.log
php74 /var/www/html/AMS/VSM/epgBannerSpMd/autoImportMulti.php
echo 完成

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;
