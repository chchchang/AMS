LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList
cdate=`date -d '1 hours' '+%Y-%m-%d'`
chour=`date -d '1 hours' '+%H'`
for ch in 12 15 2 30 42 49 50 6 7 48 20 3 5 21 13 43 17
do
	php74 sendPlayListToPumping_cronjob.php ${cdate} ${ch} ${chour};
done

#php74 putToWatchFolder.php

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;

