<?php

class HassAPI{
	var $states = null;
	
    function __construct($BASE_HASS_API, $HASS_API_TOKEN) {
		$this->BASE_HASS_API = $BASE_HASS_API;
		$this->HASS_API_TOKEN = $HASS_API_TOKEN;
		$fbvalue = @file_get_contents('auto_include.cfg');
		$this->auto_include = filter_var($fbvalue, FILTER_VALIDATE_BOOLEAN);
    }

	public function isAuthorized() {
        if(!empty($this->BASE_HASS_API) && !empty($this->HASS_API_TOKEN)){
            return true;
        }
        return false;
	}
	public function isAutoInclude() {
        return $this->auto_include;
	}
	function searchEntityId($uid) {
		if($this->loadStates()){
			foreach ($this->states as $value) {
				if($value['entity_uid_sha256'] == $uid){
					return $value['entity_id'];
				}
			}
		}
		return null;
	}

	function searchEntitiesFromChannelAndType($channel, $type) {
		$ret = array();
		if(isset($channel) && $this->loadStates()){
			foreach ($this->states as $value) {
				if(array_key_exists('attributes', $value) && array_key_exists('channel', $value['attributes'])){
					if(self::isChannelCompatible($channel, $value['attributes']['channel'])){
						$foundid = false;
						$entity_id = $value['entity_id'];
						//Sensors
						$is_sensor = (stripos($entity_id, "sensor.") !== false) ? true : false;
						if($is_sensor && array_key_exists('device_class', $value['attributes'])){
							$dclass = $value['attributes']['device_class'];
							$dclass = str_replace('humidity', 'r_humidity', $dclass);
							if($dclass == $type){
								$foundid = true;
							}
						}
						//Others
						if($type == 'level' || $type == 'state' || $type == 'toggle' || $type == 'data'){
							$foundid = true;
						}
						if($foundid){
							$ret[] = $entity_id;
						}
					}
				}
			}
		}
		return $ret;
	}

	function setState($entity_id, $type, $state, $timestamp_ms, $channel = null) {
		$reloadcache = false;
		$attributes = null;
		if($entity_id == null && isset($channel)){
			$reloadcache = true;
			//New entity
			$entity_id = "sensor.".self::toUniqueChannelName($channel);
			$attributes = array('channel' => $channel);
			if($type === 'temperature'){
				$entity_id .= '_temp';
				$attributes['device_class'] = 'temperature';
				$attributes['unit_of_measurement'] = '°C';
			}else if($type === 'illuminance'){
				$entity_id .= '_ill';
				$attributes['device_class'] = 'illuminance';
				$attributes['unit_of_measurement'] = 'lx';
			}else if($type === 'r_humidity'){
				$entity_id .= '_rh';
				$attributes['device_class'] = 'humidity';
				$attributes['unit_of_measurement'] = '%';
			}else if($type === 'toggle' || $type === 'data'){
				//auto_include disabled for toggle, it's too messy on dashboard
				return ;
			}
		}
		$content = $this->getState($entity_id);
		if($content == null && $attributes != null && $this->auto_include){
			$content = array('attributes' => $attributes);
		}
		if($content != null){
			if($attributes != null){
				$content['attributes'] = array_merge($content['attributes'], $attributes);
			}
			$current_position = null;
			if($type === 'data'){
				if ($state && strlen($state) > 20){
				   $state = substr($state, 0, 17) . '...';
				}
				if(array_key_exists('state', $content) && $content['state'] == $state){
					$state .= '+';
				}
				$reloadcache = true;
			}
			if($type === 'toggle'){
				if(array_key_exists('state', $content) && $content['state'] == 'pressed'){
					$state .= '+';
				}
				$reloadcache = true;
			}
			if($type === 'level'){
				$is_cover = (stripos($entity_id, "cover.") !== false) ? true : false;
				$current_position = intval($state);
				$state = $current_position.'%';
				if($current_position >= 100){
					$state = $is_cover ? 'open': 'on';
				}else if($current_position <= 0){
					$state = $is_cover ? 'closed': 'off';
				}
			}
			$content['state'] = $state;
			if($current_position !== null){
				if(array_key_exists('attributes', $content) && array_key_exists('supported_features', $content['attributes']) && ($content['attributes']['supported_features']&0x4) == 0x4){
					$content['attributes']['current_position'] = $current_position;
				}
			}
			unset($content['entity_uid_sha256']);
			unset($content['last_changed']);
			unset($content['last_updated']);
			//$dti = DateTimeImmutable::createFromFormat('U.u', ($timestamp_ms/1000));
			//$content['last_updated'] = $dti->format('Y-m-d').'T'.$dti->format('H:i:s').'+00:00';
			$this->state_post($entity_id, $content, $reloadcache);
			return true;
		}
		return false;
	}

	function getState($entity_id){
		if($this->loadStates() && isset($entity_id)){
			foreach ($this->states as $value) {
				if($value['entity_id'] == $entity_id){
					return $value;
				}
			}
		}
		return null;
	}

