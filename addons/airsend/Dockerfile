ARG BUILD_FROM
FROM $BUILD_FROM

ENV HASS_HOST=
ENV HASS_TOKEN=
ENV HASS_AUTOINCLUDE=0

# Copy data for add-on
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

RUN apk add php php-curl
# Start AirSendWebService
CMD [ "/run.sh" ]
EXPOSE 33863
