#!/bin/sh
cd /home/ams/cronjob/processSftpMaterial
logFile=log/`date '+%Y-%m-%d'`.log
datestr=`/bin/date +'%Y%m%d'`
cd /var/www/html/AMS/material/
php74 processSftpMaterial.php >> ${logFile}

#刪除log
cd /home/ams/cronjob/processSftpMaterial/log
find -name "*log" -mtime +90 -exec rm -f {} \;

