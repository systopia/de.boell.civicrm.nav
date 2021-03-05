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
 * Class CRM_Nav_Handler_StatusHandler
 */
class CRM_Nav_Handler_StatusHandler extends CRM_Nav_Handler_HandlerBase {

  /**
   * CRM_Nav_Handler_StatusHandler constructor.
   *
   * @param $record
   */
  public function __construct($record, $debug = false) {
    parent::__construct($record, $debug);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    if (!empty($this->record->get_error_message())) {
      return;
    }

    $nav_id = $this->record->get_individual_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    if($contact_id == "") {
      throw new Exception("Couldn't find Contact for NavID {$nav_id}. StatusRecord wont be processed.");
    }

    if ($this->check_new_record()) {
      $relationship_data = $this->record->get_relationship_data();
      $this->write_relationship_to_db($contact_id, "", $relationship_data);
      $this->record->set_consumed();
      return;
    }

    if ($this->check_delete_record()) {
      $relationship_data = $this->record->get_relationship_data();
      $relationship_id = $this->get_civi_relationship_id($contact_id, $this->hbs_contact_id, array('start_date' => $this->record->get_Status_start_date(), 'relationship_type_id' =>  $relationship_data['relationship_type_id']));
      $this->delete_entity($relationship_id, 'Relationship');
      $this->record->set_consumed();
      return;
    }

    $relationship_data_before = $this->record->get_relationship_data('before');
    $relationship_id = $this->get_civi_relationship_id($contact_id, $this->hbs_contact_id, array('start_date' => $this->record->get_Status_start_date('before'), 'relationship_type_id' =>  $relationship_data_before['relationship_type_id']));
    $relationship_data = $this->record->get_relationship_data('after');
    $this->write_relationship_to_db($contact_id, $relationship_id, $relationship_data);

    $this->record->set_consumed();
  }

  /**
   * @param $contact_id
   * @param $relationship_id
   * @param $relationship_data
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function write_relationship_to_db($contact_id, $relationship_id, $relationship_data) {
    // see https://projekte.systopia.de/issues/13750
    // empty parameter not accepted here. We create a new Relationship
    if (!empty($relationship_id)) {
      $relationship_data['id'] = $relationship_id;
    }
    $relationship_data['contact_id_a'] = $contact_id;
    $relationship_data['contact_id_b'] = $this->hbs_contact_id;

    $result = civicrm_api3('Relationship', 'create', $relationship_data);
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