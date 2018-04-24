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
 * Settings and configurations
 */
class CRM_Aivlapi_Configuration {

  protected static $registration_update_acvivity_id = NULL;
  protected static $petition_signed_acvivity_id     = NULL;
  protected static $domain_contact_id               = NULL;


  /**
   * If the system fails to detect the user this ID will be used
   */
  public static function logAPICalls() {
    // TODO: settings page?
    return TRUE;
  }

  /**
   * If the system fails to detect the user this ID will be used
   */
  public static function getFallbackUserID() {
    // TODO: settings page?
    return 233477;
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
   * get the Partition Signed activity ID
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