<?php
require_once dirname(__FILE__) . "/hassapi.class.php";

$HASS_API_PATH = getenv("HASS_HOSTNAME") == 'supervisor' ? '/core' : '';
$BASE_HASS_API = "http://" . getenv("HASS_HOSTNAME") . ":" . getenv("HASS_PORT") . $HASS_API_PATH . "/api";
$HASS_API_TOKEN = @file_get_contents('hass_api.token');

$api = new HassAPI($BASE_HASS_API, $HASS_API_TOKEN);

if(!$api->isAuthorized()){
	header("HTTP/1.1 401 Unauthorized");
	trigger_error("Unauthorized", E_USER_ERROR);
	die();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true, 512, JSON_BIGINT_AS_STRING);
if (is_array($data) && isset($data['events'])) {
	foreach ($data['events'] as $i => $val) {
		if(isset($val['channel']) && isset($val['type']) && isset($val['thingnotes'])){
			//Transfer event
			if(isset($val['thingnotes']['uid'])){
				$entity_id = $api->searchEntityId($val['thingnotes']['uid']);
				if(isset($entity_id)){
					if($val['type'] == 3 || $val['type'] == 2 || $val['type'] == 1){
						$states = $api->convertNotesToStates($val['thingnotes']['notes']);
						foreach ($states as $j => $state) {
							$api->setState($entity_id, $state[0], $state[1], $val['timestamp']);
						}
					}else{
						$api->setState($entity_id, 'error', 'error_'.$val['type'], $val['timestamp']);
					}
				}
			//Interrupt event
			}else{
				if($val['type'] == 3){			//Event type GOT (sensor)
					$isreliable = false;
					if($val['reliability'] > 0x6 && $val['reliability'] < 0x47){
						$isreliable = true;
					}
					if($isreliable == true){
						$states = $api->convertNotesToStates($val['thingnotes']['notes']);
						foreach ($states as $j => $state) {
							//Search entity and update
							$entities = $api->searchEntitiesFromChannelAndType($val['channel'], $state[0]);
							foreach ($entities as $k => $entity_id) {
								$api->setState($entity_id, $state[0], $state[1], $val['timestamp']);
							}
							//Creates if not exists
							if(count($entities) == 0){
								trigger_error("new channel found : ".json_encode($val['channel'])." ".$state[0]." ".$state[1], E_USER_NOTICE);
								$api->setState(null, $state[0], $state[1], $val['timestamp'], $val['channel']);
							}
						}
					}
				}
			}
		}
	}
}

?>
