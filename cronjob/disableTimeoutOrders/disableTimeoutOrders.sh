cd /home/ams/cronjob/disableTimeoutOrders/
logFile=log/`date '+%Y-%m-%d'`.log
php74 disableTimeoutOrders.php  >> ${logFile}
#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;