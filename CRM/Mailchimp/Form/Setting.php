<?php

class CRM_Mailchimp_Form_Setting extends CRM_Core_Form {

  /**
   * Function to return the Form Name.
   *
   * @return None
   * @access public
   */
  public function getTitle() {
    return ts('MailChimp Settings');
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    // Add the API Key Element
    $this->addElement('text', 'api_key', ts('API Key'), array(
      'size' => 48,
    ));

    $defaults['api_key'] = CRM_Core_BAO_Setting::getItem('MailChimp Preferences',
      'api_key', NULL, FALSE
    );

    // Retrieve the CiviCRM Groups
    $groups = CRM_Contact_BAO_Group::getGroups();

    // Create a Checkbox for each Group
    $checkboxes = array();
    foreach ($groups as $group) {
      $checkboxes[] = &HTML_QuickForm::createElement('checkbox', $group->id, $group->title);
    }

    // Add the Group of Checkboxes
    $this->addGroup($checkboxes, 'groups', ts('Groups'));

    // Get the Currently Selected Groups
    $current = CRM_Core_BAO_Setting::getItem('MailChimp Preferences',
      'groups', NULL, FALSE
    );

    // Set the Currently Selected Groups as the Default.
    // If non is availble, the default will be unchecked.
    if (!empty($current)) {
      foreach ($current as $key => $value) {
        $defaults['groups'][$key] = $value;
      }
    }

    // Create the Submit Button.
    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
      ),
    );

    // Add the Buttons.
    $this->addButtons($buttons);

    // Set the Default Field Values.
    $this->setDefaults($defaults);

  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {

    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);

    // Save the API Key.
    if (CRM_Utils_Array::value('api_key', $params)) {
      CRM_Core_BAO_Setting::setItem($params['api_key'],
        'MailChimp Preferences',
        'api_key'
      );
    }

    // Save the Client ID.
    if (CRM_Utils_Array::value('client_id', $params)) {
      CRM_Core_BAO_Setting::setItem($params['client_id'],
        'MailChimp Preferences',
        'client_id'
      );
    }

    // Save the selected groups.
    if (CRM_Utils_Array::value('groups', $params)) {
      CRM_Core_BAO_Setting::setItem($params['groups'],
        'MailChimp Preferences',
        'groups'
      );
    }

    // If all the necessary data is availible,
    // map the CiviCRM Groups to Campaign Monitor Lists.
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('groups', $params)) {
      $this->mapGroups($params);
    }

    //update the subscription history so we don't have to load the whole history
	$history = new CRM_Contact_BAO_SubscriptionHistory();
	$history->orderby('id DESC');
	$history->limit(0,1);
	$history->find();

	while($history->fetch()){
		setHistoryID($history->id);
	}

  }

  /**
   * Function to Map CiviCRM Groups to MailChimp Lists.
   *
   * @access private
   *
   * @return None
   */
  private function mapGroups($params = array()) {

    // Get the Current Group Map
    $group_map = CRM_Core_BAO_Setting::getItem('MailChimp Preferences',
      'group_map', NULL, FALSE
    );

    // Ensure that the value is not NULL
    $group_map = !empty($group_map) ? $group_map : array();

    // Retrieve the CiviCRM Groups.
    $groups = CRM_Contact_BAO_Group::getGroups();

    // Put all of the Group ID's into an array
    $group_ids = array();
    foreach ($groups as $group) {
      $group_ids[$group->id] = $group->id;
    }

    // If any Groups have been removed, remove the map.
    foreach ($group_map as $key => $value) {
      if (!in_array($key, $group_ids)) {
        unset($group_map[$key]);
      }
    }

    // Connect to Mail Chimp
    $mc_client = new Mailchimp($params['api_key']);
    $mc_lists = new Mailchimp_Lists($mc_client);

    // Get the Lists from Mail Chimp
    $result = $mc_lists->getlist();
    $lists = $result['data'];

    // Put all of the List ID's into an array
    $list_ids = array();
    foreach ($lists as $list) {
      $list_ids[$list['name']] = $list['id'];
    }

	//clear the mapping list
	$group_map = array();

    // For each Group, make sure it is mapped
    foreach ($groups as $group) {

      // Only Map Groups that are upposed to be synced.
      if (empty($params['groups'][$group->id])) {
        continue;
      }

      if(empty($list_ids[$group->title])){
        CRM_Core_Session::setStatus('MailChimp List <strong>' . $group->title . '</strong> does not exist, please create in MailChimp', 'Configuration error', 'error');
      }
      else{
      	//add to group mapping
     	$group_map[$group->id] = $list_ids[$group->title];

		//check if there's a webhook already in place

		$url = CRM_Utils_System::url('civicrm/mailchimp/webhook', NULL, TRUE);
		$setWebhook = true;

		$results = $mc_lists->webhooks($list_ids[$group->title]);
		if(count($results) != 0){
			foreach($results as $webhook) {
				if($webhook['url'] == $url) {
					$setWebhook = false;
				}
			}
		}

		//create webhook
	    if($setWebhook) {
	      $actions = array(
	     		'subscribe'=>false,
	     		'unsubscribe'=>true,
	     		'profile'=>true,
	     		'cleaned'=>true,
	     		'upemail'=>true,
	     		'campaign'=>true,
	     		);

          $sources = array(
	     		'user'=>true,
	     		'admin'=>true,
	     		'api'=>false,
	     		);


       	  $mc_lists->webhookAdd($list_ids[$group->title],$url, $actions, $sources);
       	 }
      }

    }


    // Save the new Group Map
    CRM_Core_BAO_Setting::setItem($group_map,
      'MailChimp Preferences',
      'group_map'
    );

    CRM_Core_Session::setStatus('Settings saved, ' . count($group_map) . ' Group to List mappings saved');

  }

}
