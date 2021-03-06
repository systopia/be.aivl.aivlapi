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

require_once 'aivlapi.civix.php';
use CRM_Aivlapi_ExtensionUtil as E;

/**
 * Implements hook_civicrm_apiWrappers for filtering
 * @see https://civicoop.plan.io/issues/2906
 */
function aivlapi_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  if (!empty($apiRequest['params']['drop_questionmarks'])) {
    $wrappers[] = new CRM_Aivlapi_ParameterSanitation();
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function aivlapi_civicrm_config(&$config) {
  _aivlapi_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function aivlapi_civicrm_xmlMenu(&$files) {
  _aivlapi_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function aivlapi_civicrm_install() {
  _aivlapi_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function aivlapi_civicrm_postInstall() {
  _aivlapi_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function aivlapi_civicrm_uninstall() {
  _aivlapi_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function aivlapi_civicrm_enable() {
  _aivlapi_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function aivlapi_civicrm_disable() {
  _aivlapi_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function aivlapi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _aivlapi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function aivlapi_civicrm_managed(&$entities) {
  _aivlapi_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function aivlapi_civicrm_caseTypes(&$caseTypes) {
  _aivlapi_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function aivlapi_civicrm_angularModules(&$angularModules) {
  _aivlapi_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function aivlapi_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _aivlapi_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function aivlapi_civicrm_entityTypes(&$entityTypes) {
  _aivlapi_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Define custom (Drupal) permissions
 */
function aivlapi_civicrm_permission(&$permissions) {
  $permissions['access AIVL API'] = 'AIVL-API: access AIVL API';
}


/**
 * Set permissions for runner/engine API call
 */
function aivlapi_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // new AivlAPI calls: simply use 'access AIVL API' permission
  $permissions['aivl_event']['register']            = array('access AIVL API');
  $permissions['aivl_petition']['sign']             = array('access AIVL API');
  $permissions['aivl_mail']['optout']               = array('access AIVL API');
  $permissions['aivl_membership']['feedback']       = array('access AIVL API');
  $permissions['aivl_selfservice']['contactdata']   = array('access AIVL API');
  $permissions['aivl_selfservice']['contactbyhash'] = array('access AIVL API');
  // AIVL Basic Signup form permissions
  $permissions['aivl_basic']['signup'] = array('access AIVL API');
  // Permission for AIVL Email Preferences
  $permissions['aivl_email_preferences']['get'] = array('access AIVL API');
  $permissions['aivl_email_preferences']['set'] = array('access AIVL API');

  // open these calls up to "OR 'access AIVL API'":
  $permissions['event']['get'] = array($permissions['event']['get'], 'access AIVL API');
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function aivlapi_civicrm_navigationMenu(&$menu) {
  _aivlapi_civix_insert_navigation_menu($menu, 'Administer/System Settings', array(
      'label'      => E::ts('AIVL API Configuration'),
      'name'       => 'aivlapi_configuration',
      'url'        => 'civicrm/admin/setting/aivlapi',
      'permission' => 'administer CiviCRM',
      'operator'   => 'OR',
      'separator'  => 0,
  ));
  _aivlapi_civix_navigationMenu($menu);
}
