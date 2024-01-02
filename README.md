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
2. Generate your API key: [link to your profile](https://my.home-assistant.io/redirect/profile/) (last part of the page)
3. Go to addons/airsend folder
4. Depending on your machine architecture, run in a terminal. Example with amd64: `docker build --build-arg "BUILD_FROM=ghcr.io/home-assistant/amd64-base:3.19" -t hass_airsend-addon .`
5. Depending on your home assistant server and your token, run in a terminal. Example with homeassistant.local:8123:  
```bash
docker run -dp 33863:33863 \
-e HASS_HOSTNAME='homeassistant.local' \
-e HASS_PORT='8123' \
-e SUPERVISOR_TOKEN='eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiI3YTJjYjE2M2VjZjQ0MGM0OGUwYzdkOTc2MjM4YWY5MCIsImlhdCI6MTY2ODg5NzA4OCwiZXhwIjoxOTg0MjU3MDg4fQ.gyDg_jYbD561OdQ0IngAMga-4LE3DTsd6bEIGkITGTc' \
hass_airsend-addon
```
