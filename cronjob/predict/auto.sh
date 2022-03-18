LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/predict
logFile=log/`date '+%Y-%m-%d'`.log
php74 getChList.php >> ${logFile}
php74 getTimes.php >> ${logFile}
if [ $? -ne 0 ]; then
	export CLASSPATH=mail.jar:.
	java Mail ttwang@cht.com.tw ,chia_chi_chang@cht.com.tw "[通知]缺少`date -d '-1 day' +'%Y/%m/%d'`頻道曝光數資料"
	exit
fi
php74 predict.php `date +'%Y%m%d'` 60 14 >> ${logFile}

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;

