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
 * Class CRM_Nav_Data_NavContactRecord
 */
class CRM_Nav_Data_NavContactRecord extends CRM_Nav_Data_NavDataRecordBase {

  protected $type                       = "civiContact";

// Data Classes for Civi Entities
  private $Contact;
  private $Email;
  private $Address;
  private $Phone;
  private $Website;


  private   $location_type_private;
  private   $location_type_organization;
  private   $website_type_id;
  private $org_name_1;
  private $org_name_2;

  private $is_organization;

  private   $matcher;

  private $contactType;

  /**
   * CRM_Nav_Data_NavContactRecord constructor.
   *
   * @param      $nav_data_after
   * @param null $nav_data_before
   *
   * @throws \Exception
   */
  public function __construct($nav_data_after, $nav_data_before = NULL) {
    $this->org_name_1 = CRM_Nav_Config::get('org_name_1');
    $this->org_name_2 = CRM_Nav_Config::get('org_name_1');
    $this->location_type_private = CRM_Nav_Config::get('location_type_private');
    $this->location_type_organization = CRM_Nav_Config::get('location_type_organization');
    $this->website_type_id = CRM_Nav_Config::get('website_type_id');

    parent::__construct($nav_data_after, $nav_data_before);
    $this->matcher = new CRM_Nav_Data_NavContactMatcherCivi($this->navision_custom_field, $this->org_name_1, $this->org_name_2);
  }

  /**
   * @throws \Exception
   */
  protected function convert_to_civi_data() {
    // set location data
    $this->set_location_type_ids();
    // contact_data
    $this->convert_civi_contact_data();
    // convert Entities
    $this->convert_civi_addresses();
    $this->convert_civi_email_data();
    $this->convert_civi_website_data();
    $this->convert_civi_phone_data();
  }

  /**
   * Creates full contact with all available data
   * Should only be called if Contact wasn't found by NavId, ID-Tracker and Data
   */
  public function create_full_contact() {
    $this->Contact->create_full();
    $contact_id = $this->Contact->get_contact_id();
    $org_id = $this->Contact->get_org_id();
    $this->Address->create_full($contact_id, $org_id);
    $this->Phone->create_full($contact_id);
    $this->Email->create_full($contact_id);
    $this->Website->create_full($contact_id);
  }

  /**
   * @return mixed
   */
  public function get_or_create_contact() {
    $contact_id = $this->Contact->get_contact_id();
    if (!empty($contact_id)) {
      // set contact_id to other objects as well and trigger civi-entity lookups
      $this->Address->set_contact_id($contact_id);
      $this->Address->set_organization_id($this->Contact->get_org_id());
      $this->Phone->set_contact_id($contact_id);
      $this->Email->set_contact_id($contact_id);
      $this->Website->set_contact_id($contact_id);
      return $contact_id;
    }
    // nothing found, we create Contact now
    $this->create_full_contact();
    return '-1';
  }

  /**
   * Trigger calculation for update/changes/i3val for all Entities
   */
  public function calc_differences() {
    $this->Contact->calc_differences();
    $this->Address->calc_differences();
    $this->Phone->calc_differences();
    $this->Email->calc_differences();
    $this->Website->calc_differences();
  }

  /**
   * Trigger update for all Entities
   */
  public function update() {
    $this->Contact->update();
    $this->Address->update();
    $this->Phone->update();
    $this->Email->update();
    $this->Website->update();
  }

  /**
   * Trigger apply changes for all Entities
   */
  public function apply_changes() {
    $this->Contact->apply_changes();
    $this->Address->apply_changes();
    $this->Phone->apply_changes();
    $this->Email->apply_changes();
    $this->Website->apply_changes();
  }

  /**
   * Trigger delete Values for all Entities
   */
  public function delete() {
    $this->Contact->delete();
    $this->Address->delete();
    $this->Phone->delete();
    $this->Email->delete();
    $this->Website->delete();
  }

