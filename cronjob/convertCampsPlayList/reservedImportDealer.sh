LANG="en_US.UTF-8"
export LANG

cd /home/ams/cronjob/convertCampsPlayList
find . -name "importDealerProcessing" -mmin 59 -exec rm -f {}\;
if [ ! -f "importDealerProcessing" ]; then
    php74 reservedImportDealer.php
fi





