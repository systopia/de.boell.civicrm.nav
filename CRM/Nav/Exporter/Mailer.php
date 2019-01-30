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
 * TODO: Create Settings page?
 * Class CRM_Nav_Exporter_Mailer
 */
class CRM_Nav_Exporter_Mailer {

  private $email_from              = 'civi2navision@boell.de';
  private $email_name_from         = 'Civi2Navision';
  private $subject                 = 'Änderungsmitteilung aus CiviCRM';
  private $sender_contact_id       = '2'; // (TODO: create user for this .. ?)

  private $to_name_sw              = 'Studienwerk HBS';
  private $to_name_kred            = 'Navision HBS';

  private $to_email_sw             = 'civicrm-stw@boell.de';
  private $to_email_kred           = 'kreditorenNAV2009@boell.de';

//    private $to_email_sw             = 'batroff@systopia.de';
//    private $to_email_kred           = 'batroff@systopia.de';

    private $custom_contact_translation = [
      'navision_id' => 'Navision Id',
      'creditor_id' => 'Kreditor Id',
      'debitor_id'  => 'Debitor Id',
    ];

    private $entity_mapper = [
      'Contact' => 'Kontakt',
      'Address' => 'Adresse',
      'CustomContact' => 'Extra Felder Kontakt',
      'Email' => 'E-Mail',
      'Phone' => 'Telefon',
      'Website' => 'Webseite',
    ];

    private $location_type_entities = [
      'Address', 'Email', 'Phone',
    ];

    private $phone_types = [];
    private $location_types = [];

  /**
   * CRM_Nav_Exporter_Mailer constructor.
   */
  public function __construct() {
  }

  /**
   * @param        $template_name
   * @param        $contact_id
   * @param        $content
   * @param        $timestamp
   *
   * @param string $supervisor
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function create_email($template_name, $contact_id, $content, $timestamp, $supervisor = '') {
    $values = [];
    switch ($template_name) {
      case CRM_Nav_Config::$studienwerk_temlpate_name:
        $template_id = $this->get_template_id($template_name);
        $this->set_studienwerk_subject($supervisor, $template_id);
        $values['to_name'] = $this->to_name_sw ;
        $values['to_email'] = $this->to_email_sw;
        break;
      case CRM_Nav_Config::$kreditoren_temlpate_name:
        $this->filter_elements($content);
        if (empty($content)) {
          return "1";
        }
        $template_id = $this->get_template_id($template_name);
        $values['to_name'] = $this->to_name_kred;
        $values['to_email'] = $this->to_email_kred;
        break;
      default:
        throw new Exception("Invalid template type ({$template_name})!");
    }
    $values['id'] = $template_id;
    $values['from'] = "\"{$this->email_name_from}\" <{$this->email_from}>";
    $values['contact_id'] = $this->sender_contact_id;

    $this->add_translations($content);
    $contact_name = $this->get_contact_name($contact_id);
    $contact_link = $this->generate_civicrm_user_link($contact_id);
    $smarty_variables = [
      'contact_link' => $contact_link,
      'timestamp'    => $timestamp,
      'contact_id'   => $contact_id,
      'contact_name' => $contact_name,
      'navision_id'  => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['navision_id'],
      'creditor_id'  => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['creditor_id'],
      'debitor_id'   => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['debitor_id'],
      'contact_data' => $content,
    ];
    $values['template_params'] = $smarty_variables;
    $result = civicrm_api3('MessageTemplate', 'send', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Error sending Emails to {$template_name}");
    }
    return "0";
  }

  /**
   * @param $content
   */
  private function add_translations(&$content) {
    foreach ($content as $entity => &$values) {
      if ($entity == 'CustomContact') {
        // write default values to 'translation' field
        foreach ($values as $entity_id => &$e_values) {
          foreach ($e_values as $table_name => &$table_values) {
            if (array_key_exists($table_name, $this->custom_contact_translation)) {
              $table_values['translation'] = $this->custom_contact_translation[$table_name];
            }
            else {
              $table_values['translation'] = $table_name;
            }
          }
        }
        continue;
      }
      try{
        if ($entity == 'Contact') {
          $class_name = "CRM_{$entity}_DAO_{$entity}";
        } else {
          $class_name = "CRM_Core_DAO_{$entity}";
        }
        $entity_fields = $class_name::fields();
      } catch (Exception $e) {
        CRM_Core_Error::debug_log_message("[de.boell.civicrm.nav] Failed to get DAO Fields for Entity {$entity}");
        continue;
      }
      foreach ($values as $entity_id => &$e_values) {
        foreach ($e_values as $table_name => &$table_values) {
          if (isset($entity_fields[$table_name]['title'])) {
            if (in_array($entity, $this->location_type_entities)) {
              $table_values['translation'] = $this->get_location_type($entity, $entity_id, $entity_fields[$table_name]['title']);
            } else {
              $table_values['translation'] = $entity_fields[$table_name]['title'];
            }
          } else {
            $table_values['translation'] = $table_name;
          }
          // for country - get country name
          if ($table_name == 'country_id') {
            $this->set_country_id($table_values);
          }
          // for master_id - use shared-Contact name and id in braces
          if ($table_name == 'master_id') {
            $this->set_master_address_id($table_values);
          }
        }
      }
    }
    // Entity translation
    foreach ($content as $entity => &$values) {
      if (!array_key_exists($entity, $this->entity_mapper)) {
        continue;
      }
      // Add Entity translation and move array
      $content[$this->entity_mapper[$entity]] = $values;
      unset($content[$entity]);
    }
  }

