<?php
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2018 SYSTOPIA                            |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

define('AIVLAPI_LOGGING', 1);

/**
 * Offers generic API processing functions
 */
class CRM_Aivlapi_Processor {

  /**
   * generic preprocessor for every call
   */
  public static function preprocessCall(&$params, $log_id = 'n/a') {
    self::fixAPIUser();
    if (AIVLAPI_LOGGING) {
      CRM_Core_Error::debug_log_message("{$log_id}: " . json_encode($params));
    }

    // resolve any custom fields
    CRM_Aivlapi_CustomData::resolveCustomFields($params);
  }

  /**
   * will use XCM to resolve the contact and add it as
   *  'contact_id' parameter in the params array
   */
  public static function resolveContact(&$params) {
    $params['check_permissions'] = 0;
    $contact_match = civicrm_api3('Contact', 'getorcreate', $params);
    $params['contact_id'] = $contact_match['id'];
  }

  /**
   * Make sure the current user exists
   */
  public static function fixAPIUser() {
    // see https://github.com/CiviCooP/org.civicoop.apiuidfix
    $session = CRM_Core_Session::singleton();
    $userId = $session->get('userID');
    if (empty($userId)) {
      $valid_user = FALSE;

      // Check and see if a valid secret API key is provided.
      $api_key = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
      if (!$api_key || strtolower($api_key) == 'null') {
        $session->set('userID', 2);
      }

      $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

      // If we didn't find a valid user, die
      if (!empty($valid_user)) {
        //now set the UID into the session
        $session->set('userID', $valid_user);
      }
    }
  }
}