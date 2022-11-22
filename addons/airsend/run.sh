#!/usr/bin/with-contenv bashio

arch="$(apk --print-arch)"
case "$arch" in \
		aarch64) arch='arm64' ;; \
		armv7) arch='arm' ;; \
		amd64) arch='x86_64' ;; \
		i386) arch='x86' ;; \
	esac;
echo "AirSendWebService arch ${arch}"

cd /home
echo ${SUPERVISOR_TOKEN} > hass_api.token
./bin/unix/${arch}/AirSendWebService 99399
php -S 127.0.0.1:80 callback.php
sleep infinity
