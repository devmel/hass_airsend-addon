#!/usr/bin/with-contenv bashio


payload=`echo $2 | base64 -d`
echo -en $payload | curl -s --data-binary @- -X POST -H "Authorization: Bearer ${SUPERVISOR_TOKEN}" -H "Content-Type: application/json" http://supervisor/core/api/states/$1 
