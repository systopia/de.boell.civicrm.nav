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

abstract  class CRM_Nav_Data_EntityData_Base {

  protected $_contact_id;

  abstract protected function get_civi_data();

    /**
     * @param $message
     */
  protected function log($message) {
    if ($this->debug) {
      CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] " . $message);
    }
  }

  /**
   * @param $entity
   * @param $values
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  protected function create_entity($entity, $values) {
    $result = civicrm_api3($entity, 'create', $values);
    if ($result['is_error']) {
      $this->log("Failed to create {$entity}-Entity with values " . json_encode($values));
    }
    return $result;
  }

  protected function get_entity($entity, $values) {
    $result = civicrm_api3($entity, 'get', $values);
    if ($result['is_error']) {
      $this->log("Failed to get {$entity}-Entity with values " . json_encode($values));
    }
    return $result;
  }

  public function set_contact_id($contact_id) {
    $this->_contact_id = $contact_id;
  }
}