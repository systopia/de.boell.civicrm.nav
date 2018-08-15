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


class CRM_Nav_Data_NavContact extends CRM_Nav_Data_NavDataRecordBase {

  protected $type = "civiContact";

  private $location_type_private        = "6";
  private $location_type_organisation   = "8"; // maybe need geschaeftlich?

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
  }

  protected function convert_to_civi_data() {
    // contact_data
    $this->convert_civi_person_data();
    // convert addresses
    $this->convert_civi_addresses();
    // convert company extra info (Nav Id, Name)
    $this->convert_civi_organisation_data();
    // Phone/Fax/Websites
    $this->convert_civi_communication_data();
  }

  /*
   * check if organisation data is set
   * if so - add 'organisation_address' to $civi_extra_data
   * and fill in data from compare
   */
  private function convert_civi_addresses() {
    $nav_data = $this->get_nav_after_data();
    // organisationAddress
    $this->civi_extra_data['Address']['organisation'] = array(
      'street_address'          => $nav_data['Company_Adress'],
      'supplemental_address_1'  => $nav_data['Adress_2'],
      'postal_code'             => $nav_data['Company_Post_Code'],
      'city'                    => $nav_data['Company_City'],
      'country_id'              => $nav_data['Company_Country_Region_Code'],
      'location_type_id'        => $this->location_type_organisation,
    );
    // Private Address (TODO)
    $this->civi_extra_data['Address']['individual'] = array(
      'street_address'          => $nav_data['Adress'],
      'supplemental_address_1'  => $nav_data['Adress_2'],
      'postal_code'             => $nav_data['Post_Code'],
      'city'                    => $nav_data['City'],
      'country_id'              => $nav_data['Country_Region_Code'],
      'location_type_id'        => $this->location_type_private,
    );
  }

  private function convert_civi_communication_data() {
    $nav_data = $this->get_nav_after_data();

    $this->civi_extra_data['Phone'] = array();
    if (isset($nav_data['Phone_No'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $nav_data['Phone_No'],
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Phone",
      );
    }
    if (isset($nav_data['Mobile_Phone_No'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $nav_data['Mobile_Phone_No'],
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Mobile",
      );
    }
    if (isset($nav_data['Fax_No'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $nav_data['Fax_No'],
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Fax",
      );
    }
    if (isset($nav_data['Private_Telefonnr'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $nav_data['Private_Telefonnr'],
        'location_type_id'  => $this->location_type_private,
        'phone_type_id'     => "Phone",
      );
    }
    if (isset($nav_data['Private_Faxnr'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $nav_data['Private_Faxnr'],
        'location_type_id'  => $this->location_type_private,
        'phone_type_id'     => "Fax",
      );
    }
    // Homepage
    if (isset($nav_data['Home_Page'])) {
      $this->civi_extra_data['Website'][] = array(
        'url'             => $nav_data['Home_Page'],
        'website_type_id' => $this->location_type_private,
      );
    }
    //Email
    // FixMe: Primary email is in Civi_person_data()
//    if (isset($nav_data['Email'])) {
//      $this->civi_extra_data['Email'][] = array(
//        'email'             => $nav_data['Email'],
//        'location_type_id' => $this->location_type_organisation,
//      );
//    }
    if (isset($nav_data['Email_2'])) {
      $this->civi_extra_data['Email'][] = array(
        'email'             => $nav_data['Email_2'],
        'location_type_id' => $this->location_type_private,
      );
    }
  }

  private function convert_civi_person_data() {
    $nav_data = $this->get_nav_after_data();
    $this->civi_extra_data['Contact'][] = array(
      'first_name'              => $nav_data['First_Name'],
      'middle_name'             => $nav_data['Middle_Name'],
      'last_name'               => $nav_data['Surname'],
      // NavisionID
      'custom_147'              => $nav_data['No'],
      'email'                   => $nav_data['Email'],
      'contact_type'            => "Individual",
    );
  }

  /**
   * add organisation name and data
   */
  private function convert_civi_organisation_data() {
    $nav_data = $this->get_nav_after_data();
    $this->civi_extra_data['Contact'][] = array(
      // NavisionID
      'contact_type'            => "Organization",
      'custom_147'              => $nav_data['Company_No'],
      'display_name'            => $nav_data['Company_Name'],
    );
  }

}