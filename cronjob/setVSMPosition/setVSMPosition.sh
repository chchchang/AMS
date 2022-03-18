#!/bin/sh

datestr=`/bin/date +'%Y%m%d'`
cd /var/www/html/AMS/VSM/
php74 setVSMPosition.php >> /home/ams/cronjob/setVSMPosition/log/${datestr}.log 2>&1

#刪除log
cd /home/ams/cronjob/setVSMPosition/log
find -name "*log" -mtime +90 -exec rm -f {} \;