  /**
   * Filter additional fields for kreditors defined in Config::$exclude_for_kreditoren
   * @param $content
   */
  private  function filter_elements(&$content) {
    foreach ($content as $entity => &$values) {
      if ($entity != "Contact") {
        continue;
      }
      foreach($values as $id => &$changed_values) {
        foreach($changed_values as $name => $v) {
          if (in_array($name, CRM_Nav_Config::$exclude_for_kreditoren)) {
            unset($changed_values[$name]);
          }
        }
        if (empty($values[$id])) {
          unset($values[$id]);
        }
      }
      if (empty($content[$entity])) {
        unset ($content[$entity]);
      }
    }
  }

  /**
   * @param $entity
   * @param $entity_id
   * @param $translated_entity
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_location_type($entity, $entity_id, $translated_entity) {
    $result = civicrm_api3($entity, 'getsingle', array(
      'sequential' => 1,
      'id' => $entity_id,
    ));
    $this->get_location_type_mapping();
    if ($entity == 'Phone') {
      $this->get_phone_type();
      return $this->phone_types[$result['phone_type_id']] . " (" . $this->location_types[$result['location_type_id']] . ")";
    }
    return $translated_entity . " (" . $this->location_types[$result['location_type_id']] . ")";
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function get_location_type_mapping() {
    $result = civicrm_api3('LocationType', 'get', array(
      'sequential' => 1,
    ));
    foreach ($result['values'] as $location_types) {
      $this->location_types[$location_types['id']] = $location_types['name'];
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private function get_phone_type() {
    if (empty($this->phone_types)) {
      $result = civicrm_api3('OptionValue', 'get', array(
        'sequential' => 1,
        'option_group_id' => "phone_type",
      ));
      foreach ($result['values'] as $values) {
        $this->phone_types[$values['value']] = $values['label'];
      }
    }
  }

  /**
   * Get contact name (first_name last_name, or if those are
   * empty display_name)
   *
   * @param $contact_id
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function get_contact_name($contact_id) {
    $result = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'return' => array("first_name", "last_name", "display_name"),
      'id' => $contact_id,
    ));
    if ($result['count'] == '1') {
      if (empty($result['values']['0']['first_name']) && empty($result['values']['0']['last_name'])) {
        // use display name instead
        $name = $result['values']['0']['display_name'];
      } else {
        $name = $result['values']['0']['first_name'] . " " . $result['values']['0']['last_name'];
      }
      return $name;
    }
  }

  /**
   * @param $template_name
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function get_template_id($template_name) {
    $result = civicrm_api3('MessageTemplate', 'get', array(
      'sequential' => 1,
      'msg_title' => $template_name,
    ));
    if ($result['count'] > '1' || $result['is_error'] == '1') {
      throw new Exception("Error determining Email Template for {$template_name}.");
    }
    if ($result['count'] == '0') {
      return $this->create_template($template_name);
    }
    if ($result['count'] == '1') {
      return $result['id'];
    }
    throw new Exception("Template not found - unclear state. Seek help");
  }

  /**
   * @param $template_name
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function create_template($template_name) {
    $template_content = file_get_contents(__DIR__ . "/../../../templates/mailer_template.tpl");
    $result = civicrm_api3('MessageTemplate', 'create', [
      'sequential'  => 1,
      'msg_title'   => $template_name,
      'msg_html'    => $template_content,
      'msg_subject' => $this->subject,
    ]);
    if ($result['is_error'] == '1') {
      throw new Exception("Coulnd't create message template.");
    }
    return $result['id'];
  }

  /**
   * @param $supervisor_suffix
   * @param $template_id
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function set_studienwerk_subject($supervisor_suffix, $template_id) {
    if ($supervisor_suffix != "not_set") {
      $subject = $supervisor_suffix . " - " . $this->subject;
    } else {
      $subject = $this->subject;
    }
    $result = civicrm_api3('MessageTemplate', 'create', [
      'sequential'  => 1,
      'id'    => $template_id,
      'msg_subject' => $subject,
    ]);
  }


  /**
   * @param $country_id
   */
  private function set_country_id(&$values) {
    $country_list = CRM_Core_PseudoConstant::country();
    // set translation for new
    $new_country_id = $values['new'];
    if (array_key_exists($new_country_id, $country_list)) {
      $values['new'] = $country_list[$new_country_id];
    }
    //if isset old -> add translation as well
    if(isset($values['old'])) {
      $old_country_id = $values['old'];
      if (array_key_exists($old_country_id, $country_list)) {
        $values['old'] = $country_list[$old_country_id];
      }
    }
  }

  /**
   * @param $values
   */
  private function set_master_address_id(&$values) {
    $new_contact_id = $values['new'];
    $result = civicrm_api3('Contact', 'getsingle', [
      'id' => $new_contact_id,
    ]);
    $contact_link = $this->generate_civicrm_user_link($new_contact_id);
    $new_contact_name = "<a href={$contact_link}>{$result['display_name']}</a>";
    $values['new'] = $new_contact_name;

    $values['new'] = $new_contact_name;
    if (isset($values['old'])) {
      $old_contact_id = $values['old'];
      $result = civicrm_api3('Contact', 'getsingle', [
        'id' => $old_contact_id,
      ]);
      $contact_link = $this->generate_civicrm_user_link($old_contact_id);
      $old_contact_name = "<a href={$contact_link}>{$result['display_name']}</a>";
      $values['old'] = $old_contact_name;
    }
  }


  /**
   * @param $contact_id
   *
   * @return string
   */
  private function generate_civicrm_user_link($contact_id) {
    return CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contact_id}", TRUE);
  }
}