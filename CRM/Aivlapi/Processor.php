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

/**
 * Offers generic API processing functions
 */
class CRM_Aivlapi_Processor {

  private static $technical_fields = array('sequential', 'prettyprint', 'json', 'check_permissions', 'version');

  /**
   * generic preprocessor for every call
   */
  public static function preprocessCall(&$params, $log_id = 'n/a') {
    self::fixAPIUser();
    if (CRM_Aivlapi_Configuration::logAPICalls()) {
      CRM_Core_Error::debug_log_message("{$log_id}: " . json_encode($params));
    }

    // undo REST related changes
    CRM_Aivlapi_CustomData::unREST($params);

    // resolve any custom fields
    CRM_Aivlapi_CustomData::resolveCustomFields($params);
  }

  /**
   * strip the technical API fields from the params
   */
  public static function stripTechnicalFields(&$params) {
    foreach (self::$technical_fields as $field_name) {
      if (isset($params[$field_name])) {
        unset($params[$field_name]);
      }
    }
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
   * Sign contact up for a group if the two fields
   *  add_to_group
   *  add_to_group_id
   * are not empty.
   */
  public static function processGroupSignup($params) {
    if (   !empty($params['add_to_group'])
        && !empty($params['add_to_group_id'])
        && !empty($params['contact_id'])) {

      $group_id = (int) $params['add_to_group_id'];
      if ($group_id && !empty($params['contact_id'])) {
        civicrm_api3('GroupContact', 'create', array(
          'check_permissions' => 0,
          'contact_id'        => $params['contact_id'],
          'group_id'          => $group_id));
      }
    }
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
        $session->set('userID', CRM_Aivlapi_Configuration::getFallbackUserID());
      }

      $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

      // If we didn't find a valid user, die
      if (!empty($valid_user)) {
        //now set the UID into the session
        $session->set('userID', $valid_user);
      }
    }
  }

  /**
   * Render the given template with the given data
   */
  public static function renderTemplate($template_path, $data) {
    $smarty = CRM_Core_Smarty::singleton();

    // first backup original variables, since smarty instance is a singleton
    $oldVars = $smarty->get_template_vars();
    $backupFrame = array();
    foreach ($data as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $backupFrame[$key] = isset($oldVars[$key]) ? $oldVars[$key] : NULL;
    }

    // then assign new variables
    foreach ($data as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    // create result
    $rendered_text =  $smarty->fetch($template_path);

    // reset smarty variables
    foreach ($backupFrame as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    return $rendered_text;
  }
}