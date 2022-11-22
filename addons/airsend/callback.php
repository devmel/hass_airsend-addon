<?php
$BASE_HASS_API = "http://supervisor/core/api";
$SUPERVISOR_TOKEN = @file_get_contents('hass_api.token');

if(empty($SUPERVISOR_TOKEN)){
	header("HTTP/1.1 401 Unauthorized");
	exit;
}

class API{
	var $states = null;
	
    function __construct($BASE_HASS_API, $SUPERVISOR_TOKEN) {
		$this->BASE_HASS_API = $BASE_HASS_API;
		$this->SUPERVISOR_TOKEN = $SUPERVISOR_TOKEN;
		$this->loadStates();
    }

	static function request($url, $data = null, $method = 'GET', $token = null) {
		$result = false;
		if (extension_loaded('curl') === true){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			if (preg_match('~^(?:DELETE|GET|POST|PUT)$~i', $method) > 0){
				if (preg_match('~^(?:POST|PUT)$~i', $method) > 0){
					if (is_array($data) === true){
						foreach (preg_grep('~^@~', $data) as $key => $value){
							$data[$key] = sprintf('@%s', rtrim(str_replace('\\', '/', realpath(ltrim($value, '@'))), '/') . (is_dir(ltrim($value, '@')) ? '/' : ''));
						}
						if (count($data) != count($data, COUNT_RECURSIVE)){
							$data = http_build_query($data, '', '&');
						}
					}
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				}
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
				if (isset($token)){
					$token = str_replace('"', "", $token);
					$options = array(CURLOPT_HTTPHEADER => array('Content-Type: application/json' , "Authorization: Bearer ".$token));
					curl_setopt_array($curl, $options);
				}
				$result = array();
				$result['data'] = curl_exec($curl);
				$result['info'] = curl_getinfo($curl);
				if (curl_errno($curl)) {
					$result['error'] = curl_error($curl);
				}
			}
			curl_close($curl);
		}
		return $result;
	}
	function loadStates() {
		$res = API::request($this->BASE_HASS_API."/states", null, 'GET', $this->SUPERVISOR_TOKEN);
        if($res !== false && isset($res) && is_array($res) && isset($res['data'])){
			$this->states = json_decode($res['data'], true);
			if($this->states){
				foreach ($this->states as &$value) {
					$h = str_split(hash('sha256', $value['entity_id']),12)[0];
					$val = intval($h, 16);
					$value['entity_uid_sha256'] = $val;
				}
			}
		}
	}
	
	function getState($entity_id){
		if($this->states){
			foreach ($this->states as $value) {
				if($value['entity_id'] == $entity_id){
					return $value;
				}
			}
		}
		return null;
	}

	function postState($entity_id, $content){
		$json = json_encode($content, JSON_FORCE_OBJECT);
		if(strpos($this->BASE_HASS_API, "supervisor") !== false){
			//Bug in supervisor proxy => use bash
			exec('./poststate.sh "'.$entity_id.'" "'.base64_encode($json).'"', $output, $retval);
			trigger_error("postState ".($retval)." ".print_r($output, true), E_USER_WARNING);
		}else{
			$res = API::request($this->BASE_HASS_API."/states/".$entity_id, $json, 'POST', $this->SUPERVISOR_TOKEN);
			trigger_error("postState ".json_encode($res), E_USER_WARNING);
		}
	}

	function searchEntityId($uid) {
		if($this->states){
			foreach ($this->states as $value) {
				if($value['entity_uid_sha256'] == $uid){
					return $value['entity_id'];
				}
			}
		}
		return null;
	}

	function setState($entity_id, $state) {
		$content = $this->getState($entity_id);
		if($content != null){
			//Update state
			$content['state'] = $state;
			if(($content['attributes']['supported_features']&0x4) == 0x4){
				$position = 50;
				if($state == 'open'){
					$position = 100;
				}else if($state == 'closed'){
					$position = 0;
				}
				$content['attributes']['current_position'] = $position;
			}
			unset($content['entity_uid_sha256']);
			unset($content['last_changed']);
			unset($content['last_updated']);
			$this->postState($entity_id, $content);
			return true;
		}
		return false;
	}

	function setPosition($entity_id, $position) {
		$content = $this->getState($entity_id);
		if($content != null){
			$is_cover = (stripos($entity_id, "cover.") !== false) ? true : false;
			//Update position
			$state = 'stop';
			if(($content['attributes']['supported_features']&0x4) == 0x4){
				$content['attributes']['current_position'] = $position;
				$state = $position.'%';
			}
			if($position == 100){
				$state = $is_cover ? 'open': 'on';
			}else if($position == 0){
				$state = $is_cover ? 'closed': 'off';
			}
			$content['state'] = $state;
			unset($content['entity_uid_sha256']);
			unset($content['last_changed']);
			unset($content['last_updated']);
			$this->postState($entity_id, $content);
			return true;
		}
		return false;
	}
}

$api = new API($BASE_HASS_API, $SUPERVISOR_TOKEN);
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
