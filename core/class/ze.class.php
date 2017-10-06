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

   public static function cron15() {
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

    $status = $this->getCmd(null, 'status');
    if (!is_object($status)) {
        $status = new zeCmd();
        $status->setLogicalId('status');
        $status->setIsVisible(1);
        $status->setName(__('Statut', __FILE__));
    }
    $status->setType('info');
    $status->setEventOnly(1);
    $status->setUnite('');
    $status->setConfiguration('onlyChangeEvent',1);
    $status->setSubType('string');
    $status->setEqLogic_id($this->getId());
    $status->save();
    
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
    $last_update->setSubType('numeric');
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

  public function preUpdate() {
      log::add('ze', 'debug', 'preUpdate : ');
  }

  public function postUpdate() {
      log::add('ze', 'debug', 'postUpdate : ');
  }

  public function preRemove() {
      log::add('ze', 'debug', 'preRemove : ' . print_r($json, true));
  }

  public function postRemove() {
      log::add('ze', 'debug', 'PostRemove : ' . print_r($json, true));
  }

  /*
   * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
    public function toHtml($_version = 'dashboard') {

    }
   */
  public function pageConf() {
    //sur sauvegarde page de conf update des infos de l'API (lockers existants, batterie, status) + verification que les share sont existants
    ze::login();
  }
  
  public function updateUser() {
    ze::login();  
  }

  public function updateObjects() {
    //scan des lockers par les gateways toutes les 15mn
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
    $charging = $json['charging'] === 'true'? 'en charge': 'pas en charge';
    $charge_level = $json['charge_level'];
    $range = $json['remaining_range'];
    $charging_point = $json['charging_point'];
    $last_update = $json['last_update'];
    
    log::add('ze', 'debug', 'updateBattery : ' . print_r($json, true));
    
    $ze->checkAndUpdateCmd('battery', $charge_level);
    $ze->checkAndUpdateCmd('range', $range);
    $ze->checkAndUpdateCmd('status', $charging);
    $ze->checkAndUpdateCmd('ChargingPoint', $charging_point);
    $ze->checkAndUpdateCmd('lastUpdate', $last_update);
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
 
  /*     * **********************Getteur Setteur*************************** */
}

class zeCmd extends cmd {
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
   * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
    return true;
    }
   */

  public function execute($_options = array()) {
    return;
  }

  /*     * **********************Getteur Setteur*************************** */
}


