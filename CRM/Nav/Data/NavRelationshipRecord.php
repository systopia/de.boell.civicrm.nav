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


class CRM_Nav_Data_NavRelationshipRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiContRelation";

  // local
  private $creditor_custom_field_id = 'custom_42';
  private $debitor_custom_field_id = 'custom_43';

// HBS
//  private $creditor_custom_field_id = 'custom_164';
//  private $debitor_custom_field_id  = 'custom_165';

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
  }



  protected function convert_to_civi_data() {
    $nav_data                         = $this->get_nav_after_data();
    $relation_code = $this->get_nav_value_if_exist($nav_data, 'Business_Relation_Code');
    $this->civi_data_after['Contact'] = array(
      $this->parse_business_relation($relation_code)           => $this->get_nav_value_if_exist($nav_data, 'No'),
    );
  }

  private function parse_business_relation($relation_code) {
    switch ($relation_code) {
      case 'KREDITOR':
        return $this->creditor_custom_field_id;
      case 'DEBITOR':
        return $this->debitor_custom_field_id;
      default:
        throw new Exception("Unknown Business Relation {$relation_code}. Couldn't parse civiContRelation");
    }
  }

  public function get_contact_data() {
    return $this->civi_data_after['Contact'];
  }
}