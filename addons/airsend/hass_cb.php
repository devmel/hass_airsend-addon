<?php
require_once dirname(__FILE__) . "/hassapi.class.php";

$SUPERVISOR_TOKEN = @getenv("SUPERVISOR_TOKEN");
$HASS_HOST= @getenv("HASS_HOST");
$HASS_TOKEN= @getenv("HASS_TOKEN");

$auto_include = false;
if(!empty($HASS_HOST) && !empty($HASS_TOKEN)){
	$machine = HassAPI::extractHostAndPort($HASS_HOST);
	$HASS_API_URL = "http://".$machine['hostname'].":".$machine['port']."/api";
	$HASS_API_TOKEN = $HASS_TOKEN;
	$HASS_AUTOINCLUDE = @getenv("HASS_AUTOINCLUDE");
	$auto_include = filter_var($HASS_AUTOINCLUDE, FILTER_VALIDATE_BOOLEAN);
}else if(!empty($SUPERVISOR_TOKEN)){
	$HASS_API_URL = "http://supervisor/core/api";
	$HASS_API_TOKEN = $SUPERVISOR_TOKEN;
	$fbvalue = @file_get_contents('auto_include.cfg');
	$auto_include = filter_var($fbvalue, FILTER_VALIDATE_BOOLEAN);
}

$api = new HassAPI($HASS_API_URL, $HASS_API_TOKEN, $auto_include);

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
							$api->setState($entity_id, $state[0], $state[1], $val['timestamp'], $val['channel']);
						}
					}else{
						$api->setState($entity_id, 'error', 'error_'.$val['type'], $val['timestamp'], $val['channel']);
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
								$api->setState($entity_id, $state[0], $state[1], $val['timestamp'], $val['channel']);
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
