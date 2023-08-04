#!/usr/bin/with-contenv bashio

cd /home
arch="$(apk --print-arch)"
case "$arch" in \
		aarch64) arch='arm64' ;; \
		armhf) arch='armhf' ;; \
		armv7) arch='arm' ;; \
		amd64) arch='x86_64' ;; \
		i386) arch='x86' ;; \
	esac;
echo "AirSendWebService arch: ${arch}"
hname="$(hostname -i)"
echo "internal_url: http://${hname}:33863/"

if [ -n "${SUPERVISOR_TOKEN:-}" ]
then
	echo ${SUPERVISOR_TOKEN} > hass_api.token
else
	echo "Not running on Home Assistant machine..."
fi

ulimit -n 4096
./bin/unix/${arch}/AirSendWebService 99399
php -S 127.0.0.1:80 callback.php
sleep infinity
