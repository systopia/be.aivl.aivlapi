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

  private $_signupSourceName = NULL;
  private $_signupSourceValue = NULL;

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
    $basicSignup = new CRM_Aivlapi_BasicSignupProcessor();
    if (isset($params['aivl_signup_source'])) {
      $params['aivl_signup_source'] = strtolower(trim($params['aivl_signup_source']));
      $basicSignup->_signupSourceName = $params['aivl_signup_source'];
      $basicSignup->getOrCreateSignupSource();
    }
    // create basic signup activity
    $activityData = $basicSignup->setSignUpActivityData($params);
    // create if not already exists
    if (!$basicSignup->alreadyExists($activityData)) {
      try {
        // create basic webform signup activity
        civicrm_api3('Activity', 'create', $activityData);
        // if required add To Check activity
        if ($basicSignup->_signupSourceValue) {
          $basicSignup->processToCheck($params['contact_id']);
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new API_Exception(E::ts('Could not create an activity for a basic webform signup in') . __METHOD__
          . E::ts(', error message from API Activity create: ') . $ex->getMessage(), 9901);
      }
    }
    return [];
  }

  /**
   * Method to check if to check activity is required and create if so
   *
   * @param $contactId
   */
  private function processToCheck($contactId) {
    // only create to check activity if setting says it is required for this kind of signup source
    $sourceActivities = Civi::settings()->get('aivl_basic_signup_source_activities');
    foreach ($sourceActivities as $sourceActivityId => $sourceActivity) {
      if ($this->_signupSourceValue == $sourceActivityId) {
        if ($sourceActivity == 1) {
          $toCheckData = [
            'check_permissions'  => 0,
            'source_contact_id'  => CRM_Aivlapi_Configuration::getAivlContactID(),
            'activity_type_id'   => CRM_Aivlbasicsignup_BasicSignupConfig::singleton()->getToCheckActivityTypeId(),
            'status_id'          => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
            'target_contact_id'  => $contactId,
            'activity_date_time' => date('Y-m-d H:i:s'),
            'subject'            => 'To check response from webform ' . $this->_signupSourceName,
          ];
          // now retrieve the assignee for the activity
          $sourceAssignees = Civi::settings()->get('aivl_basic_signup_source_assignees');
          foreach ($sourceAssignees as $sourceAssigneeId => $sourceAssignee) {
            if ($sourceAssigneeId == $this->_signupSourceValue && !empty($sourceAssignee)) {
              $toCheckData['assignee_contact_id'] = $sourceAssignee;
            }
          }
          try {
            civicrm_api3('Activity', 'create' , $toCheckData);
          }
          catch (CiviCRM_API3_Exception $ex) {
            Civi::log()->error(E::ts('Could not create a to check activity in') . __METHOD__
              . E::ts(', error message from API Activity create: ') . $ex->getMessage(), 9901);
          }
        }
      }
    }
  }

  /**
   * Method to check if an activity already exists
   *
   * @param $activityData
   * @throws Exception
   * @return bool
   */
  private function alreadyExists($activityData) {
    $queryParams = [
      1 => [CRM_Aivlapi_Configuration::getSourceRecordType(), 'Integer'],
      2 => [CRM_Aivlapi_Configuration::getTargetRecordType(), 'Integer'],
      3 => [$activityData['activity_type_id'], 'Integer'],
      4 => [1, 'Integer'],
      5 => [0, 'Integer'],
      6 => [$activityData['source_contact_id'], 'Integer'],
      7 => [$activityData['target_contact_id'], 'Integer'],
    ];
    $query = "SELECT COUNT(*) FROM civicrm_activity a
      LEFT JOIN civicrm_activity_contact src ON a.id = src.activity_id AND src.record_type_id = %1
      LEFT JOIN civicrm_activity_contact tar ON a.id = tar.activity_id AND tar.record_type_id = %2
      WHERE a.activity_type_id = %3 AND a.is_current_revision = %4
        AND a.is_deleted = %5 AND a.is_test = %5 AND src.contact_id = %6 AND tar.contact_id = %7";
    if (isset($activityData['campaign_id'])) {
      $queryParams[8] = [$activityData['campaign_id'], 'Integern'];
      $query .= " AND a.campaign_id = %8";
    }
    $existingActivityCount = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($existingActivityCount > 0) {
      if (isset($activityData['campaign_id'])) {
        Civi::log()->debug(E::ts('Webform Signup activity for contact ' . $activityData['target_contact_id'] . ', campaign '
          . $activityData['campaign_id'] . ' already exists, not duplicated'));
      }
      else {
        Civi::log()->debug(E::ts('Webform Signup activity for contact ' . $activityData['target_contact_id'] .
          ' already exists, not duplicated'));
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to set the activity data for a signup activity
   *
   * @param array $params
   * @return array $activityData
   * @throws Exception
   */
  private function setSignUpActivityData($params) {
    $activityData = [
      'check_permissions'  => 0,
      'source_contact_id'  => (int) CRM_Aivlapi_Configuration::getAivlContactID(),
      'activity_type_id'   => (int) CRM_Aivlapi_Configuration::getWebformSignupActivityTypeID(),
      'status_id'          => (int) CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      'target_contact_id'  => (int) $params['contact_id'],
      'activity_date_time' => date('Y-m-d H:i:s'),
      // 'source_record_id'   => won't set, AIVL doesn't want this connection
    ];
    if (isset($params['campaign_id'])) {
      $activityData['campaign_id'] = (int) $params['campaign_id'];
    }
    // if source signup value/name use to add to subject and set custom field
    if ($this->_signupSourceName) {
      $activityData['subject'] = 'Response via webform ' . $this->_signupSourceName;
    }
    else {
      $activityData['subject'] = 'Response via webform';
    }
    if ($this->_signupSourceValue) {
      $customFieldId = CRM_Aivlbasicsignup_BasicSignupConfig::singleton()->getBasicSignupCustomField('aivl_basic_signup_source', 'id');
      if ($customFieldId) {
        $activityData['custom_' . $customFieldId] = $this->_signupSourceValue;
      }
    }
    return $activityData;
  }

  /**
   * Method to get or create the value of the signup source
   *
   */
  public function getOrCreateSignupSource() {
    // first check if there is an option value for the signup source
    try {
      $count = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => CRM_Aivlbasicsignup_BasicSignupConfig::singleton()->getBasicSignupSourcesOptionGroupId(),
        'name' => $this->_signupSourceName,
      ]);
      switch ($count) {
        case 0:
          // create if it does not exist yet
          try {
            $nameParts = explode(' ', $this->_signupSourceName);
            $labelParts = [];
            foreach ($nameParts as $namePartId => $namePart) {
              $labelParts[] = ucfirst($namePart);
              $nameParts[$namePartId] = strtolower($namePart);
            }
            $signupLabel = implode(' ', $labelParts);
            $signupName = implode('_', $nameParts);
            $result = civicrm_api3('OptionValue', 'create', [
              'sequential' => 1,
              'option_group_id' => CRM_Aivlbasicsignup_BasicSignupConfig::singleton()->getBasicSignupSourcesOptionGroupId(),
              'label' => $signupLabel,
              'name' => $signupName,
            ]);
            if (isset($result['values']['name'])) {
              $this->_signupSourceName = $result['values']['name'];
              if (isset($result['values']['value'])) {
                $this->_signupSourceValue = $result['values']['value'];
              }
            }
          }
          catch (CiviCRM_API3_Exception $ex) {
            Civi::log()->error(E::ts('Could not create option value in signup source option group in ') . __METHOD__);
          }
          break;

        case 1:
          // return if 1 found
          try {
            $this->_signupSourceValue = (string) civicrm_api3('OptionValue', 'getvalue', [
              'option_group_id' => CRM_Aivlbasicsignup_BasicSignupConfig::singleton()->getBasicSignupSourcesOptionGroupId(),
              'name' => $this->_signupSourceName,
              'return' => 'value',
            ]);
          }
          catch (CiviCRM_API3_Exception $ex) {
            Civi::log()->error(E::ts('Could not find the signup source with name ') . $this->_signupSourceName . E::ts(' in ')
              . __METHOD__ . E::ts(', error message from API OptionValue getvalue: ') . $ex->getMessage());
          }
          break;

        default:
          // log error if more than 1 found!
          Civi::log()->error(E::ts('More than one sign up sources with name ') . $this->_signupSourceName . E::ts(' found in ') . __METHOD__);
          break;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Unexpected error from API OptionValue getcount in ') . __METHOD__
        . E::ts(' with error message ') . $ex->getMessage());
    }
  }
}
