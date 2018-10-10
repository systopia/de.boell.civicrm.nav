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
 * Class CRM_Nav_Data_NavRelationshipRecord
 */
class CRM_Nav_Data_NavRelationshipRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiContRelation";

  private $creditor_custom_field_id;
  private $debitor_custom_field_id;

  /**
   * CRM_Nav_Data_NavRelationshipRecord constructor.
   *
   * @param      $nav_data_after
   * @param null $nav_data_before
   *
   * @throws \Exception
   */
  public function __construct($nav_data_after, $nav_data_before = NULL) {
    $this->creditor_custom_field_id = CRM_Nav_Config::get('creditor_custom_field_id');
    $this->debitor_custom_field_id = CRM_Nav_Config::get('debitor_custom_field_id');
    parent::__construct($nav_data_after, $nav_data_before);
  }

  /**
   * @throws \Exception
   */
  protected function convert_to_civi_data() {
    $nav_data                         = $this->get_nav_after_data();
    $relation_code = $this->get_nav_value_if_exist($nav_data, 'Business_Relation_Code');
    $this->civi_data_after['Contact'] = array(
      $this->parse_business_relation($relation_code)           => $this->get_nav_value_if_exist($nav_data, 'No'),
    );
  }

  /**
   * @param $relation_code
   *
   * @return mixed|string
   * @throws \Exception
   */
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


  public function get_delete_record() {
    $contact_data = $this->get_contact_data();
    // just to be safe
    $nav_data                         = $this->get_nav_after_data();
    $relation_code = $this->get_nav_value_if_exist($nav_data, 'Business_Relation_Code');
    $contact_data[$this->parse_business_relation($relation_code)]  = "";
    return $contact_data;
  }

  /**
   * @return mixed
   */
  public function get_contact_data() {
    return $this->civi_data_after['Contact'];
  }
}