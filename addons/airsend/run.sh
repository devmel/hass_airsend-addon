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

# Start AirSendWebService
ulimit -n 4096
./bin/unix/${arch}/AirSendWebService 99399

CALLBACK_PID=
if [ -n "${SUPERVISOR_TOKEN:-}" ]
then
	hname="$(hostname -i)"
	echo "internal_url: http://${hname}:33863/"
	echo $(bashio::config 'auto_include') > auto_include.cfg
	# Start hass callback service
	php -S 127.0.0.1:80 hass_cb.php & CALLBACK_PID=$!
elif [ -n "${HASS_HOST:-}" ] && [ -n "${HASS_TOKEN:-}" ]
then
	echo "internal_url: http://{YOUR_DOCKER_MACHINE_IP}:33863/"
	# Start hass callback service
	php -S 127.0.0.1:80 hass_cb.php & CALLBACK_PID=$!
else
	echo "Running the AirSendWebService only..."
fi

# Give services time to start
sleep 5

# Monitor services
ASW_PID=$(cat AirSendWebService.lock)
while true; do
	sleep 30

    # Check if AirSendWebService is still running
	if ! kill -0 "$ASW_PID" 2>/dev/null; then
		echo "ERROR: AirSendWebService (PID: $ASW_PID) died..." >&2
		break
    fi

    # Check if Callback service exists and if is still running
	if [[ -n "$CALLBACK_PID" && "$CALLBACK_PID" =~ ^[0-9]+$ ]] && ! kill -0 "$CALLBACK_PID" 2>/dev/null; then
		echo "ERROR: Callback Service (PID: $CALLBACK_PID) died..." >&2
        break
    fi
done

kill $ASW_PID 2>/dev/null || true
kill $CALLBACK_PID 2>/dev/null || true
echo "exiting..."
exit 1