	public static function convertNotesToStates($notes){
		$ret = array();
		if(is_array($notes) && count($notes) > 0){
			foreach ($notes as $i => $note) {
                $ovalue = $note['value'];
				$stype = null;
                $svalue = null;
				if($note['type'] == 0){			//STATE
                    if(!is_numeric($ovalue)){
                        $statemap = array("","PING","PROG","UNPROG","RESET",
                        "","","","","","","","","","","","","STOP","TOGGLE","OFF","ON","CLOSE","OPEN",
                        "","","","","","","","","","","MIDDLE","DOWN","UP","LEFT","RIGHT","USERPOSITION");
                        $ovalue = array_search($ovalue, $statemap);
                    }
					if(is_numeric($ovalue)){
						$stype = 'state';
						switch(intval($ovalue)){
							case 18:		//TOGGLE
								$stype = 'toggle';
								$svalue = 'pressed';
							break;
							case 19:		//OFF
								$stype = 'level';
								$svalue = 0;
							break;
							case 20:		//ON
								$stype = 'level';
								$svalue = 100;
							break;
							case 17:		//STOP
								$svalue = 'stop';
							break;
							case 33:		//MIDDLE
							case 38:		//USERPOS
								$svalue = 'user';
							break;
							case 34:		//DOWN
								$stype = 'level';
								$svalue = 0;
							break;
							case 35:		//UP
								$stype = 'level';
								$svalue = 100;
							break;
						}
					}
				}else if($note['type'] == 1){	//DATA
					$stype = 'data';
					$svalue = $ovalue;
				}else if($note['type'] == 2){	//TEMPERATURE
					$stype = 'temperature';
					$svalue = (floor((floatval($ovalue) - 273.15) * 10.0 + 0.5) / 10.0);	//Kelvins to celcius
				}else if($note['type'] == 3){	//ILLUMINANCE
					$stype = 'illuminance';
					$svalue = floatval($ovalue);
                }else if($note['type'] == 4){	//R_HUMIDITY
					$stype = 'r_humidity';
                    $svalue = intval($ovalue);
				}else if($note['type'] == 9){	//LEVEL
					$stype = 'level';
					$svalue = intval($ovalue);
				}
				if($stype != null){
					$ret[] = array($stype, $svalue); 
				}
			}
		}
		return $ret;
	}

	public static function isChannelCompatible($obj1, $obj2){
        $result = false;
        if(isset($obj1) && isset($obj2)){
            $ch1 = self::toBasicChannel($obj1);
            $ch2 = self::toBasicChannel($obj2);
            $result = ($ch1 == $ch2);
        }
        return $result;
    }

	public static function toUniqueChannelName($channel){
        $result = $channel['id'];
        if($result){
            $uniquefield = array('source', 'mac', 'seed');
            foreach ($uniquefield as $field) {
                if(array_key_exists($field, $channel)){
                    $result .= "_";
                    $result .= $channel[$field];
                }
            }
        }
        return $result;
    }

	public static function toBasicChannel($channel){
        $result = array();
        $uniquefield = array('id', 'source');
        foreach ($channel as $key => $value) {
            if (in_array($key, $uniquefield)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

	private function state_post($entity_id, $content, $reloadcache = false){
		$json = json_encode($content, JSON_FORCE_OBJECT);
		if(strpos($this->BASE_HASS_API, "supervisor") !== false){
			//Bug in supervisor proxy => use bash
			exec('./state_post.sh "'.$entity_id.'" "'.base64_encode($json).'"', $output, $retval);
		}else{
			$res = HassAPI::request($this->BASE_HASS_API."/states/".$entity_id, $json, 'POST', $this->HASS_API_TOKEN);
		}
		trigger_error("state_post ".$reloadcache." : ".$entity_id." ".$content['state'], E_USER_NOTICE);
		if($reloadcache){
			$this->states_get_cache();
		}
	}
	private function states_get_cache(){
		if(strpos($this->BASE_HASS_API, "supervisor") !== false){
			//Bug in supervisor proxy => use bash
			exec('./states_get.sh', $output, $retval);
		}else{
			$res = HassAPI::request($this->BASE_HASS_API."/states", null, 'GET', $this->HASS_API_TOKEN);
			if($res !== false && isset($res) && is_array($res) && isset($res['data'])){
				file_put_contents("states.json", $res['data']);
			}
		}
	}

	private function loadStates() {
		$ret = false;
		if($this->states){
			$ret = true;
		}else{
			if($this->isAuthorized()){
				$loaded = false;
				$data_age = @(time() - filemtime('states.json'));
				if($data_age > 100){
					$this->states_get_cache();
				}
				$data = @file_get_contents('states.json');
				if(isset($data)){
					$this->states = json_decode($data, true);
					if($this->states){
						foreach ($this->states as &$value) {
							$h = str_split(hash('sha256', $value['entity_id']),12)[0];
							$val = intval($h, 16);
							$value['entity_uid_sha256'] = $val;
						}
						$loaded = true;
						$ret = true;
					}
				}
				if($loaded == false){
					trigger_error("API GET states wrong response", E_USER_ERROR);
				}
			}
		}
		return $ret;
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
					curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
					curl_setopt($curl,CURLOPT_XOAUTH2_BEARER, $token);
					$options = array(CURLOPT_HTTPHEADER => array('Content-Type: application/json'));
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