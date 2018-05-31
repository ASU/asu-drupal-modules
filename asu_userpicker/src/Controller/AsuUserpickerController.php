<?php /**
 * @file
 * Contains \Drupal\asu_userpicker\Controller\DefaultController.
 */

namespace Drupal\asu_userpicker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Default controller for the asu_userpicker module.
 */
class AsuUserpickerController extends ControllerBase {

  public function handleAutocomplete(Request $request, $search_name, $count) {
    $results = [];

    // Implementation based on https://www.qed42.com/blog/autocomplete-drupal-8
    // Not sure this is the best way to go, due to how $search_name is handled,
    // but haven't hit upon a better, more correct way to go here yet.

    // Get the search string from the URL, if it exists.
    if ($input = $request->query->get('q')) {

      if (empty($input)) { return; }

      $typed_string = Tags::explode($input);
      $search_string = Unicode::strtolower(array_pop($typed_string));
      // Swap in the search string over the generic value from the element
      // definition passed from  AsuUserpickerAutocomplete.php.
      $search_name = $search_string;

      // @todo use built-in autocreate? if so, add settings note to README
      // @todo how to populate user for CAS login?  Is there a hook in for cas create? or to assoc the user at cas login?
      // @todo remove validate function in .module not needed, right?

      // 1. Check existing users for asurite matches.
      // 2. Check Solr for asurite matches.
      // 3. Remove dupes and display.

      // LOCAL SEARCH

      $local_results = asu_userpicker_search_local($search_string);

      foreach ($local_results as $local_result) {
        // This is the cas_name/asurite ID.
        // We need to save on drupal users names.
        // We just force all usernames to be the same as ASURITE IDs.
        $results[] = [
          'value' => $local_result->name,
          'label' => $this->t('Local user : ') . \Drupal\Component\Utility\Html::escape($local_result->name . ' : <' . \Drupal\Component\Utility\Html::escape($local_result->mail) . '>')
        ];
      }

      // SOLR SEARCH

      // @todo fix it so we use $count

      // Query Solr on ASURITE ID and common name / full name.
      $search_results = asu_userpicker_search_solr($search_name, $filters = [
        'asuriteid',
        'cn',
      ]);

      // Add users to our list
      foreach ($search_results['response']['docs'] as $solr_row) {
        if ($solr_row['asuriteId']) {
          $results[] = [
            'value' => $solr_row['asuriteId'],
            'label' => $solr_row['asuriteId'] . ' : ' . (isset($solr_row['displayName']) ? $solr_row['displayName'] : '-no name-') . ' [' . (isset($solr_row['emailAddress']) ? $solr_row['emailAddress'] : '-no email-') . ']',
          ];
        }
      }

    }

    return new JsonResponse($results);

  }

}
