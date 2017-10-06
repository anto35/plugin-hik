<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ze extends eqLogic {

   public static function cron5() {
      ze::updateObjects();
    }

  public function postSave() {
    $battery = $this->getCmd(null, 'battery');
    if (!is_object($battery)) {
        $battery = new zeCmd();
        $battery->setLogicalId('battery');
        $battery->setIsVisible(1);
        $battery->setName(__('Battery', __FILE__));
    }
    $battery->setUnite('%');
    $battery->setType('info');
    $battery->setEventOnly(1);
    $battery->setSubType('numeric');
    $battery->setIsHistorized(1);
    $battery->setEqLogic_id($this->getId());
    $battery->save();

    $range = $this->getCmd(null, 'range');
    if (!is_object($range)) {
        $range = new zeCmd();
        $range->setLogicalId('range');
        $range->setIsVisible(1);
        $range->setName(__('range', __FILE__));
    }
    $range->setUnite(' kms');
    $range->setType('info');
    $range->setEventOnly(1);
    $range->setSubType('numeric');
    $range->setIsHistorized(1);
    $range->setEqLogic_id($this->getId());
    $range->save();

    $charging = $this->getCmd(null, 'charging');
    if (!is_object($charging)) {
        $charging = new zeCmd();
        $charging->setLogicalId('charging');
        $charging->setIsVisible(1);
        $charging->setName(__('Charging', __FILE__));
    }
    $charging->setType('info');
    $charging->setEventOnly(1);
    $charging->setUnite('');
    $charging->setConfiguration('onlyChangeEvent',1);
    $charging->setSubType('binary');
    $charging->setEqLogic_id($this->getId());
    $charging->save();
    
    $plugged = $this->getCmd(null, 'plugged');
    if (!is_object($plugged)) {
        $plugged = new zeCmd();
        $plugged->setLogicalId('plugged');
        $plugged->setIsVisible(1);
        $plugged->setName(__('Plugged', __FILE__));
    }
    $plugged->setType('info');
    $plugged->setEventOnly(1);
    $plugged->setUnite('');
    $plugged->setConfiguration('onlyChangeEvent',1);
    $plugged->setSubType('binary');
    $plugged->setEqLogic_id($this->getId());
    $plugged->save();
    
    $last_update = $this->getCmd(null, 'last_update');
    if (!is_object($last_update)) {
        $last_update = new zeCmd();
        $last_update->setLogicalId('last_update');
        $last_update->setIsVisible(1);
        $last_update->setName(__('lastUpdate', __FILE__));
    }
    $last_update->setUnite('');
    $last_update->setType('info');
    $last_update->setEventOnly(1);
    $last_update->setSubType('string');
    $last_update->setEqLogic_id($this->getId());
    $last_update->save();
    
    $charging_point = $this->getCmd(null, 'charging_point');
    if (!is_object($charging_point)) {
        $charging_point = new zeCmd();
        $charging_point->setLogicalId('charging_point');
        $charging_point->setIsVisible(1);
        $charging_point->setName(__('ChargingPoint', __FILE__));
    }
    $charging_point->setUnite('');
    $charging_point->setType('info');
    $charging_point->setEventOnly(1);
    $charging_point->setSubType('string');
    $charging_point->setEqLogic_id($this->getId());
    $charging_point->save();
   
    ze::updateObjects();
  }

  public function pageConf() {
    ze::login();
  }
  
  public function updateUser() {
    ze::login();  
  }

  public function updateObjects() {
    foreach (eqLogic::byType('ze', true) as $vehicle) {
        ze::updateBattery($vehicle->getLogicalId());
      }
  }
  
  public function updateBattery($VIN) {
    $ze = ze::byLogicalId($VIN, 'ze');
    if (!is_object($ze)) {
        return;
    }
    ze::login();
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL,"https://www.services.renault-ze.com/api/vehicle/" . $VIN . "/battery");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    
    $token = config::byKey('token','ze');
    $headers = array();
    $headers[] = "Authorization: Bearer " . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $json = json_decode(curl_exec($ch), true);
    $charging = $json['charging'] ? 1:0;
    $plugged = $json['plugged'] ? 1:0;
    $charge_level = $json['charge_level'];
    $range = $json['remaining_range'];
    $charging_point = $json['charging_point'];
    $last_update = new DateTime();
    $last_update->setTimestamp($json['last_update']/1000);
    
    $ze->checkAndUpdateCmd('battery', $charge_level);
    $ze->checkAndUpdateCmd('range', $range);
    $ze->checkAndUpdateCmd('charging', $charging);
    $ze->checkAndUpdateCmd('plugged', $plugged);
    $ze->checkAndUpdateCmd('charging_point', $charging_point);
    $ze->checkAndUpdateCmd('last_update', $last_update->format('Y-m-d H:i:s'));
    curl_close ($ch);
  }
  
  public function login() {
    if (time() < config::byKey('timestamp','ze')) {
        return;
    }
    log::add('ze', 'debug', 'Retour1 : ');
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,"https://www.services.renault-ze.com/api/user/login");
    curl_setopt($curl, CURLOPT_POST, 1);
    $headers = [
      'Content-Type: application/json'
    ];
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);   
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $fields_string = "{\"username\":\"" . config::byKey('username','ze') . "\",\"password\":\"" . config::byKey('password','ze ') . "\"}";
    curl_setopt($curl,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($curl, CURLOPT_POST, 1);
    $json = json_decode(curl_exec($curl), true);
    curl_close ($curl);

    $timestamp = time() + (2 * 60 * 60);
    config::save('timestamp', $timestamp,  'ze');

    config::save('token', $json['token'],  'ze');
    foreach ($json['user']['associated_vehicles'] as $vehicle) {
        $ze = ze::byLogicalId($vehicle['VIN'], 'ze');
        if (!is_object($ze)) {
            
            $ze = new ze();
            $ze->setEqType_name('ze');
            $ze->setLogicalId($vehicle['VIN']);
            $ze->setName('ZE_ ' . $vehicle['VIN']);
            $ze->setIsEnable(1);
            $ze->save();
            event::add('ze::found', array(
                'message' => __('Nouveau véhicule ' . $vehicle['VIN'], __FILE__),
              ));
        }
    }
  }
}

class zeCmd extends cmd {

  public function execute($_options = array()) {
    return;
  }
}


