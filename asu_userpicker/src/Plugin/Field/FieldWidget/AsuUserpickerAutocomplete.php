<?php
/**
 * @file
 * Contains \Drupal\asu_userpicker\Plugin\Field\FieldWidget\AsuUserpickerAutocomplete.
 */

namespace Drupal\asu_userpicker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
//use Drupal\views\Views;

/**
 * Plugin implementation of the "asu_userpicker_autocomplete" widget.
 *
 * @FieldWidget(
 *   id = "asu_userpicker_autocomplete",
 *   label = @Translation("ASU User Picker Autocomplete"),
 *   description = @Translation("Autocomplete userpicker that searches current Drupal site as well as ASU Solr"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "target_type" = "user"
 *   },
 *   multiple_values = FALSE
 * )
 */
class AsuUserpickerAutocomplete extends EntityReferenceAutocompleteWidget {

  /**
   * @FIXME
   * Move all logic relating to the asu_userpicker_autocomplete widget into this class.
   * For more information, see:
   *
   * https://www.drupal.org/node/1796000
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetInterface.php/interface/WidgetInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetBase.php/class/WidgetBase/8
   */

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // @todo this line brings in the parent method... but we've copied in and
    // are altering that code below.
    //$element = parent::formElement($items, $delta, $element, $form, $form_state);

