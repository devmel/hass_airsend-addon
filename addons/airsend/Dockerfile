ARG BUILD_FROM
FROM $BUILD_FROM

ENV HASS_HOST=
ENV HASS_TOKEN=
ENV HASS_AUTOINCLUDE=0
ENV HTTPS=1

# Copy data for add-on
COPY nginx.conf /etc/nginx/nginx.conf
COPY hassapi.class.php /home
COPY hass_cb.php /home
COPY state_post.sh /home
COPY states_get.sh /home
COPY run.sh /
RUN chmod a+x /home/state_post.sh
RUN chmod a+x /home/states_get.sh
RUN chmod a+x /run.sh

# Install AirSendWebService
RUN mkdir -p /home && cd /home && wget http://devmel.com/dl/AirSendWebService.tgz && tar -zxvf AirSendWebService.tgz && chmod -R 777 bin

# Install Services
RUN apk add php php-curl nginx openssl

# Generate self-signed certificate
RUN mkdir -p /etc/nginx/ssl
RUN openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
    -subj "/C=FR/ST=France/L=Paris/O=AirSendWebService/CN=localhost" \
    -keyout /etc/nginx/ssl/selfsigned.key \
    -out /etc/nginx/ssl/selfsigned.crt

# Start AirSendWebService
CMD [ "/run.sh" ]
EXPOSE 33863
