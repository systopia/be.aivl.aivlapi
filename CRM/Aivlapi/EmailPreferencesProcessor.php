<?php
use CRM_Aivlapi_ExtensionUtil as E;
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2019 CiviCooP                            |
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
 * Processing class for email preferences
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 4 Nov 2019
 * @license AGPL-3.0
 */

class CRM_Aivlapi_EmailPreferencesProcessor {

  private $_signupSourceName = NULL;
  private $_signupSourceValue = NULL;

  /**
   * Method to retrieve the current email preferences for a contact based on contact hash
   *
   * @param $contactHash
   * @return array
   */
  public static function get($contactHash) {
    $result = [];
    if (!empty($contactHash)) {
      $emailPreferences = new CRM_Aivlapi_EmailPreferencesProcessor();
      $result = $emailPreferences->getContactNames($contactHash) + $emailPreferences->calculateCurrentPreferences($contactHash);
      $removes = ['is_error', 'hash', 'contact_is_deleted', 'is_deceased', 'id', 'contact_id'];
      foreach ($removes as $remove) {
        if (isset($result[$remove])) {
          unset($result[$remove]);
        }
      }
    }
    return $result;
  }

  /**
   * Method to add the contact names from the dao to the incoming array
   *
   * @param string $contactHash
   * @return array $result
   */
  private function getContactNames($contactHash) {
    $result = [];
    if (!empty($contactHash)) {
      try {
        $result = civicrm_api3('Contact', 'getsingle', [
          'return' => ["contact_type", "sort_name", "display_name", "first_name", "last_name", "organization_name", "household_name", "addressee_display"],
          'hash' => $contactHash,
          'is_deceased' => 0,
          'is_deleted' => 0,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $result;
  }

  /**
   * Method to calculate the current email preferences from a dao
   *
   * @param string $contactHash
   * @return  array $result
   */
  private function calculateCurrentPreferences($contactHash) {
    $result = [
      'news_action' => "0",
      'news_only' => "0",
      'year_only' => "0",
      'no_action' => "0",
      'write_actions' => "0",
      'call_for_action' => "0",
    ];
    $news = FALSE;
    $actions = FALSE;
    $query = "
        SELECT cg.name AS group_name
        FROM civicrm_contact AS cc
        JOIN civicrm_group_contact AS cgc ON cc.id = cgc.contact_id
        JOIN civicrm_group AS cg ON cgc.group_id = cg.id
        WHERE cc.hash = %1 AND cgc.status = %2";
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$contactHash, "String"],
      2 => ["Added", "String"],
    ]);
    while ($dao->fetch()) {
      switch ($dao->group_name) {
        case "aivl_call_for_action":
          $result['call_for_action'] = 1;
          break;

        case "aivl_letterwriting":
          $result['write_actions'] = 1;
          break;

        case "aivl_year_news":
          $result['year_only'] = 1;
          break;

        case "aivl_monthly_actions":
          $actions = TRUE;
          break;

        case "aivl_monthly_newsletter":
          $news = TRUE;
          break;
      }
    }
    if ($news && $actions) {
      $result['news_action'] = 1;
    }
    if ($news && !$actions) {
      $result['news_only'] = 1;
    }
    return $result;
  }
}
