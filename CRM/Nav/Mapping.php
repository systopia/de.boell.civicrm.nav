<?php
/*-------------------------------------------------------+
| Navision API Tools                                     |
| Heinrich Böll Stiftung                                 |
| Copyright (C) 2021 SYSTOPIA                            |
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

use CRM_Lijuldap_ExtensionUtil as E;


/**
 * Class CRM_Nav_Sync
 */
class CRM_Nav_Mapping {

  public static $nav_studienbereich = [
    '1' => 'Sprach- und Kulturwissenschaften allgemein',
    '2' => 'Medienwissenschaft',
    '3' => 'Evang. Theologie, -Religionslehre',
    '4' => 'Kath. Theologie, -Religionslehre',
    '5' => 'Philosophie',
    '6' => 'Geschichte',
    '7' => 'Informations- und Bibliothekswissenschaften',
    '8' => 'Allgemeine und vergleichende Literatur- und Sprachwissenschaft',
    '9' => 'Altphilologie (klass. Philologie), Neugriechisch',
    '10' => 'Germanistik (Deutsch, germanische Sprachen ohne Anglistik)',
    '11' => 'Anglistik, Amerikanistik',
    '12' => 'Romanistik',
    '13' => 'Slawistik, Baltistik, Finno-Ugristik',
    '14' => 'Sonstige Sprach- und Kulturwissenschaften',
    '15' => 'Kulturwissenschaften i.e.S.',
    '16' => '',
    '17' => '',
    '18' => 'Islamische Studien/Islamische Theologie',
    '19' => '',
    '20' => '',
    '21' => '',
    '22' => 'Sport, Sportwissenschaft',
    '23' => 'Rechts-, Wirtschafts- und Sozialwissenschaften allgemein',
    '24' => 'Kommunikationswissenschaft/Publizistik',
    '25' => 'Regionalwissenschaften',
    '26' => 'Politikwissenschaft',
    '27' => 'Sozialwissenschaften/Soziologie',
    '28' => 'Sozialwesen',
    '29' => 'Rechtswissenschaften',
    '30' => 'Verwaltungswissenschaften',
    '31' => 'Wirtschaftswissenschaften',
    '32' => 'Wirtschaftsingenieurwesen mit wirtschaftswiss. Schwerpunkt',
    '33' => 'Psychologie',
    '34' => 'Erziehungswissenschaften',
    '35' => '',
    '36' => 'Mathematik, Naturwissenschaften allgemein',
    '37' => 'Mathematik',
    '38' => '',
    '39' => 'Physik, Astronomie',
    '40' => 'Chemie',
    '41' => 'Pharmazie',
    '42' => 'Biologie',
    '43' => 'Geowissenschaften (ohne Geographie)',
    '44' => 'Geographie',
    '45' => '',
    '46' => '',
    '47' => '',
    '48' => 'Gesundheitswissenschaften allgemein',
    '49' => 'Humanmedizin (ohne Zahnmedizin)',
    '50' => 'Zahnmedizin',
    '51' => 'Veterinärmedizin',
    '52' => '',
    '53' => '',
    '54' => '',
    '55' => '',
    '56' => '',
    '57' => 'Landespflege, Umweltgestaltung',
    '58' => 'Agrarwissenschaften, Lebensmittel- und Getränketechnologie',
    '59' => 'Forstwissenschaft, Holzwirtschaft',
    '60' => 'Ernährungs- und Haushaltswissenschaften',
    '61' => 'Ingenieurwesen allgemein',
    '62' => 'Bergbau, Hüttenwesen',
    '63' => 'Maschinenbau/Verfahrenstechnik',
    '64' => 'Elektrotechnik und Informationstechnik',
    '65' => 'Verkehrstechnik, Nautik',
    '66' => 'Architektur, Innenarchitektur',
    '67' => 'Raumplanung',
    '68' => 'Bauingenieurwesen',
    '69' => 'Vermessungswesen',
    '70' => 'Wirtschaftsingenieurwesen mit ingenieurwiss. Schwerpunkt',
    '71' => 'Informatik',
    '72' => 'Materialwissenschaft und Werkstofftechnik',
    '73' => '',
    '74' => 'Kunst, Kunstwissenschaft allgemein',
    '75' => 'Bildende Kunst',
    '76' => 'Gestaltung',
    '77' => 'Darstellende Kunst, Film und Fernsehen, Theaterwissenschaft',
    '78' => 'Musik, Musikwissenschaft',
    '79' => '',
    '80' => '',
    '81' => '',
    '82' => '',
    '83' => 'Außerhalb der Studienbereichsgliederung',
    '84' => '',
    '85' => '',
    '86' => '',
    '87' => '',
    '88' => '',
    '89' => '',
    '90' => 'Außereuropäische Sprach- und Kulturwissenschaften',
    '91' => '',
    '92' => '',
    '93' => '',
    '94' => '',
    '95' => '',
    '96' => '',
    '97' => '',
    '98' => '',
    '99' => '',
    '100' => '',
  ];


  /**
   * @param $id
   * @return mixed
   * @throws CRM_Nav_EmptyStudienbereichMapping
   * @throws CRM_Nav_InternalApiError
   * @throws CRM_Nav_InvalidMappingKey
   * @throws CRM_Nav_MappingNotFound
   * @throws CRM_Nav_MultipleMappingMatches
   * @throws CiviCRM_API3_Exception
   */
  public static function get_civi_studienbereich_value($id) {
    if (!array_key_exists($id, self::$nav_studienbereich)) {
      throw new CRM_Nav_Exceptions_InvalidMappingKey("Invalid Studienbereich id ('{$id}')");
    }
    if (empty(self::$nav_studienbereich[$id])) {
      throw new CRM_Nav_Exceptions_EmptyStudienbereichMapping("No Mapping available for id {$id}");
    }
    $result = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'label' => self::$nav_studienbereich[$id],
    ]);
    if ($result['is_error'] != '0') {
      throw new CRM_Nav_Exceptions_InternalApiError('Internal API Error: ' . $result['error_message']);
    }
    if ($result['count'] == '0') {
      throw new CRM_Nav_Exceptions_MappingNotFound("No Result found for mapping Entry ('{$id}')");
    }
    if ($result['count'] > '1') {
      throw new CRM_Nav_Exceptions_MultipleMappingMatches("Found {$result['count']} matches for id '{$id}'");
    }
    foreach ($result['values'] as $values) {
      return $values['value'];
    }
  }
}