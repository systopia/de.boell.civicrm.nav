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


class CRM_Nav_Data_NavContactRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type                       = "civiContact";

  private   $location_type_private      = "6";
  private   $location_type_organisation = "8";

  // TODO: create Config file for custom field mapping
  private $org_name_1 = 'custom_45';
  private $org_name_2 = 'custom_46';

  private   $matcher;

  public function __construct($nav_data_after, $nav_data_before = NULL) {
    parent::__construct($nav_data_after, $nav_data_before);
    $this->matcher = new CRM_Nav_Data_NavContactMatcherCivi($this->navision_custom_field, $this->org_name_1, $this->org_name_2);
  }

  protected function convert_to_civi_data() {
    // set location data
    $this->set_location_type_ids();
    // contact_data
    $this->convert_civi_person_data();
    // convert addresses
    $this->convert_civi_addresses();
    // convert company extra info (Nav Id, Name)
    $this->convert_civi_organisation_data();
    // Phone/Fax/Websites
    $this->convert_civi_communication_data();

    $this->clean_civi_data('before');
    $this->clean_civi_data('after');
  }

  public function get_contact_details($contact_type = 'Individual') {
    foreach ($this->civi_data_after['Contact'] as $contact) {
      if ($contact['contact_type'] == $contact_type) {
        return $contact;
      }
    }
  }

  public function get_civi_individual_address() {
    return $this->civi_data_after['Address']['individual'];
  }

  public function get_civi_phones() {
    return $this->civi_data_after['Phone'];
  }

  public function get_civi_emails() {
    return $this->civi_data_after['Email'];
  }

  public function get_civi_website() {
    return $this->civi_data_after['Website'];
  }

  private function set_location_type_ids() {
    $nav_data = $this->get_nav_after_data();
    if ($nav_data['Type'] == 'Company') {
      // overwrite private location type id, this is 'geschaeftlich' now
      $this->location_type_private = $this->location_type_organisation;
    }
  }

  public function get_company_data() {
    $nav_data = $this->get_nav_after_data();
    if ($nav_data['Type'] == 'Company') {
      // nothing to do here, as a company we don;t have shared addresses
      return array();
    }
    foreach ($this->civi_data_before['Contact'] as $contact) {
      if ($contact['contact_type'] == "Organization") {
        $result['Contact'] = $this->civi_data_after['Contact'] = $contact;
      }
    }
//    $result['Contact'] = $this->civi_data_after['Contact']
    $result['Address'] = $this->civi_data_after['Address']['organisation'];
    $result['Org_nav_id'] = $nav_data['Company_No'];

    return $result;
  }

  /*
   * check if organisation data is set
   * if so - add 'organisation_address' to $civi_extra_data
   * and fill in data from compare
   */
  private function convert_civi_addresses() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Private Address
    $this->civi_data_after['Address']['individual']  = $this->create_civi_address_values_private($nav_data_after);
    $this->civi_data_before['Address']['individual'] = $this->create_civi_address_values_private($nav_data_before);
    // organisationAddress
    $this->civi_data_after['Address']['organisation']  = $this->create_civi_address_values_organisation($nav_data_after);
    $this->civi_data_before['Address']['organisation'] = $this->create_civi_address_values_organisation($nav_data_before);
  }

  private function create_civi_address_values_private($nav_data) {
    return [
      'street_address'         => $this->get_nav_value_if_exist($nav_data, 'Address'),
      'supplemental_address_1' => $this->get_nav_value_if_exist($nav_data, 'Address_2'),
      'postal_code'            => $this->get_nav_value_if_exist($nav_data, 'Post_Code'),
      'city'                   => $this->get_nav_value_if_exist($nav_data, 'City'),
      'country_id'             => $this->get_nav_value_if_exist($nav_data, 'Country_Region_Code'),
      'location_type_id'       => $this->location_type_private,
    ];
  }

  private function create_civi_address_values_organisation($nav_data) {
    return [
      'street_address'         => $this->get_nav_value_if_exist($nav_data, 'Company_Adress'),
      'supplemental_address_1' => $this->get_nav_value_if_exist($nav_data, 'Company_Adress_2'),
      'postal_code'            => $this->get_nav_value_if_exist($nav_data, 'Company_Post_Code'),
      'city'                   => $this->get_nav_value_if_exist($nav_data, 'Company_City'),
      'country_id'             => $this->get_nav_value_if_exist($nav_data, 'Company_Country_Region_Code'),
      'location_type_id'       => $this->location_type_organisation,
    ];
  }

  private function create_civi_phone_values($location_type, $phone_type, $nav_index, $nav_data) {
    return [
      'phone'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
      'phone_type_id'    => $phone_type,
    ];
  }

  private function create_civi_mail_values($location_type, $nav_index, $nav_data) {
    return [
      'email'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
    ];
  }

  private function convert_civi_communication_data() {
    $nav_data_after                  = $this->get_nav_after_data();
    $nav_data_before                 = $this->get_nav_before_data();
    $this->civi_data_after['Phone']  = [];
    $this->civi_data_before['Phone'] = [];


    if (isset($nav_data_after['Phone_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Phone", 'Phone_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Phone", 'Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Mobile_Phone_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Mobile", 'Mobile_Phone_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Mobile", 'Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Fax_No'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_organisation, "Fax", 'Fax_No', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_organisation, "Fax", 'Fax_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Telefonnr'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Faxr'])) {
      $this->civi_data_after['Phone'][]  = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_after);
      $this->civi_data_before['Phone'][] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_before);
    }
    // Homepage
    if (isset($nav_data_after['Home_Page'])) {
      $this->civi_data_after['Website'][] = [
        'url'             => $this->get_nav_value_if_exist($nav_data_after, 'Home_Page'),
        'website_type_id' => $this->location_type_private,
      ];
      $this->civi_data_before['Website'][] = [
        'url'             => $this->get_nav_value_if_exist($nav_data_before, 'Home_Page'),
        'website_type_id' => $this->location_type_private,
      ];
    }
    //Email
    // FixMe: Primary email is in Civi_person_data()
    if (isset($nav_data_after['Email'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_organisation, 'Email', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_organisation, 'Email', $nav_data_before);
    }
    if (isset($nav_data_after['E_Mail_2'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_before);
    }
    if (isset($nav_data_after['Private_E_Mail'])) {
      $this->civi_data_after['Email'][]  = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_after);
      $this->civi_data_before['Email'][] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_before);
    }
  }

  /**
   * @param $type
   *
   * @throws \Exception
   */
  private function clean_civi_data($type) {
    switch ($type) {
      case 'before':
        if (empty($this->civi_data_before)) {
          return;
        }
        $civi_data = &$this->civi_data_before;
        break;
      case 'after':
        $civi_data = &$this->civi_data_after;
        break;
      default:
        throw new Exception('Invalid Type {$type} in clean_civi_data');
    }
    foreach ($civi_data as $entity_name => &$civi_entity) {
      foreach ($civi_entity as $key => &$values) {
        foreach ($values as $name => &$val) {
          if ($val == "") {
            if ($entity_name == 'Phone' || $entity_name == 'Email') {
              // unset parent
              unset($civi_entity[$key]);
              break;
            }
            else {
              // unset child
              unset($values[$name]);
            }
          }
        }
      }
    }
  }

  private function create_civi_contact_values($nav_data) {
    return [
      'contact_type' => "Individual",
      'first_name'   => $this->get_nav_value_if_exist($nav_data, 'First_Name'),
      'middle_name'  => $this->get_nav_value_if_exist($nav_data, 'Middle_Name'),
      'last_name'    => $this->get_nav_value_if_exist($nav_data, 'Surname'),
      'birth_date'   => $this->get_nav_value_if_exist($nav_data, 'Geburtsdatum'),
      // NavisionID
      $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'No'),
      'email'        => $this->get_nav_value_if_exist($nav_data, 'E_mail'),
      'job_title'    => $this->get_nav_value_if_exist($nav_data, 'Job_Title'),
      'contact_type' => $this->get_contact_type($this->get_nav_value_if_exist($nav_data, 'Type')),
      'prefix_id'    => $this->get_nav_value_if_exist($nav_data, 'Salutation_Code'),
    ];
  }

  private function convert_civi_person_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    $this->civi_data_after['Contact'][]  = $this->create_civi_contact_values($nav_data_after);
    $this->civi_data_before['Contact'][] = $this->create_civi_contact_values($nav_data_before);
  }

  private function get_contact_type($type) {
    switch ($type) {
      case 'Person':
        return "Individual";
      case 'Company':
        return "Organisation";
      case "":
        return "";
      default:
        throw new Exception("Invalid Field for Contact_type. Must be either Person or Company");
    }
  }

  private function value_changed($fields) {
    foreach ($fields as $f) {
      if (array_key_exists($f, $this->changed_data) && $this->changed_data[$f] != "") {
        return TRUE;
      }
    }
    return FALSE;
  }

  private function create_civi_contact_data_organisation($nav_data) {
    return [
      // NavisionID
      'contact_type' => "Organization",
      $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'Company_No'),
      $this->org_name_1   => $this->get_nav_value_if_exist($nav_data, 'Company_Name'),
      $this->org_name_2   => $this->get_nav_value_if_exist($nav_data, 'Company_Name_2'),
      'organization_name' => ($this->get_nav_value_if_exist($nav_data, 'Company_Name') ." " . $this->get_nav_value_if_exist($nav_data, 'Company_Name_2')),
    ];
  }

  private function convert_civi_organisation_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    $this->civi_data_after['Contact'][]  = $this->create_civi_contact_data_organisation($nav_data_after);
    $this->civi_data_before['Contact'][] = $this->create_civi_contact_data_organisation($nav_data_before);
  }

  public function get_contact_lookup_details() {
    $result['Emails'] = $this->get_contact_email();
    foreach ($this->civi_data_before['Contact'] as $contact) {
      if ($contact['contact_type'] == "Individual") {
        $result['Contact'] = array (
          'first_name' => $contact['first_name'],
          'last_name'  => $contact['last_name'],
        );
      }
    }
    return $result;
  }

  private function get_contact_email() {
    $nav_data = $this->get_nav_before_data();
    $result= [];
    if (isset($nav_data['E_mail'])) {
      $result[] = $nav_data['E_mail'];
    }
    if (isset($nav_data['E_mail_2'])) {
      $result[] = $nav_data['E_mail_2'];
    }
    if (isset($nav_data['Private_E_Mail'])) {
      $result[] = $nav_data['Private_E_Mail'];
    }
    return $result;
  }

  public function get_i3val_values($entity) {
    return $this->civi_data_after[$entity];
  }

  public function get_changed_contact_values($type) {
    $result         = [];
    $contact_fields = $this->matcher->get_contact_fields('Individual');
    foreach ($contact_fields as $field) {
      if (array_key_exists($field, $this->changed_data) && $this->changed_data[$field] != "") {
        switch ($type) {
          case 'after':
            $result['Contact'][$this->matcher->get_civi_values($field)] = $this->changed_data[$field];
            break;
          case 'before':
            $nav_data_before                                            = $this->get_nav_before_data();
            $result['Contact'][$this->matcher->get_civi_values($field)] = $nav_data_before[$field];
            break;
          default:
            throw new Exception("Invalid type '{$type}' in get_contact_values");
        }
      }
    }
    return $result;
  }

  public function get_changed_Address_values($type) {
    $result                 = [];
    $private_address_fields = $this->matcher->get_address_fields('private');
    if ($this->value_changed($private_address_fields)) {
      switch ($type) {
        case 'after':
          $result['Address'][] = $this->civi_data_after['Address']['individual'];
          break;
        case 'before':
          $result['Address'][] = $this->civi_data_before['Address']['individual'];
          break;
        default:
          throw new Exception("Invalid type '{$type}' in get_changed_Address_values");
      }
    }
    return $result;
  }

  public function get_changed_Phone_values($type) {
    $result = [];
    // Private Phone
    $private_phone_fields = $this->matcher->get_phone_fields('private');
    if ($this->value_changed($private_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_private, "Phone", $type);
    }
    // private Fax
    $private_fax_fields = $this->matcher->get_fax_fields('private');
    if ($this->value_changed($private_fax_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_private, "Fax", $type);
    }
    // organisation Phone
    $org_phone_fields = $this->matcher->get_phone_fields('organisation');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Phone", $type);
    }
    // organisation Mobile
    $org_phone_fields = $this->matcher->get_phone_fields('mobile');
    if ($this->value_changed($org_phone_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Mobile", $type);
    }
    // organisation Phone
    $org_fax_fields = $this->matcher->get_fax_fields('organisation');
    if ($this->value_changed($org_fax_fields)) {
      $result['Phone'][] = $this->get_phone_values($this->location_type_organisation, "Fax", $type);
    }
    return $result;
  }

  public function get_changed_Email_values($type) {
    $result       = [];
    $email_fields = $this->matcher->get_email_fields('organisation');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $result['Email'][] = $this->get_email_value($this->location_type_organisation, $email, $type);
      }
    }
    $email_fields = $this->matcher->get_email_fields('private');
    foreach ($email_fields as $email) {
      if (array_key_exists($email, $this->changed_data)) {
        $result['Email'][] = $this->get_email_value($this->location_type_private, $email, $type);
      }
    }
    return $result;
  }

  public function get_changed_Website_values($type) {
    $result         = [];
    $website_fields = $this->matcher->get_website_fields();

    // TODO: finish this
    foreach ($website_fields as $website) {
      if ($this->value_changed($website)) {
        switch ($type) {
          case 'after':
            $result['Website'] = $this->civi_data_after['Website'];
            break;
          case 'before':
            $result['Website'] = $this->civi_data_before['Website'];
          default:
            throw new Exception("Invalid Type '{$type}' in get_changed_Website_values");
        }
      }
    }
  }

  public function get_new_values() {

  }

  private function get_email_value($location_type, $email_key, $type) {
    switch ($type) {
      case 'after':
        $nav_data_after = $this->get_nav_after_data();
        foreach ($this->civi_data_after['Email'] as $email_data) {
          if ($email_data['location_type_id'] == $location_type && $email_data['email'] == $nav_data_after[$email_key]) {
            return $email_data;
          }
        }
        return "";
      case 'before':
        $nav_data_before = $this->get_nav_before_data();
        foreach ($this->civi_data_before['Email'] as $email_data) {
          if ($email_data['location_type_id'] == $location_type && $email_data['email'] == $nav_data_before[$email_key]) {
            return $email_data;
          }
        }
        return "";
      default:
        throw new Exception("Invalid Type '{$type}' in get_email_value");
    }
  }

  private function get_phone_values($location_type, $phone_type, $type) {
    switch ($type) {
      case 'after':
        foreach ($this->civi_data_after['Phone'] as $phone_data) {
          if ($phone_data['location_type_id'] == $location_type && $phone_data['phone_type_id'] == $phone_type) {
            return $phone_data;
          }
        }
        return "";
      case 'before':
        foreach ($this->civi_data_before['Phone'] as $phone_data) {
          if ($phone_data['location_type_id'] == $location_type && $phone_data['phone_type_id'] == $phone_type) {
            return $phone_data;
          }
        }
        return "";
      default:
        throw new Exception("Invalid Type '{$type}' in get_phone_values");
    }
  }

}