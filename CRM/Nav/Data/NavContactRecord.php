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

  private $matcher;

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
    $this->matcher  = new CRM_Nav_Data_NavContactMatcherCivi();
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
      'street_address'          => $this->get_nav_value_if_exist($nav_data, 'Company_Adress'),
      'supplemental_address_1'  => $this->get_nav_value_if_exist($nav_data, 'Company_Adress_2'),
      'postal_code'             => $this->get_nav_value_if_exist($nav_data, 'Company_Post_Code'),
      'city'                    => $this->get_nav_value_if_exist($nav_data, 'Company_City'),
      'country_id'              => $this->get_nav_value_if_exist($nav_data, 'Company_Country_Region_Code'),
      'location_type_id'        => $this->location_type_organisation,
    );
    // Private Address (TODO)
    $this->civi_extra_data['Address']['individual'] = array(
      'street_address'          => $this->get_nav_value_if_exist($nav_data, 'Adress'),
      'supplemental_address_1'  => $this->get_nav_value_if_exist($nav_data, 'Adress_2'),
      'postal_code'             => $this->get_nav_value_if_exist($nav_data, 'Post_Code'),
      'city'                    => $this->get_nav_value_if_exist($nav_data, 'City'),
      'country_id'              => $this->get_nav_value_if_exist($nav_data, 'Country_Region_Code'),
      'location_type_id'        => $this->location_type_private,
    );
  }

  private function convert_civi_communication_data() {
    $nav_data = $this->get_nav_after_data();

    $this->civi_extra_data['Phone'] = array();
    if (isset($nav_data['Phone_No'])){
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $this->get_nav_value_if_exist($nav_data, 'Phone_No'),
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Phone",
      );
    }
    if (isset($nav_data['Mobile_Phone_No'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $this->get_nav_value_if_exist($nav_data, 'Mobile_Phone_No'),
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Mobile",
      );
    }
    if (isset($nav_data['Fax_No'])) {
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $this->get_nav_value_if_exist($nav_data, 'Fax_No'),
        'location_type_id'  => $this->location_type_organisation,
        'phone_type_id'     => "Fax",
      );
    }
    if (isset($nav_data['Private_Telfonnr'])){
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $this->get_nav_value_if_exist($nav_data, 'Private_Telefonnr'),
        'location_type_id'  => $this->location_type_private,
        'phone_type_id'     => "Phone",
      );
    }
    if (isset($nav_data['Private_Faxr'])){
      $this->civi_extra_data['Phone'][] = array(
        'phone'             => $this->get_nav_value_if_exist($nav_data, 'Private_Faxnr'),
        'location_type_id'  => $this->location_type_private,
        'phone_type_id'     => "Fax",
      );
    }
    // Homepage
    if (isset($nav_data['Home_Page'])){
      $this->civi_extra_data['Website'] = array(
        'url'             => $this->get_nav_value_if_exist($nav_data, 'Home_Page'),
        'website_type_id' => $this->location_type_private,
      );
    }
    //Email
    // FixMe: Primary email is in Civi_person_data()
    if (isset($nav_data['Email'])) {
        $this->civi_extra_data['Email'][] = array(
          'email'             => $this->get_nav_value_if_exist($nav_data, 'Email'),
          'location_type_id'  => $this->location_type_organisation,
      );
    }
    if (isset($nav_data['E_Mail_2'])){
      $this->civi_extra_data['Email'][] = array(
        'email'            => $this->get_nav_value_if_exist($nav_data, 'E_Mail_2'),
        'location_type_id' => $this->location_type_private,
      );
    }
    if (isset($nav_data['Private_E_Mail'])){
      $this->civi_extra_data['Email'][] = array(
        'email'             => $this->get_nav_value_if_exist($nav_data, 'Private_E_Mail'),
        'location_type_id'  => $this->location_type_private,
      );
    }
  }

  private function convert_civi_person_data() {
    $nav_data = $this->get_nav_after_data();
    $this->civi_extra_data['Contact'][] = array(
      'first_name'              => $this->get_nav_value_if_exist($nav_data, 'First_Name'),
      'middle_name'             => $this->get_nav_value_if_exist($nav_data, 'Middle_Name'),
      'last_name'               => $this->get_nav_value_if_exist($nav_data, 'Surname'),
      'birth_date'               => $this->get_nav_value_if_exist($nav_data, 'Geburtsdatum'),
      // NavisionID
      'custom_147'              => $this->get_nav_value_if_exist($nav_data, 'No'),
      'email'                   => $this->get_nav_value_if_exist($nav_data, 'E_mail'),
      'job_title'               => $this->get_nav_value_if_exist($nav_data, 'Job_Title'),
      'contact_type'            => $this->get_contact_type($this->get_nav_value_if_exist($nav_data, 'Type')),
      'prefix_id'               => $this->get_nav_value_if_exist($nav_data, 'Salutation_Code'),
    );
  }

  private function get_contact_type($type) {
    switch ($type) {
      case 'Person':
        return "Individual";
      case 'Company':
        return "Organisation";
      default:
        throw new Exception("Invalid Field for Contact_type. Must be either Person or Company");
    }
  }

  private function value_changed($fields) {
    foreach ($fields as $f) {
      if (array_key_exists($f, $this->changed_data)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * add organisation name and data
   */
  private function convert_civi_organisation_data() {
    $nav_data = $this->get_nav_after_data();
    $this->civi_extra_data['Contact'][] = array(
      // NavisionID
      'contact_type'            => "Organization",
      'custom_147'              => $this->get_nav_value_if_exist($nav_data, 'Company_No'),
      'custom_106'              => $this->get_nav_value_if_exist($nav_data, 'Company_Name'),
      'custom_107'              => $this->get_nav_value_if_exist($nav_data, 'Company_Name_2'),
      'display_name'            => ($this->get_nav_value_if_exist($nav_data, 'Company_Name') .  $this->get_nav_value_if_exist($nav_data, 'Company_Name_2')),
    );
  }

  public function get_xcm_contact_details() {
    $result = array();
    foreach ($this->civi_extra_data['Contact'] as $contact) {
      if ($contact['contact_type' == "Individual"]) {
        $result = array(
          'first_name' => $contact['first_name'],
          'last_name'  => $contact['last_name'],
          'email'      => $contact['email'],
        );
        return $result;
      }
    }
    return $result;
  }

  public function get_changed_contact_values() {
    $result = array();
    $nav_data_before = $this->get_nav_before_data();
    $contact_fields  = $this->matcher->get_contact_fields();
    foreach ($contact_fields as $field) {
      if (array_key_exists($field, $this->changed_data)) {
        $result['Contact'][$this->matcher->get_civi_values($field)] = $this->changed_data[$field];
        $result['Contact_old'][$field]                              = $nav_data_before[$field];
      }
    }
    return $result;
  }

  public function get_changed_address_values(){
    $result = array();
    $nav_data_before = $this->get_nav_before_data();
    $private_address_fields = $this->matcher->get_address_fields('private');
    if ($this->value_changed($private_address_fields)) {
      $result['Address'][] = $this->civi_extra_data['Address']['individual'];
      $old_vals            = [];
      foreach ($private_address_fields as $address_field) {
        $old_vals[$address_field] = $nav_data_before[$address_field];
      }
      $result['Address_old'][] = $old_vals;
    }
    $org_address_fields = $this->matcher->organisation('organisation');
    if ($this->value_changed($org_address_fields)) {
      $result['Address'][] = $this->civi_extra_data['Address']['organisation'];
      $old_vals            = [];
      foreach ($org_address_fields as $address_field) {
        $old_vals[$address_field] = $nav_data_before[$address_field];
      }
      $result['Address_old'][] = $old_vals;
    }
    return $result;
  }

  public function get_changed_phone_values() {
    $result = array();
    $nav_data_before = $this->get_nav_before_data();
    // Private Phone
    $private_phone_fields = $this->matcher->get_phone_fields('private');
    if ($this->value_changed($private_phone_fields)) {
      $phone_data                      = $this->get_phone_values($this->location_type_private, "Phone");
      $result['Phone'][]               = $phone_data;
      $nav_index                       = $this->get_index($phone_data['phone']);
      $result['Phone_old'][$nav_index] = $nav_data_before[$nav_index];
    }
    // private Fax
    $private_fax_fields = $this->matcher->get_fax_fields('private');
    if ($this->value_changed($private_fax_fields)) {
      $phone_data                      = $this->get_phone_values($this->location_type_private, "Fax");
      $result['Phone'][]               = $phone_data;
      $nav_index                       = $this->get_index($phone_data['phone']);
      $result['Phone_old'][$nav_index] = $nav_data_before[$nav_index];
    }
    // organisation Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organisation');
    if ($this->value_changed($org_phone_fields)) {
      $phone_data                      = $this->get_phone_values($this->location_type_organisation, "Phone");
      $result['Phone'][]               = $phone_data;
      $nav_index                       = $this->get_index($phone_data['phone']);
      $result['Phone_old'][$nav_index] = $nav_data_before[$nav_index];
    }
    // organisation Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if ($this->value_changed($org_phone_fields)) {
      $phone_data                      = $this->get_phone_values($this->location_type_organisation, "Mobile");
      $result['Phone'][]               = $phone_data;
      $nav_index                       = $this->get_index($phone_data['phone']);
      $result['Phone_old'][$nav_index] = $nav_data_before[$nav_index];
    }
    // organisation Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organisation');
    if ($this->value_changed($org_fax_fields)) {
      $phone_data                      = $this->get_phone_values($this->location_type_organisation, "Fax");
      $result['Phone'][]               = $phone_data;
      $nav_index                       = $this->get_index($phone_data['phone']);
      $result['Phone_old'][$nav_index] = $nav_data_before[$nav_index];
    }
    return $result;
  }

  public function get_changed_mail_values(){
    $result = array();
    $nav_data_before = $this->get_nav_before_data();
    $email_fields = $this->matcher->get_email_fields('organisation');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $email_data                      = $this->get_email_value($this->location_type_organisation, $this->changed_data[$email]);
        $result['Email'][]               = $email_data;
        $nav_index                       = $this->get_index($email_data['email']);
        $result['Email_old'][$nav_index] = $nav_data_before[$nav_index];
      }
    }
    $email_fields = $this->matcher->get_email_fields('private');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $email_data                      = $this->get_email_value($this->location_type_private, $this->changed_data[$email]);
        $result['Email'][]               = $email_data;
        $nav_index                       = $this->get_index($email_data['email']);
        $result['Email_old'][$nav_index] = $nav_data_before[$nav_index];
      }
    }
    return $result;
  }

  public function get_changed_website_values() {
    $result = array();
    $nav_data_before = $this->get_nav_before_data();
    $website_fields = $this->matcher->get_website_fields();
    // TODO: finish this
    foreach ($website_fields as $website) {
      if ($this->value_changed($website)) {
        $result['Website'] = $this->civi_extra_data['Website'];
        $nav_data_before = $this->get_index($this->civi_extra_data['Website']['url']);
        $result['Website_old'] = $nav_data_before[$nav_data_before];
      }
    }
  }

  /**
   * Get the index of the provided value from $this->nav_data_after
   *
   * @param $value
   */
  private function get_index($value) {
    $result = array_search($value, $this->get_nav_after_data());
    if (!$result) {
      throw new Exception("Couldn't find index for value {$value} in nav_data_before");
    }
    return $result;
  }

  private function get_email_value($location_type, $email) {
    foreach ($this->civi_extra_data['Email'] as $email_data) {
      if ($email_data['location_type_id'] == $location_type && $email_data['email'] == $email) {
        return $email_data;
      }
    }
    return "";
  }

  private function get_phone_values($location_type, $phone_type) {
    foreach ($this->civi_extra_data['Phone'] as $phone_data) {
      if ($phone_data['location_type_id'] == $location_type && $phone_data['phone_type_id'] == $phone_type) {
        return $phone_data;
      }
      return "";
    }
  }

  /**
   * Returns bi-directional Array with
   *   civi_key     => VALUE
   *   navision_key => civi_key
   *
   * @return array
   * @throws \Exception
   */
  public function get_change_fields_civi() {
    $result = array(
      'reverse_keys' => array(),
    );
    foreach ($this->changed_data as $key => $value) {
      $result[$this->matcher->get_civi_values($key)] = $value;
      $result['reverse_keys'][$key]                  = $this->matcher->get_civi_values($key);
    }
    return $result;
  }

}