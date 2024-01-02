ARG BUILD_FROM
FROM $BUILD_FROM

ENV HASS_HOSTNAME=supervisor
ENV HASS_PORT=80

# Copy data for add-on
COPY hassapi.class.php /home
COPY callback.php /home
COPY state_post.sh /home
COPY states_get.sh /home
COPY run.sh /
RUN chmod u+x /home/state_post.sh
RUN chmod u+x /home/states_get.sh
RUN chmod u+x /run.sh

# Install AirSendWebService
RUN mkdir -p /home && cd /home && wget http://devmel.com/dl/AirSendWebService.tgz && tar -zxvf AirSendWebService.tgz && chmod -R 777 bin

RUN apk add --no-cache php php-curl

# Configure S6-overlay
COPY s6-rc.d /etc/s6-overlay/s6-rc.d
RUN chmod -R 755 /etc/s6-overlay/s6-rc.d

# Start AirSendWebService
EXPOSE 33863
