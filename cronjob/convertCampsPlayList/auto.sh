LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList
cdate=`date -d '1 days' '+%Y-%m-%d'`

php74 sendPlayListToPumping_cronjob.php ${cdate} all;

#處理log
find log/ -type f -name "*.log" -mtime +90 -exec rm {} \;
find log/ -type f -name "*.log" -mtime +90 -exec rm {} \;

