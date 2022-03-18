#!/bin/bash
cd /home/ams/cronjob/IabFiles
logFile=log/`date '+%Y-%m-%d'`_hours.log

echo 開始產生檔案 >> ${logFile}
php74 /var/www/html/AMS/getOrbitLogs_hours.php >> ${logFile}

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;