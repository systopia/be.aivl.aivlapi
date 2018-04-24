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
 * Process AivlEvent.register
 *
 * @param see specs below (_civicrm_api3_engage_signpetition_spec)
 * @return array API result array
 * @access public
 */
function civicrm_api3_aivl_petition_sign($params) {

  // preprocess incoming call
  CRM_Aivlapi_Processor::preprocessCall($params, 'AivlPetition.sign');

  // resolve contact
  CRM_Aivlapi_Processor::resolveContact($params);

  error_log("STEP 1");
  // create petition activity
  $activity_data = array(
    'check_permissions'  => 0,
    'source_contact_id'  => CRM_Aivlapi_Configuration::getAivlContactID(),
    'activity_type_id'   => CRM_Aivlapi_Configuration::getPetitionActivityTypeID(),
    'status_id'          => CRM_Core_OptionGroup::getValue('activity_status', 'Completed'),
    'target_contact_id'  => $params['contact_id'],
    'activity_date_time' => date('Y-m-d H:i:s'),
    // 'source_record_id'   => won't set, AIVL doesn't want this connection
    'campaign_id'        => (int) $params['campaign_id'],
  );
  error_log("STEP 2");
  // add campaign title to subject
  $activity_data['subject'] = civicrm_api3('Campaign', 'getvalue', array(
    'check_permissions' => 0,
    'id'                => (int) $params['campaign_id'],
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

    error_log("STEP 3");
    if ($existing_activity_count > 0) {
      CRM_Core_Error::debug_log_message('Petition activity for contact '.$params['contact_id'].', campaign '
        .$params['campaign_id'].' already exists, not duplicated');
      return civicrm_api3_create_success("Already signed");

    } else {
      // create subscription activity
      return civicrm_api3('Activity', 'create', $activity_data);
    }
}

/**
 * Adjust Metadata for Payment action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_aivl_petition_sign_spec(&$params) {
  $params['campaign_id'] = array(
    'name'         => 'campaign_id',
    'api.required' => 1,
    'title'        => 'Campaign ',
    );
}
