cd /home/ams/cronjob/getCsmsOrderResult/
logFile=log/`date '+%Y-%m-%d'`.log
php74 /var/www/html/AMS/casting/getCsmsOrderResult.php  >> ${logFile}
#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;