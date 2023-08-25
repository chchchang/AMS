LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList
cdate=`date -d '1 hours' '+%Y-%m-%d'`
chour=`date -d '1 hours' '+%H'`

php74 sendPlayListToPumping_cronjob.php ${cdate} ${chour};


