#!/bin/sh

datestr=`/bin/date +'%Y%m%d'`
cd /var/www/html/AMS/VSM/
php74 resendVSMDefaultBanner.php

#刪除log
cd /home/ams/cronjob/resendVSMDefaultBanner/log
find -name "*log" -mtime +90 -exec rm -f {} \;

