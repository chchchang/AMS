#!/bin/bash

if [ "$#" != "0" ]; then
php74 getTimes.php "${1}"
else
i=20190202
while [ "${i}" != "20190229" ]
do
	php74 getTimes.php ${i}
	i=$(($i+1))
done
fi
