<?php
use CRM_Aivlapi_ExtensionUtil as E;

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
 * Settings and configurations
 */
class CRM_Aivlapi_Configuration {

  // generic configuration
  protected static $configuration                   = NULL;

  protected static $registration_update_acvivity_id = NULL;
  protected static $petition_signed_acvivity_id     = NULL;
  protected static $_webformSignupActivityTypeId    = NULL;
  protected static $_sourceRecordTypeId             = NULL;
  protected static $_targetRecordTypeId             = NULL;
  protected static $domain_contact_id               = NULL;


  /**
   * Get a setting
   *
   * @param $name
   * @param null $default
   *
   * @return mixed
   */
  public static function getSetting($name, $default = NULL) {
    if (self::$configuration === NULL) {
      self::$configuration = CRM_Core_BAO_Setting::getItem('be.aivl.aivlapi', 'aivlapi_config');
    }

    return CRM_Utils_Array::value($name, self::$configuration, $default);
  }


  /**
   * If the system fails to detect the user this ID will be used
   */
  public static function logAPICalls() {
    return self::getSetting('debug', FALSE);
  }

  /**
   * If the system fails to detect the user this ID will be used
   */
  public static function getFallbackUserID() {
    return self::getSetting('fallback_contact_id', 2);
  }

  /**
   * Get the AIVL Org contact ID
   */
  public static function getAivlContactID() {
    if (self::$domain_contact_id === NULL) {
      self::$domain_contact_id = civicrm_api3('Domain', 'getvalue', array(
        'return'  => 'contact_id',
        'options' => array('limit' => 1),
      ));
    }
    return self::$domain_contact_id;
  }

  /**
   * get the Petition Signed activity ID
   */
  public static function getPetitionActivityTypeID($create_if_not_found = TRUE) {
    if (self::$petition_signed_acvivity_id === NULL) {
      $search = civicrm_api3('OptionValue', 'get', array(
        'check_permissions' => 0,
        'option_group_id'   => 'activity_type',
        'name'              => 'petition_signed',
        'return'            => 'id,value'
      ));
      if (!empty($search['id'])) {
        $activity_type = reset($search['values']);
        self::$petition_signed_acvivity_id = $activity_type['value'];
      } elseif ($create_if_not_found) {
        $create = civicrm_api3('OptionValue', 'create', array(
          'check_permissions' => 0,
          'option_group_id'   => 'activity_type',
          'name'              => 'petition_signed',
          'label'             => 'Petition Signed',
          'description'       => 'Activity Type used when petition form signed',
          'is_reserved'       => 1,
        ));
        return self::getPetitionActivityTypeID(FALSE);
      } else {
        throw new Exception("Cannot find petition_signed activity type");
      }
    }
    return self::$petition_signed_acvivity_id;
  }

  /**
   * get the Webform Signup activity type ID
   *
   * @param bool $createIfNotFound
   * @return int
   * @throws
   */
  public static function getWebformSignupActivityTypeID($createIfNotFound = TRUE) {
    if (self::$_webformSignupActivityTypeId === NULL) {
      $search = civicrm_api3('OptionValue', 'get', array(
        'check_permissions' => 0,
        'option_group_id'   => 'activity_type',
        'name'              => 'Response via webform',
        'return'            => 'id,value'
      ));
      if (!empty($search['id'])) {
        $activityType = reset($search['values']);
        self::$_webformSignupActivityTypeId = $activityType['value'];
      }
      elseif ($createIfNotFound) {
          civicrm_api3('OptionValue', 'create', array(
          'check_permissions' => 0,
          'option_group_id'   => 'activity_type',
          'name'              => 'Response via webform',
          'label'             => E::ts('Webform Signup'),
          'description'       => E::ts('Information/response sent to CiviCRM via form on the website.'),
          'is_reserved'       => 1,
        ));
        return self::getWebformSignupActivityTypeID(FALSE);
      }
      else {
        throw new Exception(E::ts("Cannot find Webform Signup (name = Response via webform) activity type"));
      }
    }
    return self::$_webformSignupActivityTypeId;
  }

  /**
   * get the source record type (for activity contact)
   *
   * @return int
   * @throws
   */
  public static function getSourceRecordType() {
    if (self::$_sourceRecordTypeId === NULL) {
      $search = civicrm_api3('OptionValue', 'get', array(
        'check_permissions' => 0,
        'option_group_id'   => 'activity_contacts',
        'name'              => 'Activity Source',
        'return'            => 'id,value'
      ));
      if (!empty($search['id'])) {
        $recordType = reset($search['values']);
        self::$_sourceRecordTypeId = $recordType['value'];
      }
      else {
        throw new Exception(E::ts("Cannot find activity_contacts option value with name Activity Source"));
      }
    }
    return self::$_sourceRecordTypeId;
  }
  /**
   * get the target record type (for activity contact)
   *
   * @return int
   * @throws
   */
  public static function getTargetRecordType() {
    if (self::$_targetRecordTypeId === NULL) {
      $search = civicrm_api3('OptionValue', 'get', array(
        'check_permissions' => 0,
        'option_group_id'   => 'activity_contacts',
        'name'              => 'Activity Targets',
        'return'            => 'id,value'
      ));
      if (!empty($search['id'])) {
        $recordType = reset($search['values']);
        self::$_targetRecordTypeId = $recordType['value'];
      }
      else {
        throw new Exception(E::ts("Cannot find activity_contacts option value with name Activity Targets"));
      }
    }
    return self::$_targetRecordTypeId;
  }

  /**
   * get the activity ID to be used for duplicate registrations
   *  and/or registration updates
   */
  public static function getRegistrationUpdateActivityID() {
    if (self::$registration_update_acvivity_id == NULL) {
      $activity_types = civicrm_api3('OptionValue', 'get', array(
        'check_permissions' => 0,
        'option_group_id'   => 'activity_type',
        'name'              => 'aivl_registration_update'
      ));
      if (empty($activity_types['count'])) {
        // not there yet -> create
        $activity_type = civicrm_api3('OptionValue', 'create', array(
          'check_permissions' => 0,
          'option_group_id'   => 'activity_type',
          'name'              => 'aivl_registration_update',
          'label'             => 'AIVL Event Registration Update',
          'is_active'         => 1,
        ));
        $activity_types = civicrm_api3('OptionValue', 'get', array(
          'check_permissions' => 0,
          'id'                => $activity_type['id']));
      }

      $activity_type = reset($activity_types['values']);
      self::$registration_update_acvivity_id = $activity_type['value'];
    }
    return self::$registration_update_acvivity_id;
  }
}