ARG BUILD_FROM
FROM $BUILD_FROM

# Copy data for add-on
COPY run.sh /
RUN chmod a+x /run.sh

# Install AirSendWebService
# RUN mkdir -p /home && cd /home && wget http://devmel.com/dl/AirSendWebService.tgz && (gzip -dc AirSendWebService.tgz | tar -xvzf -) && chmod -R 777 bin
RUN mkdir -p /home && cd /home && wget http://devmel.com/dl/AirSendWebService.tgz && tar -zxvf AirSendWebService.tgz && chmod -R 777 bin

# Start AirSendWebService
CMD [ "/run.sh" ]
