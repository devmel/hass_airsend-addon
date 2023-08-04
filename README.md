<img align="left" width="80" src="https://raw.githubusercontent.com/devmel/hass_airsend-addon/master/icons/icon.png" alt="App icon">

# AirSend Home Assistant

Home Assistant Addon to control an AirSend device in a local network.

## Repository Installation

[![Open your Home Assistant instance and show the add add-on repository dialog with a specific repository URL pre-filled.](https://my.home-assistant.io/badges/supervisor_add_addon_repository.svg)](https://my.home-assistant.io/redirect/supervisor_add_addon_repository/?repository_url=https%3A%2F%2Fgithub.com%2Fdevmel%2Fhass_airsend-addon)


## Manual Installation

1. Into the terminal, run `wget -q -O - https://raw.githubusercontent.com/devmel/hass_airsend-addon/master/install | bash -`
 OR copy the `airsend` folder into your [addon folder](https://developers.home-assistant.io/docs/creating_integration_file_structure/#where-home-assistant-looks-for-integrations).
2. In Supervisor -> Add-on store -> Local add-ons, refresh, install and start AirSend addon


## Install on an external machine
1. Clone repository
2. Go to addons/airsend folder
3. In DockerFile : replace `ARG BUILD_FROM FROM $BUILD_FROM` with your machine architecture build, example : `FROM ghcr.io/home-assistant/amd64-base:3.18` 
4. In callback.php : replace $BASE_HASS_API and $HASS_API_TOKEN with your home automation machine values 
5. In terminal, run `docker build -t hass_airsend-addon .`
6. In terminal, run `docker run -dp 33863:33863 hass_airsend-addon`
