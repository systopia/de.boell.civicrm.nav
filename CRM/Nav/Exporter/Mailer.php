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

//  private $to_email_sw             = 'civicrm-stw@boell.de';
//  private $to_email_kred           = 'kreditorenNAV2009@boell.de';

    private $to_email_sw             = 'batroff@systopia.de';
    private $to_email_kred           = 'batroff@systopia.de';

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
    $smarty_variables = [
      'timestamp'    => $timestamp,
      'contact_id'   => $contact_id,
      'navision_id'  => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['navision_id'],
      'creditor_id'  => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['creditor_id'],
      'debitor_id'  => CRM_Nav_ChangeTracker_LogAnalyzeRunner::$nav_id_cache[$contact_id]['debitor_id'],
      'contact_data' => $content,
    ];
    $values['template_params'] = $smarty_variables;
    $result = civicrm_api3('MessageTemplate', 'send', $values);
    if ($result['is_error'] == '1') {
      throw new Exception("Error sending Emails to {$template_name}");
    }
  }

  /**
   * @param $content
   */
  private function add_translations(&$content) {
    foreach ($content as $entity => &$values) {
      if ($entity == 'CustomContact') {
        // write default values to 'translation' field
        foreach ($values as $table_name => &$table_values) {
          if (array_key_exists($table_name, $this->custom_contact_translation)) {
            $table_values['translation'] = $this->custom_contact_translation[$table_name];
          } else {
            $table_values['translation'] = $table_name;
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

      foreach ($values as $table_name => &$table_values) {
        if (isset($entity_fields[$table_name]['title'])) {
          $table_values['translation'] = $entity_fields[$table_name]['title'];
        } else {
          $table_values['translation'] = $table_name;
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

  private function set_studienwerk_subject($supervisor_suffix, $template_id) {
    if ($supervisor_suffix != not_set) {
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
}