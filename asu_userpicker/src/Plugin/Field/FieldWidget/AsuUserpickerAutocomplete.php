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

    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    // Append the match operation to the selection settings.
//
    $selection_settings = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];

    //error_log(var_export(array_keys($form_state->getStorage()['field_storage']['#parents']['#fields']), 1));
    //error_log(var_export($form_state->getValue(), 1));
    //error_log(var_export($form_state->get(), 1));
    //$entity_def = $items->getFieldDefinition();
    //error_log(var_export($entity_def, 1));
    /*
        $element += [
          '#type' => 'entity_autocomplete',
          //'#target_type' => $this->getFieldSetting('target_type'),
          '#target_type' => 'user',
          '#selection_handler' => $this->getFieldSetting('handler'),
          '#selection_settings' => $selection_settings,
          // Entity reference field items are handling validation themselves via
          // the 'ValidReference' constraint.
          '#validate_reference' => FALSE,
          '#maxlength' => 1024,
          '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
          //'#size' => $this->getSetting('size'),
          //'#placeholder' => $this->getSetting('placeholder'),
          //'#element_validate' => [[get_called_class(), 'validateUriElement']],

          '#autocomplete_route_name' => 'asu_userpicker.autocomplete',
          // @todo add setting for count and wire in here.
          '#autocomplete_route_parameters' => array('search_name' => 'name', 'count' => 10),
        ];
        // ref: https://api.drupal.org/api/drupal/core%21modules%21link%21src%21Plugin%21Field%21FieldWidget%21LinkWidget.php/function/LinkWidget%3A%3AformElement/8.2.x
        // #
    */
    $element += [
      // @todo if we use entity_autocomplete, we can't use #autocomplete_route_* parmeters... and we need those. Alternative?
      '#type' => 'textfield',
      // @todo works on node form, breaks settings page for field...
//      '#type' => 'entity_autocomplete', // @todo doesn't work on node form, works on settings page

      // @todo add setting for placeholder and wire in here.
      '#placeholder' => $this->t('Name or ASURITE'),
      '#autocomplete_route_name' => 'asu_userpicker.autocomplete',
      // @todo add setting for count and wire in here.
      '#autocomplete_route_parameters' => array(
        'search_name' => 'name',
        'count' => 10
      ),
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      //'#autocreate' => FALSE,
      //'#autocreate[bundle]' => 'user',

      '#target_type' => 'user',
      // ??? use this instead of controller ??? '#selection_handler' => $this->getFieldSetting('handler'),
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint. @todo verify
      '#validate_reference' => FALSE,

      // Don't process default value...
      '#process_default_value' => FALSE,

      //'#element_validate' => [ [$this, 'validate'] ],

      //'#selection_settings' => $selection_settings,
      //'#maxlength' => 1024,
      // @todo add setting for size?
      //'#size' => $this->getSetting('size'),
      // @todo add setting for placeholder... something about asurite id?
      //'#placeholder' => $this->getSetting('placeholder'),
    ];

    if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
      $element['#autocreate'] = [
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()
          ->id()
      ];
    }

    return ['target_id' => $element];


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
   * Need this in order to avoid error on field settings page:
   *   "This value should be of the correct primitive type" @todo didn't work... however we may be able to use this for doing our own autocreate if needed...
   *
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      //$values[$key] = '';
    }
    return $values;
  }

}
