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

class CRM_Nav_Handler_StatusHandler extends CRM_Nav_Handler_HandlerBase {

  public function __construct($record) {
    parent::__construct($record);
  }

  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $nav_id = $this->record->get_individual_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    if($contact_id == "") {
      $this->log("Couldn't find Contact for NavID {$nav_id}. StatusRecord wont be processed.");
      // TODO: set this consumed? How do we proceed with entires we cannot match?
      return;
    }
    $relationship_id = $this->get_civi_relationship_id($contact_id, $this->hbs_contact_id, 'start_date', $this->record->get_Status_start_date());
    $this->write_relationship_to_db($contact_id, $relationship_id);

    $this->record->set_consumed();
  }

  private function write_relationship_to_db($contact_id, $relationship_id){
    $values = array(
      'id'              => $relationship_id,
      'contact_id_a'    => $contact_id,
      'contact_id_b'    => $this->hbs_contact_id,
    );
    $values = $values + $this->record->get_relationship_data();
    $result = civicrm_api3('Relationship', 'create', $values);
    if ($result['is_error'] == '1') {
      $this->log("[StatusHandler] Couldn't write Relationship to DB. '{$result['error_message']}'");
      throw new Exception("[StatusHandler] Couldn't write Relationship to DB. '{$result['error_message']}'");
    }
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiContStatus';
  }
}