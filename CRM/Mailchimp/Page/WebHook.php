<?php
class CRM_Mailchimp_Page_WebHook extends CRM_Core_Page {

  function run() {
	// Get the Groups
	$groups = mailchimp_variable_get('groups', array());

	// Get the Groups
    $group_map = mailchimp_variable_get('group_map', array());

    if(!empty($_POST['data']['list_id']) && !empty($_POST['type'])) {
    	$request_type = $_POST['type'];
    	$request_data = $_POST['data'];

    	//check the  request is from a list we keep in sync
  		foreach($groups as $group_id => $in_group) {

  			if($group_map[$group_id] == $request_data['list_id']) {

				//profile update
				if($request_type == 'profile') {
					//try to find the email address
					$email = new CRM_Core_BAO_Email();
					$email->get('email', $request_data['email']);

					// If the Email was found.
					if (!empty($email->contact_id)) {
						$contact = new CRM_Contact_BAO_Contact();
						$contact->id = $email->contact_id;
          				$contact->find(TRUE);

          				//update the contact's name
          				if(!empty($request_data['merges']['FNAME']) && !empty($request_data['merges']['FNAME']) ) {
          					if(($contact->first_name != $request_data['merges']['FNAME']) || ($contact->last_name != $request_data['merges']['LNAME'])) {
          						$contact->first_name = $request_data['merges']['FNAME'];
          						$contact->last_name = $request_data['merges']['LNAME'];
          						$contact->sort_name = $contact->last_name . ', ' . $contact->first_name;
          						$contact->display_name = $contact->first_name . ' ' . $contact->last_name;
          						$contact->email_greeting_display = 'Dear ' . $contact->first_name;
          						$contact->save();
          					}
          				}
              		}
				}

				//email update
				else if($request_type == 'upemail') {
					//try to find the email address
					$email = new CRM_Core_BAO_Email();
					$email->get('email', $request_data['old_email']);

					// If the Email was found.
					if (!empty($email->contact_id)) {
						$email->email = $request_data['new_email'];
						$email->save();
					}
				}

				//cleaned email
				else if($request_type == 'cleaned') {
					//try to find the email address
					$email = new CRM_Core_BAO_Email();
					$email->get('email', $request_data['email']);

					// If the Email was found.
					if (!empty($email->contact_id)) {
						$email->on_hold = 1;
						$email->holdEmail($email);
					}
				}

				//unsubscribe requests
				else if($request_type == 'unsubscribe') {
					//instead of removing from CiviCRM groups, set the no bulk communications flag, this will prevent smart groups from being messed up
					//try to find the email address
					$email = new CRM_Core_BAO_Email();
					$email->get('email', $request_data['email']);

					// If the Email was found.
					if (!empty($email->contact_id)) {
						$contact = new CRM_Contact_BAO_Contact();
						$contact->id = $email->contact_id;
						$contact->find(TRUE);

						//update the opt out status
						$contact->is_opt_out = 1;
						$contact->save();

					}
				}

   			}
  		}
	}

    // Return the JSON output
    header('Content-type: application/json');
    print json_encode($data);
    CRM_Utils_System::civiExit();

  }

}
