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
 * Class CRM_Nav_Handler_ProcessHandler
 */
class CRM_Nav_Handler_ProcessHandler extends CRM_Nav_Handler_HandlerBase {

  private $process_id;

  /**
   * CRM_Nav_Handler_ProcessHandler constructor.
   *
   * @param $record
   */
  public function __construct($record, $debug = false) {
    $this->process_id = CRM_Nav_Config::get('process_id');
    parent::__construct($record, $debug);
  }

  /**
   * @throws \Exception
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
      throw new Exception("Couldn't find Contact for NavID {$nav_id}. ProcessRecord wont be processed.");
    }

    if ($this->check_delete_record()) {
      $relationship_id = $this->get_relationship($this->record->get_process_id());
      $this->delete_entity($relationship_id, 'Relationship');
      $this->record->set_consumed();
      return;
    }
    if ($this->check_new_record()) {
      $this->write_relationship_to_db($contact_id, "");
      $this->record->set_consumed();
      return;
    }
    $relationship_id = $this->get_relationship($this->record->get_process_id());
    $this->write_relationship_to_db($contact_id, $relationship_id);

    $this->record->set_consumed();
  }

  /**
   * @param $process_id
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_relationship($process_id) {
    $result = civicrm_api3('Relationship', 'get', array(
      'sequential' => 1,
      'is_active' => 1,
      $this->process_id => $process_id,
    ));
    if ($result['count'] > 1) {
      throw new Exception("Found {$result['count']} Results for given ProcessID {$process_id}. Aborting");
    }
    if ($result['count'] == '1') {
      return $result['id'];
    }
    return "";
  }

  /**
   * @param $contact_id
   * @param $relationship_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function write_relationship_to_db($contact_id, $relationship_id) {
    $values = array(
      'contact_id_a'    => $contact_id,
      'contact_id_b'    => $this->hbs_contact_id,
    );
    if (!empty($relationship_id)) {
      $values['id'] = $relationship_id;
    }
    $values = $values + $this->record->get_relationship_data();
    // TODO: if other Option Values need creation it shall be done here
    CRM_Nav_Config::check_or_create_option_value($values[CRM_Nav_Config::get('Candidature_Process_Code')]);
    $result = civicrm_api3('Relationship', 'create', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("[ProcessHandler] Couldn't write Relationship to DB. '{$result['error_message']}'");
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