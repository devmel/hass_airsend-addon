#!/usr/bin/with-contenv bashio

curl -o states.json -s -X GET -H "Authorization: Bearer ${SUPERVISOR_TOKEN}" -H "Content-Type: application/json" http://supervisor/core/api/states 
