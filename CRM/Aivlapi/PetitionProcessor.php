<?php
/*-------------------------------------------------------+
| Amnesty Iternational Vlaanderen Custom API             |
| Copyright (C) 2019 SYSTOPIA                            |
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
 * Offers relationship API processing functions
 */
class CRM_Aivlapi_PetitionProcessor {

  /**
   * Sign $params['contact_id'] up for all petitions found in the $params:
   *   $params['campaign_id'], and/or
   *   $params['campaign_XX'] with XX being the campaign ID and the value being not empty()
   *
   * @param $params
   * @return array stats: error, counter_already, counter_signed
   * @throws CiviCRM_API3_Exception
   */
  public static function signPetitions($params) {
    if (CRM_Aivlapi_Configuration::logAPICalls()) {
      CRM_Core_Error::debug_log_message("AivlAPI::signPetitions: " . json_encode($params));
    }

    if (empty($params['contact_id'])) {
      // there's nobody to sign up..
      return array('error'  => 'No contact identified');
    }

    // create petition activity
    $activity_data = array(
        'check_permissions'  => 0,
        'source_contact_id'  => CRM_Aivlapi_Configuration::getAivlContactID(),
        'activity_type_id'   => CRM_Aivlapi_Configuration::getPetitionActivityTypeID(),
        'status_id'          => CRM_Core_OptionGroup::getValue('activity_status', 'Completed'),
        'target_contact_id'  => $params['contact_id'],
        'activity_date_time' => date('Y-m-d H:i:s'),
        // 'source_record_id'   => won't set, AIVL doesn't want this connection
        'campaign_id'        => (int)$params['campaign_id'],
    );

    // extract campaign_ids:
    $campaign_ids = array();
    if (!empty($params['campaign_id'])) {
      $campaign_ids[] = $params['campaign_id'];
    }
    // extract from campaign_xx fields
    foreach ($params as $key => $value) {
      if (preg_match("#^campaign_(?P<campaign_id>[0-9]+)$#", $key, $match)) {
        if (!empty($value)) {
          $campaign_ids[] = $match['campaign_id'];
        }
      }
    }

    // create a signature for each campaign
    $counter_signed = 0;
    $counter_already = 0;
    foreach ($campaign_ids as $campaign_id) {
      $activity_data['campaign_id'] = $campaign_id;

      // add campaign title to subject
      $activity_data['subject'] = civicrm_api3('Campaign', 'getvalue', array(
          'check_permissions' => 0,
          'id'                => (int)$activity_data['campaign_id'],
          'return'            => 'title'));

      // check if petition signature already exists
      //   (copied from be.aivl.webcontacts)
      $existing_activity_count = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_activity a
      LEFT JOIN civicrm_activity_contact src ON a.id = src.activity_id AND src.record_type_id = %1
      LEFT JOIN civicrm_activity_contact tar ON a.id = tar.activity_id AND tar.record_type_id = %2
      WHERE a.activity_type_id = %3 AND a.campaign_id = %4 AND a.is_current_revision = %5
        AND a.is_deleted = %6 AND a.is_test = %6 AND src.contact_id = %7 AND tar.contact_id = %8",
          array(1 => array(2,                                   'Integer'),
                2 => array(3,                                   'Integer'),
                3 => array($activity_data['activity_type_id'],  'Integer'),
                4 => array($activity_data['campaign_id'],       'Integer'),
                5 => array(1,                                   'Integer'),
                6 => array(0,                                   'Integer'),
                7 => array($activity_data['source_contact_id'], 'Integer'),
                8 => array($activity_data['target_contact_id'], 'Integer')));

      if ($existing_activity_count > 0) {
        CRM_Core_Error::debug_log_message('Petition activity for contact ' . $params['contact_id'] . ', campaign '
            . $activity_data['campaign_id'] . ' already exists, not duplicated');
        $counter_already += 1;

      } else {
        // create subscription activity
        civicrm_api3('Activity', 'create', $activity_data);
        $counter_signed += 1;
      }
    }

    return array(
        'counter_already' => $counter_already,
        'counter_signed'  => $counter_signed
    );
  }
}