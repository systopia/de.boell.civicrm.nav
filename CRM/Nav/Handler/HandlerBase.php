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

abstract class CRM_Nav_Handler_HandlerBase {

  protected $record;
  protected $hbs_contact_id = "4";
  private $debug;
// local
//  protected $navision_custom_field = 'custom_41';
  // hbs
  protected $navision_custom_field = 'custom_147';

  public function __construct($record) {
    // Fixme: make configurable, probably extension wide config
    $this->debug = TRUE;
    $this->record = $record;
  }

  abstract protected function check_record_type();

  protected function get_contact_id_from_nav_id($navId) {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      $this->navision_custom_field => $navId,
    ));
    if ($result['count'] != 1) {
      return "";
    }
    return $result['id'];
  }

  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  abstract public function process();
}