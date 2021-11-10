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
 * Class CRM_Nav_Handler_ContactHandler
 */
class CRM_Nav_Handler_ContactHandler extends CRM_Nav_Handler_HandlerBase {

  /**
   * CRM_Nav_Handler_ContactHandler constructor.
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

    if ($this->record->is_delete()) {
      $nav_id = $this->record->get_individual_navision_id();
      $contact_id = $this->get_contact_id_from_nav_id($nav_id);
      $this->delete_nav_id_from_contact($contact_id);
      $this->record->set_consumed();
      $this->log("Deleted nav_id from contact");
      return;
    }

    $contact_id = $this->record->get_or_create_contact();
    if ($contact_id < '0') {
      // contact is created, all new values are already added as well
      $this->record->set_consumed();
      return;
    }

    $nav_id = $this->record->get_nav_id();
    // add NavId to Contact
    $this->add_nav_id_to_contact($contact_id, $nav_id);

    // Calculate stuff to do
    $this->record->calc_differences();

    // update
    $this->record->update();
    // apply valid change operations
    $this->record->apply_changes();
    // delete
    $this->record->delete();
    // i3val
    $this->record->i3val();

    $this->record->set_consumed();
    return;
  }

  /**
   * @param $contact_id
   * @param $nav_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function add_nav_id_to_contact($contact_id, $nav_id) {
    $values = array(
      'id'          => $contact_id,
      $this->navision_custom_field  =>  $nav_id,
    );
    $result = CRM_Nav_Utils::civicrm_nav_api('Contact', 'create', $values);
    if ($result['is_error'] == 1) {
      $this->log("Couldn't add Navision ID to contact ({$contact_id}).");
      throw new Exception("Couldn't add Navision ID to contact ({$contact_id}).");
    }
  }

  /**
   * @param $contact_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function delete_nav_id_from_contact($contact_id) {
    // if we don't have a contact_id --> no need for delete of navId
    if (empty($contact_id)) {
      return;
    }
    $result = CRM_Nav_Utils::civicrm_nav_api('Contact', 'create', array(
      'sequential' => 1,
      'contact_type' => "Individual",
      'id' => $contact_id,
      $this->navision_custom_field => " ",
    ));
    if ($result['is_error'] == '1') {
      throw new Exception("Error occured while removing NavisionId from Contact {$contact_id}. Error Message: {$result['error_message']}");
    }
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiContact';
  }
}