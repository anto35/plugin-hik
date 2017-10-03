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
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */
    public function updateUser() {
      ze::authCloud();
    }


    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
    }

    public function postSave() {
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

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
      if ($this->getType() == 'info') {
        return;
      }
      ze::update();
      
    }

    /*     * **********************Getteur Setteur*************************** */

    public function authCloud() {
      $url = 'https://www.services.renault-ze.com/api/user/login';
      $user = config::byKey('username','ze');
      $pass = config::byKey('password','ze');
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL,$url);
      curl_setopt($curl, CURLOPT_POST, 1);
      $headers = [
        'Content-Type: application/x-www-form-urlencoded'
      ];
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      $fields = array(
        '_username' => urlencode($user),
        '_password' => urlencode($pass),
      );
      $fields_string = '';
      foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
      rtrim($fields_string, '&');
      curl_setopt($curl,CURLOPT_POST, count($fields));
      curl_setopt($curl,CURLOPT_POSTFIELDS, $fields_string);
      curl_setopt($curl,CURLOPT_RETURNTRANSFER , 1);
      $json = json_decode(curl_exec($curl), true);
      curl_close ($curl);
      $timestamp = time() + (2 * 60 * 60);
      config::save('token', $json['token'],  'ze');
      config::save('token', $json['user']['vehicle_details'],  'ze');
      config::save('timestamp', $timestamp,  'ze');
      log::add('ze', 'debug', 'Retour : ' . print_r($json, true));
      return;
    }


    public function callCloud($url,$data = array('format' => 'json')) {
    $url = 'https://api.the-keys.fr/fr/api/v2/' . $url;
    if (isset($data['format'])) {
      $url .= '?_format=' . $data['format'];
    }
    if (time() > config::byKey('timestamp','thekeys')) {
      thekeys::authCloud();
    }
    $request_http = new com_http($url);
    $request_http->setHeader(array('Authorization: Bearer ' . config::byKey('token','thekeys')));
    if (!isset($data['format'])) {
      $request_http->setPost($data);
    }
    $output = $request_http->exec(30);
    $json = json_decode($output, true);
    log::add('ze', 'debug', 'URL : ' . $url);
    //log::add('ze', 'debug', 'Authorization: Bearer ' . config::byKey('token','thekeys'));
    log::add('ze', 'debug', 'Retour : ' . $output);
    return $json;
  }
}


