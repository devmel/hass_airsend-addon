<img align="left" width="80" src="https://raw.githubusercontent.com/devmel/hass_airsend-addon/master/icons/icon.png" alt="App icon">

# AirSend Home Assistant

Home Assistant Addon to control an AirSend device in a local network.

## Repository Installation

[![Open your Home Assistant instance and show the add add-on repository dialog with a specific repository URL pre-filled.](https://my.home-assistant.io/badges/supervisor_add_addon_repository.svg)](https://my.home-assistant.io/redirect/supervisor_add_addon_repository/?repository_url=https%3A%2F%2Fgithub.com%2Fdevmel%2Fhass_airsend-addon)


## Manual Installation

#### 1. Install the Add-on
Run the following command in your terminal:
```bash
wget -q -O - https://raw.githubusercontent.com/devmel/hass_airsend-addon/master/install   bash -
```
**OR**
Manually copy the `airsend` folder into your [Home Assistant addons directory](https://developers.home-assistant.io/docs/creating_integration_file_structure/#where-home-assistant-looks-for-integrations).

#### 2. Start the Add-on
1. Go to **Supervisor** → **Add-on Store** → **Local add-ons**.
2. Refresh the list, then install and start the **AirSend** add-on.


## Install on an external machine
#### 1. Clone the Repository
```bash
git clone https://github.com/devmel/hass_airsend-addon
cd hass_airsend-addon/addons/airsend
```

#### 2. Generate a Home Assistant API Key
1. Go to your [Home Assistant profile](https://my.home-assistant.io/redirect/profile/).
2. Navigate to the **Security** tab.
3. Generate a **long-lived access token** (use this as `HASS_TOKEN`).

---

#### 3. Build the Docker Image
Replace `amd64` with your machine's architecture if needed (`armhf`, `armv7`, `aarch64`, etc.):
```bash
docker build --build-arg "BUILD_FROM=ghcr.io/home-assistant/amd64-base:3.22" -t hass_airsend-addon .
```

---

#### 4. Run the Docker Container
Adjust the environment variables according to your setup:
```bash
docker run -dp 33863:33863 \
  -e HTTPS=1 \
  -e HASS_HOST='homeassistant.local:8123' \
  -e HASS_TOKEN='your_token_here' \
  -e HASS_AUTOINCLUDE=0 \
  hass_airsend-addon
```

##### Environment Variables:
- HTTPS=1: Enables HTTPS. Use HTTPS=0 if you cannot handle self-signed certificates.
- HASS_HOST: Your Home Assistant server address and port (e.g., homeassistant.local:8123).
- HASS_TOKEN: The long-lived access token you generated.
- HASS_AUTOINCLUDE: Set to 0 to disable auto-inclusion of the add-on in Home Assistant.

---

### Important Notes
- **Security**: Never share your `HASS_TOKEN`.
- **HTTPS**: If `HTTPS=0`, ensure your network is secure.
