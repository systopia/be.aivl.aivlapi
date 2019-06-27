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
 * Processing class for basic signup
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 27 Jun 2019
 * @license AGPL-3.0
 */

class CRM_Aivlapi_BasicSignupProcessor {

  /**
   * Sign $params['contact_id'] up for all petitions found in the $params:
   *   $params['campaign_id'], and/or
   *   $params['campaign_XX'] with XX being the campaign ID and the value being not empty()
   *
   * @param $params
   * @return array stats: error, counter_already, counter_signed
   * @throws
   */
  public static function basicSignup($params) {
    if (empty($params['contact_id'])) {
      // there's nobody to sign up..
      return ['error'  => 'No contact identified'];
    }
    // create basic signup activity
    $activityData = [
        'check_permissions'  => 0,
        'source_contact_id'  => CRM_Aivlapi_Configuration::getAivlContactID(),
        'activity_type_id'   => CRM_Aivlapi_Configuration::getWebformSignupActivityTypeID(),
        'status_id'          => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
        'target_contact_id'  => $params['contact_id'],
        'activity_date_time' => date('Y-m-d H:i:s'),
        // 'source_record_id'   => won't set, AIVL doesn't want this connection
    ];
    if (isset($params['campaign_id'])) {
      $activityData['campaign_id'] = (int) $params['campaign_id'];
    }
    // check for hidden field with source of activity. If so, use to add to subject and set custom field

    // if applicable, create activity To Check for signup

    // check if signup already exists
    $existingActivityCount = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_activity a
      LEFT JOIN civicrm_activity_contact src ON a.id = src.activity_id AND src.record_type_id = %1
      LEFT JOIN civicrm_activity_contact tar ON a.id = tar.activity_id AND tar.record_type_id = %2
      WHERE a.activity_type_id = %3 AND a.campaign_id = %4 AND a.is_current_revision = %5
        AND a.is_deleted = %6 AND a.is_test = %6 AND src.contact_id = %7 AND tar.contact_id = %8",
          [
            1 => [CRM_Aivlapi_Configuration::getSourceRecordType(), 'Integer'],
            2 => [CRM_Aivlapi_Configuration::getTargetRecordType(), 'Integer'],
            3 => [$activityData['activity_type_id'], 'Integer'],
            4 => [$activityData['campaign_id'], 'Integer'],
            5 => [1, 'Integer'],
            6 => [0, 'Integer'],
            7 => [$activityData['source_contact_id'], 'Integer'],
            8 => [$activityData['target_contact_id'], 'Integer'],
          ]);
    if ($existingActivityCount > 0) {
      Civi::log()->debug(E::ts('Webform Signup activity for contact ' . $params['contact_id'] . ', campaign '
          . $activityData['campaign_id'] . ' already exists, not duplicated'));
    }
    else {
      // create activity
      civicrm_api3('Activity', 'create', $activityData);
    }
    return [];
  }
}