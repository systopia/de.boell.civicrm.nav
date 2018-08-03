<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: P. Batroff (batroff@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


/**
 * This class will handle the SOAP communication to the
 * Navision API. All communication should go through this class
 */
class CRM_Nav_SOAPConnector {

  // SOAP Parameters
  private $wsdl;
  private $soapClient;
  private $location;
  private $password;
  private $login;
  private $soap_options;

  const types = array("civiContact","civiContRelation","civiContStatus","civiProcess",);

  /**
   * CRM_Nav_SOAPConnector constructor.
   *
   * @param string $type
   * @param bool $debug
   *
   * @throws \Exception
   */
  public function __construct($type = "civiContact", $debug = TRUE) {
    if (!$this->verify_type($type)) {
      throw new Exception("Invalid type. Please provide a valid type of (e.g. civiContact)");
    }
    // get credentials from file
    $this->getSoapCredentials($type);
    // set NAV API URLs
    $this->setSoapLocations($type);
    // set SOAP options
    $this->setSoapOptions();
    // initialize SOAP Object
    // TODO: add debug option to extension (probably in settings page)
    try {
      if ($debug) {
        $this->soapClient = new CRM_Nav_SOAPDebugClient($this->wsdl, $this->soap_options);
      } else {
        $this->soapClient = new SoapClient($this->wsdl, $this->soap_options);
      }
    } catch (Exception $e) {
      error_log($e->getMessage());
    }
  }


  /**
   * Checks if the type in constructor is valid
   * @param $type
   *
   * @return bool
   */
  private function verify_type($type) {
    return in_array($type, self::types);
  }

  /**
   * Executes a given soap Command
   * @param $navSoapCommand
   *
   * @return mixed
   */
  public function executeCommand($navSoapCommand) {
    return $navSoapCommand->execute($this->soapClient);
  }

  // TODO: function to set location for this SOAPObject (needs to be called form Command).
  // E.g. Read(contact) Command needs location for CiviContact

  private function getSoapCredentials($type){
    $this->wsdl = "resources/wsdl/{$type}.wsdl";
    $pw_file = "resources/pw.txt";
    $login_file = "resources/user.txt";
    $file_content = explode("\n", file_get_contents($pw_file));
    $this->password = array_pop(array_reverse($file_content));
    $file_content = explode("\n", file_get_contents($login_file));
    $this->login = array_pop(array_reverse($file_content));
  }

  /**
   * Sets locations/URLs for the SOAP API.
   * TODO: make this configurable? (config file, settings form...)
   */
  private function setSoapLocations($type){
    $this->location = "http://10.1.0.148:7037/NAVUSER/WS/Heinrich%20Boell%20Stiftung%20e.V./Page/{$type}";
  }

  /**
   * Set SOAP options
   * TODO: If needed, add a settings form
   */
  private function setSoapOptions() {
    $context = stream_context_create([
      'ssl' => [
        // set some SSL/TLS specific options
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      ]
    ]);

    $this->soap_options = [
      "trace" => 1,
      "exceptions" => TRUE,
      "cache_wsdl" => WSDL_CACHE_NONE,
      'login' => $this->login,
      'password' => $this->password,
      "stream_context" => $context,
      "location" => $this->location,
    ];
  }

}