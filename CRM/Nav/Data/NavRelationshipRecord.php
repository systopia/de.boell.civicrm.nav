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


class CRM_Nav_Data_NavRelationship extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiContRelation";

  public function __construct($navision_data) {
    parent::__construct($navision_data);
  }

  protected function convert_to_civi_data() {
    $nav_data                         = $this->get_nav_after_data();
    $this->civi_data_after['Contact'] = array(
      'custom_147'              => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
      'relation_code'           => $this->get_nav_value_if_exist($nav_data, 'Business_Relation_Code'),
      // TODO: how is the relation done here? :\
      'external_identifier'     => $this->get_nav_value_if_exist($nav_data, 'No'),
    );
  }

  public function get_contact_data() {
    return $this->civi_data_after['Contact'];
  }
}