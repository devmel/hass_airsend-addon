<?php

class HassAPI{
	var $states = null;
	
    function __construct($BASE_HASS_API, $HASS_API_TOKEN) {
		$this->BASE_HASS_API = $BASE_HASS_API;
		$this->HASS_API_TOKEN = $HASS_API_TOKEN;
        if($this->isAuthorized()){
		    $this->loadStates();
        }
    }

	public function isAuthorized() {
        if(!empty($this->BASE_HASS_API) && !empty($this->HASS_API_TOKEN)){
            return true;
        }
        return false;
	}

	function loadStates() {
		$res = HassAPI::request($this->BASE_HASS_API."/states", null, 'GET', $this->HASS_API_TOKEN);
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

	private function postState($entity_id, $content){
		$json = json_encode($content, JSON_FORCE_OBJECT);
		if(strpos($this->BASE_HASS_API, "supervisor") !== false){
			//Bug in supervisor proxy => use bash
			exec('./poststate.sh "'.$entity_id.'" "'.base64_encode($json).'"', $output, $retval);
		}else{
			$res = HassAPI::request($this->BASE_HASS_API."/states/".$entity_id, $json, 'POST', $this->HASS_API_TOKEN);
		}
		trigger_error("postState ".$entity_id." ".$content['state'], E_USER_NOTICE);
	}

    private static function request($url, $data = null, $method = 'GET', $token = null) {
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
}

?>