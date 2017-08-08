# asu-drupal-modules

## List of modules

* asu_ajax_solr - Adds AJAX Solr library.
* asu_ap - (Feature) Pulls degree programs from webapp4 (utilizing the Degree Search API) via Feeds;
  includes necessary modules ASU Feeds 2 (asu_feeds2) and ASU IXR (asu_ixr), which were previously not bundled;
  includes theming for nodes and views.
* asu_brand - Contains ASU Brand header and footer blocks for usage in Drupal;
* asu_cas - (Feature) CAS settings for integrating ASU SSO and CAS into Drupal.
* asu_dir - AJAX-powered, rudimentary ASU directory; Pulls data from a Solr instance (which gets its data from iSearch).
* asu_rfi - Extends asu_degrees to include Request for Info (RFI) forms, which collect and transmit data to Salesforce
  via middleware.
* asu_userpicker - Provides a custom user field widget for picking users in Drupal and ASU LDAP, and creating those not
  yet in Drupal.
