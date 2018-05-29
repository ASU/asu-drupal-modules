DESCRIPTION
--------------------------
Provides a custom user field widget for picking users in Drupal and ASU Solr, 
and creating those not yet in Drupal.

INSTALLATION
--------------------------
Install the module as usual, see http://drupal.org/node/70151 for further
information.

Configuration
--------------------------
The ASU Userpicker module creates an AJAX autoocomplete userpicker widget
and is easily assignable using the Fields managment UI in Drupal.

Using the ASU Userpicker with fields requires custom code to map it into place
for field other than Fields API user_reference fields provided via the
user_reference module (optional):

Implement a form_alter() to change an existing userpicker field's
'#autocomplete_path' to 'autocomplete/asu/user' and the '#element_validate'
field to 'asu_userpicker_autocomplete_validate'.

Like so:
$form['my_picker_field']['#autocomplete_path'] = 'autocomplete/asu/user';
$form['my_picker_field']['#element_validate'] = 'asu_userpicker_autocomplete_validate';

Additional configurations for the module can be made using the devel/php tool,
as there is no admin UI for these ASU Userpicker variables:

variable_set('asu_userpicker_ldap_server', 'your_server_machinename');
variable_set('asu_userpicker_referenceable_roles', 
    serialize(array(
      2 => '2', // Authenticated user
      3 => '0',
    )));
variable_set('asu_userpicker_referenceable_status', 
    serialize(array(
      1 => '1', // Active users
      0 => '0',
    )));
variable_set('asu_userpicker_referenceables_view', 'your_view_name');
variable_set('asu_userpicker_label', 'USER');
variable_set('asu_userpicker_search_user_fields', array('field_first_name', 'field_last_name', 'another_field_machine_name'));

PERMISSIONS
--------------------------

PAGES
--------------------------

TABLES
--------------------------

BLOCKS
--------------------------
None.

HOOKS
--------------------------
See the API file...  [ TODO ]

CREDITS
--------------------------
ASU Userpicker was created by Michael Samuelson <mlsamuel@asu.edu> /
<mlsamuelson@gmail.com> (http://mlsamuelson.com).


