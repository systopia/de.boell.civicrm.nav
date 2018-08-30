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


class CRM_Nav_Data_NavStatusRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiStatus";
  private $hbs_contact_id = "4";
  private $relationship_type_mapping = array(
    'Vertrauensdozent_in'       => '15',
    'Stipendiat_in'             => '12',
    'Promotionsstipendiat_in'   => '11',
  );

  public function __construct($navision_data) {
    parent::__construct($navision_data);
  }

  /**
   * @throws \Exception
   */
  protected function convert_to_civi_data() {
    $nav_data                              = $this->get_nav_after_data();
    $this->civi_data_after['Contact']      = array(
      $this->navision_custom_field        => $this->get_nav_value_if_exist($nav_data, 'Contact_No'),
    );
    $relationship_type_id                  = $this->get_relationship_type_id($this->get_nav_value_if_exist($nav_data, 'Status'));
    $this->civi_data_after['Relationship'] = array (
      'relationship_type_id'    => $relationship_type_id,
      'start_date'              => $this->get_nav_value_if_exist($nav_data, 'Valid_from'),
      'end_date'                => $this->get_nav_value_if_exist($nav_data, 'Valid_to'),
      'contact_id_b'            => $this->hbs_contact_id,
    );
  }

  public function get_relationship_data() {
    return $this->civi_data_after['Relationship'];
  }

  public function get_Status_start_date() {
    return $this->civi_data_after['Relationship']['start_date'];
  }

  /**
   * @param $relationship_type
   *
   * @return mixed
   * @throws \Exception
   */
  private function get_relationship_type_id ($relationship_type) {
    if (!isset($this->relationship_type_mapping[$relationship_type])) {
      $this->log("Cannot get the relationship_type_id from '{$relationship_type}'. Aborting");
      throw new Exception("Cannot get the relationship_type_id from '{$relationship_type}'. Aborting");
    }
    return $this->relationship_type_mapping[$relationship_type];
  }
}