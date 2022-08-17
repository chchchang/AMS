cd /home/ams/cronjob/deleteOutDatedData/
logFile=log/`date '+%Y-%m-%d'`.log
echo 刪除過期託播單與素材  >> ${logFile}
find /var/www/html/AMS/material/uploadedFile/ -type f -mtime +180 > /var/www/html/AMS/outdatedMaterialList.dat
php74 /var/www/html/AMS/deleteOldOrders.php  >> ${logFile}
echo 刪除CSMS介接檔案  >> ${logFile}
find /var/www/html/AMS/order/851/ -type f -name "*.xls" -mtime +180 -exec rm -f {} \;
echo 刪除CSMS處理完成檔案  >> ${logFile}
find /var/www/html/AMS/casting/local/N/ -type f -name "*.fin" -mtime +180 -exec rm -f {} \;
find /var/www/html/AMS/casting/local/C/ -type f -name "*.fin" -mtime +180 -exec rm -f {} \;
find /var/www/html/AMS/casting/local/S/ -type f -name "*.fin" -mtime +180 -exec rm -f {} \;
#echo 強制刪除1年以上的素材
#find /var/www/html/AMS/material/uploadedFile/ -type f -mtime +300 -exec rm -f {} \;

echo 刪除log  >> ${logFile}
find /var/www/html/AMS/0b7d6e5a265d20715443e19a1f7609c6/log/ -type f -name "AMS.log.*" -mtime +180 -exec rm -f {} \;

echo 刪除曝光數檔案  >> ${logFile}
find /var/www/html/AMS/predict/export/ -type f -name "*.xls" -mtime +180 -exec rm -f {} \;

echo 完成  >> ${logFile}

#處理log
find log/ -type f -name "*.log" -mtime +180 -exec rm {} \;