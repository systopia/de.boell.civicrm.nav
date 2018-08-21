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

class CRM_Nav_Handler_ProcessHandler extends CRM_NAV_Handler_HandlerBase {

  public function __construct() {
  }

  /**
   * @throws \Exception
   */
  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $nav_id = $this->record->get_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    if($contact_id == "") {
      $this->log("Couldn't find Contact for NavID {$nav_id}. ProcessRecord wont be processed.");
      return;
    }
    $relationship_id = $this->get_relationship($this->record->get_process_id());
    $this->write_relationship_to_db($contact_id, $relationship_id);

    $this->record->set_consumed();
  }

  private function get_relationship($process_id) {
    $result = civicrm_api3('Relationship', 'get', array(
      'sequential' => 1,
      'custom_126' => $process_id,
    ));
    if ($result['count'] != 1) {
      $this->log("Didn't find conclusive result for {$process_id}");
      return "";
    }
    return $result['values']['id'];
  }

  private function write_relationship_to_db($contact_id, $relationship_id) {
    $values = array(
      'id'              => $relationship_id,
      'contact_id_a'    => $contact_id,
      'contact_id_b'    => $this->hbs_contact_id,
    );
    $values = array_merge($values, $this->record->get_relationship_data());
    $result = civicrm_api3('Relationship', 'create', $values);
    if ($result['is_error'] == '1') {
      $this->log("Couldn't write Relationship to DB. '{$result['error_message']}'");
      throw new Exception("Couldn't write Relationship to DB. '{$result['error_message']}'");
    }
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiProcess';
  }
}