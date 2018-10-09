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
 * Class CRM_Nav_Handler_RelationshipHandler
 */
class CRM_Nav_Handler_RelationshipHandler extends CRM_Nav_Handler_HandlerBase {

  /**
   * CRM_Nav_Handler_RelationshipHandler constructor.
   *
   * @param $record
   */
  public function __construct($record, $debug = false) {
    parent::__construct($record, $debug);
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiContRelation';
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $nav_id = $this->record->get_individual_navision_id();
    $contact_id = $this->get_contact_id_from_nav_id($nav_id);
    if (empty($contact_id)) {
      throw new Exception("Couldn't get Contact to Navision id {$nav_id}");
    }
    if ($this->check_delete_record()) {
      $contact_data = $this->record->get_delete_record();
    } else {
      $contact_data = $this->record->get_contact_data();
    }
    $contact_data['id'] = $contact_id;
    $this->create_civi_entity($contact_data, 'Contact');

    // mark record as consumed
    $this->record->set_consumed();
  }

}