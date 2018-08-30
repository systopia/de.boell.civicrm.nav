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

class CRM_Nav_Handler_RelationshipHandler extends CRM_Nav_Handler_HandlerBase {

  private $creditor_custom_field_id = 'custom_164';
  private $debitor_custom_field_id  = 'custom_165';

  public function __construct($record) {
    parent::__construct($record);
  }

  /**
   * Check if the record is a civiContRelation
   * @return bool
   */
  protected function check_record_type() {
    return $this->record->get_type() == 'civiContRelation';
  }

  public function process() {
    if (!$this->check_record_type()) {
      return;
    }
    $contact_data = $this->record->get_contact_data();
    $contact_id = $this->get_contact_id_from_nav_id($contact_data[$this->navision_custom_field]);

    // TODO:
    // How to distinguish between creditor/debitor
    civicrm_api3('Contact', 'create', array(
      'sequential'                      => 1,
      'contact_type'                    => "Individual",
      $this->creditor_custom_field_id   => $contact_data['relation_code'],
      'id'                              => $contact_id,
//      $this->debitor_custom_field_id => "",
    ));
    // TODO: Activity Tracker works automatically?

    // mark record as consumed
    $this->record->set_consumed();
  }

}