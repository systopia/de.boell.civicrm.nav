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
 * Class CRM_Nav_SoapCommand_BaseSoapCommand
 * abstract base class for SOAP commands
 */
abstract class CRM_Nav_SoapCommand_BaseSoapCommand {

  protected $result;

  /**
   * CRM_Nav_SoapCommand_BaseSoapCommand constructor.
   */
  public function __construct() {
  }

  public function getSoapResult(){
    return $this->result;
  }

  /**
   * @param $soapClient
   *
   * @return mixed
   */
  abstract public function execute($soapClient);

}