  /**
   * Trigger i3Val request_update if needed for all Entities
   */
  public function i3val() {
    $this->Contact->i3val();
    $this->Address->i3val();
    $this->Phone->i3val();
    $this->Email->i3val();
    $this->Website->i3val();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function convert_civi_contact_data() {

    $nav_data_after              = $this->get_nav_after_data();
    $nav_data_before             = $this->get_nav_before_data();
    // individual
    $civi_data_before_individual = $this->create_civi_contact_values($nav_data_before);
    $civi_data_after_individual  = $this->create_civi_contact_values($nav_data_after);

    // company
    if ($this->contactType == 'Individual') {
      $is_organization = FALSE;
    } else {
      $is_organization = TRUE;
    }
    if (!$is_organization) {
      // only parse Company Data if it is a company
      $civi_data_before_individual_company = $this->create_civi_contact_data_organization($nav_data_before);
      $civi_data_after_individual_company  = $this->create_civi_contact_data_organization($nav_data_after);
    } else {
      $civi_data_before_individual_company = [];
      $civi_data_after_individual_company  = [];
    }


    $lookup_data = $this->get_contact_lookup_details();
    $this->Contact = new CRM_Nav_Data_EntityData_Contact(
      $civi_data_before_individual,
      $civi_data_after_individual,
      $civi_data_before_individual_company,
      $civi_data_after_individual_company,
      $this->get_individual_navision_id(),
      $lookup_data,
      $is_organization
    );

  }

  /*
 * check if organization data is set
 * if so - add 'organization_address' to $civi_extra_data
 * and fill in data from compare
 */
  private function convert_civi_addresses() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Private Address
    $private_before = $this->create_civi_address_values_private($nav_data_before);
    $private_after  = $this->create_civi_address_values_private($nav_data_after);
    // organizationAddress
//    $organization_before  = $this->create_civi_address_values_organization($nav_data_before);
//    $organization_after  = $this->create_civi_address_values_organization($nav_data_after);

    $contact_id = $this->Contact->get_contact_id();
    $organization_id = $this->Contact->get_org_id();

    $this->Address = new CRM_Nav_Data_EntityData_Address(
      $private_before,
      $private_after,
//      $organization_before,
//      $organization_after,
      $contact_id,
      $organization_id,
      $this->location_type_private,
      $this->location_type_organization,
      $this->Contact->is_organization()
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_phone_data() {
    $nav_data_after                  = $this->get_nav_after_data();
    $nav_data_before                 = $this->get_nav_before_data();

    $phone_org   = [];
    $mobile_org  = [];
    $fax_org     = [];
    $fax_priv    = [];
    $mobile_priv = [];
    $phone_priv  = [];
    if (isset($nav_data_after['Phone_No'])) {
      $phone_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Phone", 'Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Phone_No'])) {
      $phone_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Phone", 'Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Mobile_Phone_No'])) {
      $mobile_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Mobile", 'Mobile_Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Mobile_Phone_No'])) {
      $mobile_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Mobile", 'Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Fax_No'])) {
      $fax_org['after'] = $this->create_civi_phone_values($this->location_type_organization, "Fax", 'Fax_No', $nav_data_after);
    }
    if (isset($nav_data_before['Fax_No'])) {
      $fax_org['before'] = $this->create_civi_phone_values($this->location_type_organization, "Fax", 'Fax_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Faxnr'])) {
      $fax_priv['after'] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_after);
    }
    if (isset($nav_data_before['Private_Faxnr'])) {
      $fax_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Fax", 'Private_Faxnr', $nav_data_before);
    }
    if (isset($nav_data_after['Privat_Mobile_Phone_No'])) {
      $mobile_priv['after'] = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_after);
    }
    if (isset($nav_data_before['Privat_Mobile_Phone_No'])) {
      $mobile_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Mobile", 'Privat_Mobile_Phone_No', $nav_data_before);
    }
    if (isset($nav_data_after['Private_Telefonnr'])) {
      $phone_priv['after']  = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_after);
    }
    if (isset($nav_data_before['Private_Telefonnr'])) {
      $phone_priv['before'] = $this->create_civi_phone_values($this->location_type_private, "Phone", 'Private_Telefonnr', $nav_data_before);
    }
    $this->Phone = new CRM_Nav_Data_EntityData_Phone(
      $phone_org,
      $mobile_org,
      $fax_org,
      $phone_priv,
      $mobile_priv,
      $fax_priv,
      $this->location_type_private,
      $this->location_type_organization,
      $this->Contact->get_contact_id()
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_website_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();

    // Homepage
    $website_before = [];
    $website_after = [];
    if (isset($nav_data_after['Home_Page'])) {
      $website_after = [
        'url'             => $this->get_nav_value_if_exist($nav_data_after, 'Home_Page'),
        'website_type_id' => $this->website_type_id,
      ];
    }
    if (isset($nav_data_before['Home_Page'])) {
      $website_before = [
        'url'             => $this->get_nav_value_if_exist($nav_data_before, 'Home_Page'),
        'website_type_id' => $this->website_type_id,
      ];
    }

    $this->Website = new CRM_Nav_Data_EntityData_Website(
      $website_before,
      $website_after,
      $this->Contact->get_contact_id()
    );
  }

  /**
   * convert_civi_phone_data
   */
  private function convert_civi_email_data() {
    $nav_data_after  = $this->get_nav_after_data();
    $nav_data_before = $this->get_nav_before_data();
    //Email
    // FixMe: Primary email is in Civi_person_data()
    $email_org_1 = [];
    $email_priv = [];
    $email_priv_2 = [];
    if (isset($nav_data_after['E_Mail'])) {
      $email_org_1['after']  = $this->create_civi_mail_values($this->location_type_organization, 'E_Mail', $nav_data_after);
    }
    if (isset($nav_data_before['E_Mail'])) {
      $email_org_1['before'] = $this->create_civi_mail_values($this->location_type_organization, 'E_Mail', $nav_data_before);
    }
    if (isset($nav_data_after['E_Mail_2'])) {
      $email_priv_2['after'] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_after);
    }
    if (isset($nav_data_before['E_Mail_2'])) {
      $email_priv_2['before'] = $this->create_civi_mail_values($this->location_type_private, 'E_Mail_2', $nav_data_before);
    }
    if (isset($nav_data_after['Private_E_Mail'])) {
      $email_priv['after'] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_after);
    }
    if (isset($nav_data_before['Private_E_Mail'])) {
      $email_priv['before'] = $this->create_civi_mail_values($this->location_type_private, 'Private_E_Mail', $nav_data_before);
    }
    $this->Email = new CRM_Nav_Data_EntityData_Email(
      $email_org_1,
      $email_priv,
      $email_priv_2,
      $this->location_type_private,
      $this->location_type_organization,
      $this->Contact->get_contact_id()
    );
  }

  /**
   * overwrite possible locationtype IDs in case of Type = Company
   */
  private function set_location_type_ids() {
    $nav_data = $this->get_nav_after_data();
    if ($nav_data['Type'] == 'Company') {
      // overwrite private location type id, this is 'geschaeftlich' now
      $this->location_type_private = $this->location_type_organization;
    }
  }

   /**
   * @param $nav_data
   *
   * @return array
   */
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

  /**
   * @param $location_type
   * @param $phone_type
   * @param $nav_index
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_phone_values($location_type, $phone_type, $nav_index, $nav_data) {
    return [
      'phone'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
      'phone_type_id'    => $phone_type,
    ];
  }

  /**
   * @param $location_type
   * @param $nav_index
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_mail_values($location_type, $nav_index, $nav_data) {
    return [
      'email'            => $this->get_nav_value_if_exist($nav_data, $nav_index),
      'location_type_id' => $location_type,
    ];
  }



  /**
   * @param $type
   *
   * @return string
   * @throws \Exception
   */
  private function get_contact_type($type) {
    switch ($type) {
      case 'Person':
        return "Individual";
      case 'Company':
        return "Organization";
      case "":
        return "";
      default:
        throw new Exception("Invalid Field for Contact_type. Must be either Person or Company");
    }
  }

  /**
   * @param $nav_data
   *
   * @return array
   * @throws \Exception
   */
  private function create_civi_contact_values($nav_data) {
    $contact_type = $this->get_nav_value_if_exist($nav_data, 'Type');
//    if (empty($this->contactType)) {}
    $this->contactType = $this->get_contact_type($contact_type);
    if (empty($contact_type)) {
      return [];
    }
    switch ($contact_type) {
      case 'Company':
        // nothing to do here. No Person data needs to be created
        $org_name1 = $this->get_nav_value_if_exist($nav_data, 'Company_Name');
        $org_name2 = $this->get_nav_value_if_exist($nav_data, 'Company_Name_2');
        return [
          'contact_type' => "Organization",
          $this->org_name_1   => $org_name1,
          $this->org_name_2   => $org_name2,
          'organization_name' => $org_name1 ." " . $org_name2,
          $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'No'),        // NavisionID
        ];
      case 'Person':
      return [
        // TODO: Iterate over fields from NavContactMatcher and make this more generic!
        'contact_type' => "Individual",
        'first_name'   => $this->get_nav_value_if_exist($nav_data, 'First_Name'),
        'middle_name'  => $this->get_nav_value_if_exist($nav_data, 'Middle_Name'),
        'last_name'    => $this->get_nav_value_if_exist($nav_data, 'Surname'),
        'birth_date'   => $this->get_nav_value_if_exist($nav_data, 'Geburtsdatum'),
        $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'No'),        // NavisionID
        'formal_title' => $this->get_nav_value_if_exist($nav_data, 'Job_Title'),
        'job_title'    => $this->get_nav_value_if_exist($nav_data, 'Funktion'),
        'prefix_id'    => $this->get_nav_value_if_exist($nav_data, 'Salutation_Code'),
      ];
      default:
        throw new Exception("Invalid Contact Type {$contact_type}. Couldn't convert Navision Data to CiviCRM data.");
    }

  }

  /**
   * @param $nav_data
   *
   * @return array
   */
  private function create_civi_contact_data_organization($nav_data) {
    return [
      // NavisionID
      'contact_type' => "Organization",
      $this->navision_custom_field   => $this->get_nav_value_if_exist($nav_data, 'Company_No'),
      $this->org_name_1   => $this->get_nav_value_if_exist($nav_data, 'Company_Name'),
      $this->org_name_2   => $this->get_nav_value_if_exist($nav_data, 'Company_Name_2'),
      'organization_name' => ($this->get_nav_value_if_exist($nav_data, 'Company_Name') ." " . $this->get_nav_value_if_exist($nav_data, 'Company_Name_2')),
    ];
  }


  /**
   * @return mixed
   */
  private function get_contact_lookup_details() {
    $result['Emails'] = $this->get_contact_email();

    $nav_data_before = $this->get_nav_before_data();
    $nav_data_after = $this->get_nav_before_data();
    // before
    $first_name = $this->get_nav_value_if_exist($nav_data_before, 'First_Name');
    if (isset($first_name)) {
      $result['Contact']['before']['first_name'] = $first_name;
    }
    $last_name = $this->get_nav_value_if_exist($nav_data_before, 'Surname');
    if (isset($last_name)) {
      $result['Contact']['before']['last_name'] = $last_name;
    }
    // after
    $first_name = $this->get_nav_value_if_exist($nav_data_after, 'First_Name');
    if (isset($first_name)) {
      $result['Contact']['after']['first_name'] = $first_name;
    }
    $last_name = $this->get_nav_value_if_exist($nav_data_after, 'Surname');
    if (isset($last_name)) {
      $result['Contact']['after']['last_name'] = $last_name;
    }
    return $result;
  }

  /**
   * @return array
   */
  private function get_contact_email() {
    $nav_data_before = $this->get_nav_before_data();
    $nav_data_after = $this->get_nav_after_data();
    $result= [];
    // before values
    if (isset($nav_data_before['E_Mail'])) {
      $result['before'][] = $nav_data_before['E_Mail'];
    }
    if (isset($nav_data_before['E_Mail_2'])) {
      $result['before'][] = $nav_data_before['E_Mail_2'];
    }
    if (isset($nav_data_before['Private_E_Mail'])) {
      $result['before'][] = $nav_data_before['Private_E_Mail'];
    }
    // after values
    if (isset($nav_data_after['E_Mail'])) {
      $result['after'][] = $nav_data_after['E_Mail'];
    }
    if (isset($nav_data_after['E_Mail_2'])) {
      $result['after'][] = $nav_data_after['E_Mail_2'];
    }
    if (isset($nav_data_after['Private_E_Mail'])) {
      $result['after'][] = $nav_data_after['Private_E_Mail'];
    }
    return $result;
  }
}
