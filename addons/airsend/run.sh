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
./bin/unix/${arch}/AirSendWebService 99399
sleep infinity
