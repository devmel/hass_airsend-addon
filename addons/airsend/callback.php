<?php
require_once dirname(__FILE__) . "/hassapi.class.php";

$BASE_HASS_API = "http://supervisor/core/api";
$HASS_API_TOKEN = @file_get_contents('hass_api.token');

//On an external machine, replace with your ha values
//$BASE_HASS_API = "http://homeassistant.local:8123/api";
//$HASS_API_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiI3YTJjYjE2M2VjZjQ0MGM0OGUwYzdkOTc2MjM4YWY5MCIsImlhdCI6MTY2ODg5NzA4OCwiZXhwIjoxOTg0MjU3MDg4fQ.gyDg_jYbD561OdQ0IngAMga-4LE3DTsd6bEIGkITGTc';

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
			$entity_id = $api->searchEntityId($val['thingnotes']['uid']);
			if(isset($entity_id)){
				//Transfer event
				if($val['type'] == 3 || $val['type'] == 2 || $val['type'] == 1){
					$notes = $val['thingnotes']['notes'];
					if(is_array($notes) && count($notes) > 0){
						foreach ($notes as $i => $note) {
							$ovalue = $note['value'];
							if($note['type'] == 0){			//STATE
								if(is_numeric($ovalue)){
									$ovalue = intval($ovalue);
									$state = null;
									$position = 0;
									switch($ovalue){
										case 18:		//TOGGLE
											$state = 'pressed';
										break;
										case 19:		//OFF
											$position = 0;
										break;
										case 20:		//ON
											$position = 100;
										break;
										case 17:		//STOP
											$state = 'stop';
										break;
										case 33:		//MIDDLE
										case 38:		//USERPOS
											$state = 'user';
										break;
										case 34:		//DOWN
											$position = 0;
										break;
										case 35:		//UP
											$position = 100;
										break;
									}
									if($state){
										$api->setState($entity_id, $state);
									}else{
										$api->setPosition($entity_id, $position);
									}
								}
							}else if($note['type'] == 1){	//DATA
								$api->setState($entity_id, 'pressed');
							}else if($note['type'] == 9){	//LEVEL
								$api->setPosition($entity_id, intval($ovalue));
							}
						}
					}
				}else{
					$api->setState($entity_id, 'error_'.$val['type']);
				}
			}
		}
	}
}

?>
