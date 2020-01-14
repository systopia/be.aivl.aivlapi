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

  private $_monthlyNewsletterGroup = [];
  private $_monthlyActionsGroup = [];
  private $_yearlyReportGroup = [];
  private $_specificGroups = [];
  private $_noGeneralInfo = NULL;

  /**
   * CRM_Aivlapi_EmailPreferencesProcessor constructor.
   */
  public function __construct() {
    $this->_noGeneralInfo = 'geen_algemene_informatie';
    $groupIds = [
      (int) Civi::settings()->get('aivl_monthly_newsletter_group_id'),
      (int) Civi::settings()->get('aivl_monthly_actions_group_id'),
      (int) Civi::settings()->get('aivl_yearly_report_group_id'),
    ];
    $specificSetting = Civi::settings()->get('aivl_specific_group_ids');
    $specificGroupIds = explode(",", $specificSetting);
    foreach ($specificGroupIds as $specificGroupId) {
      $groupIds[] = $specificGroupId;
    }
    try {
      $apiGroupData = civicrm_api3('Group', 'get', [
          'return' => ["title"],
          'options' => ['limit' => 0],
          'id' => ['IN' => $groupIds],
        ]);
      foreach ($apiGroupData['values'] as $groupId => $groupData) {
        switch ($groupId) {
          case Civi::settings()->get('aivl_monthly_newsletter_group_id'):
            $this->_monthlyNewsletterGroup = [
              'group_id' => $groupId,
              'title' => $this->generateWebformName($groupData['title']),
            ];
            break;
          case Civi::settings()->get('aivl_monthly_actions_group_id'):
            $this->_monthlyActionsGroup = [
              'group_id' => $groupId,
              'title' => $this->generateWebformName($groupData['title']),
            ];
            break;
          case Civi::settings()->get('aivl_yearly_report_group_id'):
            $this->_yearlyReportGroup = [
              'group_id' => $groupId,
              'title' => $this->generateWebformName($groupData['title']),
              ];
            break;
          default:
            if (in_array($groupId, $specificGroupIds)) {
              $this->_specificGroups[] = [
                'group_id' => $groupId,
                'title' => $this->generateWebformName($groupData['title']),
                ];
            }
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to generate the webform field name from the group title
   * @param $groupTitle
   * @return mixed|string
   */
  private function generateWebformName($groupTitle) {
    $webformName = strtolower($groupTitle);
    $webformName = str_replace(' ', '_', $webformName);
    $webformName = str_replace(',', '_', $webformName);
    $webformName = str_replace('.', '_', $webformName);
    $webformName = str_replace(';', '_', $webformName);
    $webformName = str_replace(':', '_', $webformName);
    $webformName = str_replace('__', '_', $webformName);
    return $webformName;
  }

  /**
   * Method to retrieve the current email preferences for a contact based on contact hash
   *
   * @param array $params
   * @return array
   */
  public static function get($params) {
    $result = [];
    if (!empty($params['contact_id'])) {
      $emailPreferences = new CRM_Aivlapi_EmailPreferencesProcessor();
      try {
        $result = civicrm_api3('Contact', 'getsingle', [
          'return' => ["contact_type", "sort_name", "display_name", "first_name", "last_name", "organization_name", "household_name", "addressee_display"],
          'id' => $params['contact_id'],
          'is_deceased' => 0,
          'is_deleted' => 0,
        ]);
        $removes = ['is_error', 'hash', 'contact_is_deleted', 'is_deceased', 'id'];
        foreach ($removes as $remove) {
          if (isset($result[$remove])) {
            unset($result[$remove]);
          }
        }
        // add email preferences
        $emailPreferences->getCurrentPreferences($params, $result);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $result;
  }

  /**
   * Method to set the email preferences for a contact
   *
   * @param $params
   * @return array
   */
  public static function set($params) {
    $result = [];
    $adds = [];
    $removes = [];
    $emailPreferences = new CRM_Aivlapi_EmailPreferencesProcessor();
    // sanitize params (might be arrays)
    $emailPreferences->sanitizeSetParams($params);
    if (isset($params[$emailPreferences->_noGeneralInfo]) && $params[$emailPreferences->_noGeneralInfo] == "1") {
      $removes[] = $emailPreferences->_monthlyNewsletterGroup['group_id'];
      $removes[] = $emailPreferences->_monthlyActionsGroup['group_id'];
      $removes[] = $emailPreferences->_yearlyReportGroup['group_id'];
    }
    else {
      if (isset($params[$emailPreferences->_yearlyReportGroup['title']]) && $params[$emailPreferences->_yearlyReportGroup['title']] == "1") {
        $removes[] = $emailPreferences->_monthlyNewsletterGroup['group_id'];
        $removes[] = $emailPreferences->_monthlyActionsGroup['group_id'];
        $adds[] = $emailPreferences->_yearlyReportGroup['group_id'];
      } else {
        if (isset($params[$emailPreferences->_monthlyActionsGroup['title']]) && $params[$emailPreferences->_monthlyActionsGroup['title']] == "1") {
          $adds[] = $emailPreferences->_monthlyNewsletterGroup['group_id'];
          $adds[] = $emailPreferences->_monthlyActionsGroup['group_id'];
          $removes[] = $emailPreferences->_yearlyReportGroup['group_id'];
        } else {
          if (isset($params[$emailPreferences->_monthlyNewsletterGroup['title']]) && $params[$emailPreferences->_monthlyNewsletterGroup['title']] == "1") {
            $adds[] = $emailPreferences->_monthlyNewsletterGroup['group_id'];
            $removes[] = $emailPreferences->_monthlyActionsGroup['group_id'];
            $removes[] = $emailPreferences->_yearlyReportGroup['group_id'];
          }
        }
      }
    }
    // now add or remove the specific ones
    foreach ($emailPreferences->_specificGroups as $specificGroup) {
      if (isset($params[$specificGroup['title']]) && $params[$specificGroup['title']] == "1") {
        $adds[] = $specificGroup['group_id'];
      }
      else {
        $removes[] = $specificGroup['group_id'];
      }
    }
    $emailPreferences->processAddsAndRemoves($params['contact_id'], $adds, $removes);
    return $result;
  }

  /**
   * Method to sanitize the params for a set (might be arrays!)
   * @param $params
   */
  private function sanitizeSetParams(&$params) {
    foreach ($params as $paramKey => $paramValue) {
      if (is_array($paramValue) && isset($paramValue[0])) {
        $params[$paramKey] = $paramValue[0];
      }
    }
  }

  /**
   * Method to process the group adds and removes to reflect the email preferences
   *
   * @param $contactId
   * @param $adds
   * @param $removes
   */
  private function processAddsAndRemoves($contactId, $adds, $removes) {
    $contactCurrentGroupIds = [];
    foreach ($adds as $addGroupId) {
      try {
        civicrm_api3('GroupContact', 'create', [
          'group_id' => $addGroupId,
          'contact_id' => $contactId,
          'status' => 'Added',
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    try {
      $apiGroupContact = civicrm_api3('GroupContact', 'get', [
        'sequential' => 1,
        'return' => ["group_id"],
        'contact_id' => $contactId,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiGroupContact['values'] as $apiGroupContact) {
        $contactCurrentGroupIds[] = $apiGroupContact['group_id'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    foreach ($removes as $removeGroupId) {
      if (in_array($removeGroupId, $contactCurrentGroupIds)) {
        try {
          civicrm_api3('GroupContact', 'create', [
            'group_id' => $removeGroupId,
            'contact_id' => $contactId,
            'status' => 'Removed',
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
        }
      }
    }
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
   * Method to get the current email preferences from a dao
   *
   * @param array $params
   * @param array $result
   */
  private function getCurrentPreferences($params, &$result) {
    try {
      $apiGroupContact = civicrm_api3('GroupContact', 'get', [
        'sequential' => 1,
        'return' => ["group_id"],
        'contact_id' => $params['contact_id'],
        'options' => ['limit' => 0],
      ]);
      // by default no general information is set to 1 and all others to 0
      $this->defaultPreferencesForGet($result);
      $newsLetter = FALSE;
      $actionMails = FALSE;
      $yearlyReport = FALSE;
      foreach ($apiGroupContact['values'] as $apiGroupContact) {
        switch ($apiGroupContact['group_id']) {
          // if monthly newsletter: monthly newsletter on and no general, yearly report and monthly actions off
          case $this->_monthlyNewsletterGroup['group_id']:
            $newsLetter = TRUE;
            break;
          // if monthy actions: monthly newsletter and monthly actions on, general and yearly report off
          case $this->_monthlyActionsGroup['group_id']:
            $actionMails = TRUE;
            break;
          // if yearly report: yearly report on and monthly newsletter, monthly actions  and general off
          case $this->_yearlyReportGroup['group_id']:
            $yearlyReport = TRUE;
            break;
          // check if any of the specifics need setting
          default:
            foreach ($this->_specificGroups as $specificGroup) {
              if ($apiGroupContact['group_id'] == $specificGroup['group_id']) {
                $result[$specificGroup['title']] = "1";
              }
            }
        }
      }
      if ($newsLetter && $actionMails) {
        $result[$this->_monthlyNewsletterGroup['title']] = "0";
        $result[$this->_monthlyActionsGroup['title']] = "1";
        $result[$this->_yearlyReportGroup['title']] = "0";
        $result[$this->_noGeneralInfo] = "0";
      }
      elseif ($newsLetter && !$actionMails) {
        $result[$this->_monthlyNewsletterGroup['title']] = "1";
        $result[$this->_monthlyActionsGroup['title']] = "0";
        $result[$this->_yearlyReportGroup['title']] = "0";
        $result[$this->_noGeneralInfo] = "0";
      }
      elseif ($yearlyReport && !$newsLetter && !$actionMails) {
        $result[$this->_monthlyNewsletterGroup['title']] = "0";
        $result[$this->_monthlyActionsGroup['title']] = "0";
        $result[$this->_yearlyReportGroup['title']] = "1";
        $result[$this->_noGeneralInfo] = "0";
      }
      elseif (!$yearlyReport && !$newsLetter && !$actionMails) {
        $result[$this->_monthlyNewsletterGroup['title']] = "0";
        $result[$this->_monthlyActionsGroup['title']] = "0";
        $result[$this->_yearlyReportGroup['title']] = "0";
        $result[$this->_noGeneralInfo] = "1";
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to set the default preferences for get
   *
   * @param $result
   */
  private function defaultPreferencesForGet(&$result) {
    $result[$this->_noGeneralInfo] = "1";
    $result[$this->_monthlyNewsletterGroup['title']] = "0";
    $result[$this->_monthlyActionsGroup['title']] = "0";
    $result[$this->_yearlyReportGroup['title']] = "0";
    foreach ($this->_specificGroups as $specificGroup) {
      $result[$specificGroup['title']] = "0";
    }
  }
}
