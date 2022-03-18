#!/bin/bash
cd /home/ams/cronjob/IabFiles

orbitlogFile=`date -d "yesterday" '+%Y-%m-%d'`_orbitLogs.txt
#orbitlogFileHours=`date -d "yesterday" '+%Y-%m-%d'`_orbitLogs_hours.txt
materialFile=`date '+%Y-%m-%d'`_materials.txt
orderListFile=`date '+%Y-%m-%d'`_orderList.txt
orderFile=`date '+%Y-%m-%d'`_orders.txt
logFile=log/`date '+%Y-%m-%d'`.log

if [ ! -f "$orbitlogFile" ] || [ ! -f "$materialFile" ] || [ ! -f "$orderListFile" ] || [ ! -f "$orderFile" ]; then
echo
date '+%Y-%m-%d %H:%M:%S' >> ${logFile}
echo 開始產生檔案 >> ${logFile}
php74 /var/www/html/AMS/_produceIabFiles.php >> ${logFile}
#php74 /www/html/AMS/getOrbitLogs.php

if [ -r materials_append.txt ]; then
  echo "append orers" >> ${logFile}
  cat materials_append.txt >> `date '+%Y-%m-%d'`_materials.txt
fi

if [ -r orders_append.txt ]; then
  echo "append materials" >> ${logFile}
  cat orders_append.txt >> `date '+%Y-%m-%d'`_orders.txt
fi

if [ -r orderList_append.txt ]; then
  echo "append orderList" >> ${logFile}
  cat orderList_append.txt >> `date '+%Y-%m-%d'`_orderList.txt
fi
for hour in 00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23
do
  #cat hours/`date -d "yesterday" '+%Y-%m-%d'`_${hour}_orbitLogs.txt >> ${orbitlogFileHours}
  cat hours/`date -d "yesterday" '+%Y-%m-%d'`_${hour}_orbitLogs.txt >> ${orbitlogFile}
done

FILESIZE=$(stat -c%s "$orbitlogFile")
if [ "$FILESIZE" != "0"  ]; then
touch allfiledone
fi

fi

if [ -f "allfiledone" ]; then
echo 
date '+%Y-%m-%d %H:%M:%S' >> ${logFile}
echo 開始上傳檔案 >> ${logFile}
#scp `date '+%Y-%m-%d'`_materials.txt `date '+%Y-%m-%d'`_orderList.txt `date '+%Y-%m-%d'`_orders.txt `date -d "yesterday" '+%Y-%m-%d'`_orbitLogs.txt reporter@172.17.254.155:/oradata/reporter/share/tps2/AMS
./put_cloud_sftp  ${orbitlogFile} ${materialFile} ${orderListFile} ${orderFile}# ${orbitlogFileHours}
rm allfiledone
echo
date '+%Y-%m-%d %H:%M:%S' >> ${logFile}
echo 處理完成 >> ${logFile}
echo 刪除過期資料 >> ${logFile}
find -type f -name "*.txt" -mtime +180 -exec rm {} \;
find hours/ -type f -name "*.txt" -mtime +180 -exec rm {} \;
echo 處理完成 >> ${logFile}
fi

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;

