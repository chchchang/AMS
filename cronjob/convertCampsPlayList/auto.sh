LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList
cdate=`date -d '1 days' '+%Y-%m-%d'`
for ch in 12 15 2 30 42 49 50 6 7 48 20 3 5 21 13 43 17 16 14 18 36 47 40
do
	php74 sendPlayListToPumping_cronjob.php ${cdate} ${ch} all;
done

#php74 putToWatchFolder.php

#處理log
find log/ -type f -name "*.log" -mtime +90 -exec rm {} \;
find log/ -type f -name "*.log" -mtime +90 -exec rm {} \;