    // ref: https://api.drupal.org/api/drupal/core%21modules%21link%21src%21Plugin%21Field%21FieldWidget%21LinkWidget.php/function/LinkWidget%3A%3AformElement/8.2.x

    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];

    $element += [
      // Using the textfield type, overriding the entity_autocomplete type, so
      // we can tap into the #autocomplete_route_* parameters. Just requires
      // some tweakery in massageFormValues().
      '#type' => 'textfield',
      // @todo add setting for placeholder and wire in here.
      '#placeholder' => $this->t('Name or ASURITE'),
      //'#placeholder' => $this->getSetting('placeholder'),
      '#autocomplete_route_name' => 'asu_userpicker.autocomplete',
      // @todo add setting for count and wire in here.
      '#autocomplete_route_parameters' => array(
        'search_name' => 'name',
        'count' => 10
      ),
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta]->get('name')->value : NULL,
      // Don't process default value...
      //'#process_default_value' => FALSE,

      // Autocreate on "user doesn't work, by design:
      // https://www.drupal.org/project/drupal/issues/2700411
      // @todo do we want to keep track of this? Force it to autocomplete? How to make the UI clear?
      //'#autocreate' => FALSE,
      //'#autocreate[bundle]' => 'user',

      '#target_type' => $this->getFieldSetting('target_type'), // 'user'
      // @todo alt approach would be to do custom selection handler...
      // @todo Our logic ignores these settings, currently.
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,

      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint. @todo verify
      '#validate_reference' => FALSE,
      //'#element_validate' => [ [$this, 'validate'] ],

      //'#selection_settings' => $selection_settings,
      '#maxlength' => 1024,
      // @todo add setting for size?
      //'#size' => $this->getSetting('size'),
      // @todo add setting for placeholder... something about asurite id?
    ];

    // @todo What to make of this? Can probably yank.
    if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
      $element['#autocreate'] = [
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()
          ->id()
      ];
    }

    return ['target_id' => $element];

    // @todo remove, when determined we're not tapping into standard autocreate
    // routines here. Yank when the conditional above goes.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    // Pull in from global configs in AsuUserpickerAdminSettings.php.
    return [
      // Create a default setting 'size', and
      // assign a default value of 60
      'asu_userpicker_solr_query_url' => \Drupal::config('asu_userpicker.settings')
        ->get('asu_userpicker_solr_query_url'),
      // Taking the Views selector out. Currently not supporting that.
      //'asu_userpicker_referenceables_view' => \Drupal::config('asu_userpicker.settings')->get('asu_userpicker_referenceables_view'),
      'asu_userpicker_label' => \Drupal::config('asu_userpicker.settings')
        ->get('asu_userpicker_label'),
      'asu_userpicker_referenceable_roles' => \Drupal::config('asu_userpicker.settings')
        ->get('asu_userpicker_referenceable_roles'),
      'asu_userpicker_referenceable_status' => \Drupal::config('asu_userpicker.settings')
        ->get('asu_userpicker_referenceable_status'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['asu_userpicker_solr_query_url'] = [
      '#type' => 'textfield',
      '#title' => t('Solr Search URL'),
      '#default_value' => $this->getSetting('asu_userpicker_solr_query_url'),
      '#required' => TRUE,
    ];

    // @todo Filter for user based views?
    //$views_options = [0 => $this->t('None')] + Views::getViewsAsOptions($views_only = TRUE, $filter = 'all', $exclude_view = NULL, $optgroup = FALSE, $sort = FALSE);
    //$element['asu_userpicker_referenceables_view'] = [
    //  '#type' => 'select',
    //  '#title' => t('Search users using a view'),
    //  '#default_value' => $this->getSetting('asu_userpicker_referenceables_view'),
    //  '#options' => $views_options,
    //  '#required' => FALSE,
    //  '#description' => $this->t('Must be a user-based view.'),
    //];

    $role_objects = Role::loadMultiple();
    $system_roles = array_combine(array_keys($role_objects), array_map(function ($a) {
      return $a->label();
    }, $role_objects));
    unset($system_roles['anonymous']);
    $element['asu_userpicker_referenceable_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Search local users with the following roles'),
      '#default_value' => $this->getSetting('asu_userpicker_referenceable_roles'),
      '#options' => $system_roles,
      '#required' => FALSE,
    ];

    $status_options = ['active' => 'Active', 'blocked' => 'Blocked'];
    $element['asu_userpicker_referenceable_status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Search local users with the following statuses'),
      '#default_value' => $this->getSetting('asu_userpicker_referenceable_status'),
      '#options' => $status_options,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Solr Url: @url', array('@url' => $this->getSetting('asu_userpicker_solr_query_url')));
    //$summary[] = t('View: @view', array('@view' => $this->getSetting('asu_userpicker_referenceables_view')));
    $summary[] = t('Referenceable roles: @roles', array('@roles' => implode(',', array_filter($this->getSetting('asu_userpicker_referenceable_roles')))));
    $summary[] = t('Referenceable status: @statuses', array('@statuses' => implode(', ', array_filter($this->getSetting('asu_userpicker_referenceable_status')))));

    return $summary;
  }


  /**
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    /*
     * Use massageFormValues to accomplish three jobs:
     *   1. Massage form values to avoid "primitive type" error on field settings
     *      page.
     *   2. Massage form values to convert a user name string into a target_id
     *      UID.
     *   3. In the case where the user name doesn't exists locally but does in
     *      Solr, work with cas.user_manager service to create the user, then map
     *      in target_id UID value.
     *
     */

    foreach ($values as $key => $value) {

      // Need this in order to avoid error on field settings page:
      // "This value should be of the correct primitive type."
      if ($value['target_id'] == '') {
        $values[$key] = NULL;
      }

      $target_id = NULL;

      $input_string = $value['target_id']; // It's not a target_id at current.

      // Check for user already in Drupal.
      $acct = user_load_by_name($input_string);
      if ($acct) {
        // Lineup our target_id for reference.
        $target_id = $acct->id();
        $values[$key] = $target_id;
        continue;
      }

      // Check CAS user record from External Auth that wasn't in Drupal users.
      // @todo Needed?
      // If external auth users don't get created in Drupal, we'll want to look
      // up this way, too.
      $cas_user_manager = \Drupal::service('cas.user_manager');
      $cas_uid = $cas_user_manager->getUidForCasUsername($input_string);
      if ($cas_uid) {
        // Set target_id for reference.
        $values[$key] = $cas_uid;
        continue;
      }

      // Check if user exists in Solr.
      $solr_acct = asu_userpicker_get_solr_profile_record($input_string);
      if ($solr_acct) {

        // Create user via cas.user_manager service.
        $cas_user_manager = \Drupal::service('cas.user_manager');
        $property_values['mail'] = $solr_acct['emailAddress'];
        // @todo Include other field mappings when we add those into UI.
        // pass and name are handled for us by the manager.
        $new_acct = $cas_user_manager->register($input_string, $property_values);

        // Lineup new user's target_id for the reference.
        $values[$key] = $new_acct->id();
      }

    }
    return $values;
  }

}
