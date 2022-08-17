LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList

if [ ! -f "importDealerProcessing" ]; then
    php74 reservedImportDealer.php
fi





