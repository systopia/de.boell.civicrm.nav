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
 * Class CRM_Nav_SoapCommand_Read
 * Implementation for a Navision SOAP Read Command
 */
class CRM_Nav_SoapCommand_Read extends CRM_Nav_SoapCommand_BaseSoapCommand {

  private $searchParams;
  /**
   * CRM_Nav_SoapCommand_Read constructor.
   *
   * @param $searchParams
   */
  // TODO: Figure out the correct params here
  public function __construct($readParams) {
    // TODO: for now we just save params
    // in the future this needs to be after a certain timestamp,
    // and probably from an ID
    $this->searchParams = $readParams;
//    example: reads record #1, soapTester
//    $params = array(
//      "Entry_No" => 1,
//    );
  }

  /**
   * @param $soapClient
   *
   * @throws SoapFault
   */
  public function execute($soapClient) {
    // TODO: Implement execute() method.
    $this->result = $soapClient->Read($this->searchParams);
  }
}