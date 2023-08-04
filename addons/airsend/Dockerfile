ARG BUILD_FROM
FROM $BUILD_FROM

# Copy data for add-on
COPY hassapi.class.php /home
COPY callback.php /home
COPY poststate.sh /home
COPY run.sh /
RUN chmod a+x /home/poststate.sh
RUN chmod a+x /run.sh

# Install AirSendWebService
RUN mkdir -p /home && cd /home && wget http://devmel.com/dl/AirSendWebService.tgz && tar -zxvf AirSendWebService.tgz && chmod -R 777 bin

RUN apk add php php-curl
# Start AirSendWebService
CMD [ "/run.sh" ]
EXPOSE 33863
