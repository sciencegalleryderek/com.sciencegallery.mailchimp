<?php

class CRM_Mailchimp_Form_Sync extends CRM_Core_Form {

  const QUEUE_NAME = 'mc-sync';

  const END_URL = 'civicrm/admin/mailchimp/sync';

  const END_PARAMS = 'run=true';

  /**
   * Function to return the Form Name.
   *
   * @return None
   * @access public
   */
  public function getTitle() {
    return ts('Mail Chimp Subscriber Sync');
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    // Create the Submit Button.
    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Sync Contacts'),
      ),
    );

    // Add the Buttons.
    $this->addButtons($buttons);

    // Set the Default Field Values.
    // $this->setDefaults($defaults);

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

    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name' => self::QUEUE_NAME,
      'type' => 'Sql',
      'reset' => TRUE,
    ));

    // Set the Number of Rounds to 0
    $rounds = 0;

    // Set the Group IDs to an empty array
    $group_ids = array();

    // Get the Groups
    $groups = mailchimp_variable_get('groups', array());


    // Get the Groups
    $group_map = mailchimp_variable_get('group_map', array());


    // Loop through each Group
    foreach ($groups as $group_id => $sync) {

      // If the Group is Supposed to be synced and a map exists for the group
      if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {
        $group_ids[$group_id] = $group_id;
      }

    }

    // Figure out how many Contacts there are.
    if (!empty($group_ids)) {

      $group_contact = new CRM_Contact_BAO_GroupContact();
      $group_contact->whereAdd('group_id IN ('.implode(',', $group_ids).')');
      $group_contact->whereAdd("status = 'Added'");
      $group_contact->orderBy('id ASC');
      $group_contact->find();
      $count = $group_contact->count();

      $rounds = ceil($count/10);
    }


    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {

      $start = $i * 10;

      $task = new CRM_Queue_Task(
        array (
          'CRM_Mailchimp_Form_Sync',
          'runSync',
        ),
        array(
          $start,
        ),
        'Mail Chimp Sync - Contacts '.($start+10).' of '.$count
      );

      // Add the Task to the Queu
      $queue->createItem($task);
      $i++;
    }

    // Setup the Runner
    $runner = new CRM_Queue_Runner(array(
      'title' => ts('Mail Chimp Sync'),
      'queue' => $queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl' => CRM_Utils_System::url(self::END_URL, self::END_PARAMS),
    ));
    // Run Everything in the Queue via the Web.
    $runner->runAllViaWeb();

  }

  /**
   * Run the From
   *
   * @access public
   *
   * @return TRUE
 */
  public function runSync(CRM_Queue_TaskContext $ctx, $start) {

     // Get the API Key
    $api_key = mailchimp_variable_get('api_key');

    // If the API Key or Client ID are empty
    // return now, for there is nothing else we can do.
    if (empty($api_key)) {
      return CRM_Queue_Task::TASK_FAIL;
    }

    // Get the Groups
    $groups = mailchimp_variable_get('groups', array());

    // Get the Groups
    $group_map = mailchimp_variable_get('group_map', array());

    // Connect to MailChimp
    $mc_client = new Mailchimp($api_key);
    $mc_lists = new Mailchimp_Lists($mc_client);

    // Loop through each Group
    foreach ($groups as $group_id => $sync) {

      // If the Group is Supposed to be synced and a map exists for the group
      if (!empty($groups[$group_id]) && !empty($group_map[$group_id])) {



        // Find the GroupContacts matching this group_id
        $group_contact = new CRM_Contact_BAO_GroupContact();
        $group_contact->whereAdd('group_id = '.$group_id);
        $group_contact->whereAdd("status = 'Added'");
        $group_contact->orderBy('id ASC');
        $group_contact->limit($start, 10);
        $group_contact->find();

        while ($group_contact->fetch()) {

          $contact = new CRM_Contact_BAO_Contact();
          $contact->id = $group_contact->contact_id;
          $contact->find(TRUE);

          $email = new CRM_Core_BAO_Email();
          $email->contact_id = $group_contact->contact_id;
          $email->is_primary = TRUE;
          $email->find(TRUE);

          $history = new CRM_Contact_BAO_SubscriptionHistory();
          $history->group_id = $group_id;
          $history->contact_id = $group_contact->contact_id;
          $history->orderBy('id ASC');
          $history->limit($start, 1);
          $history->find(TRUE);

          $email->contact_id = $group_contact->contact_id;

          if (!empty($email->email)) {

            $resubscribe = TRUE;
            if ($contact->do_not_email ) {
              $resubscribe = FALSE;
            }

	    if($resubscribe && ($contact->is_opt_out == 0) && ($contact->do_not_email) == 0 &&($email->on_hold == 0) ){

			  if (empty($contact->first_name)) $buffer = array( 'EMAIL'=>$email->email, 'groupings'=>array(), 'optin_time'=>$history->date);
			  elseif (empty($contact->last_name)) $buffer = array( 'EMAIL'=>$email->email, 'FNAME'=>$contact->display_name, 'groupings'=>array(), 'optin_time'=>$history->date);
			  else $buffer = array( 'EMAIL'=>$email->email, 'FNAME'=>$contact->first_name, 'LNAME'=>$contact->last_name, 'groupings'=>array(), 'optin_time'=>$history->date);

			  $results = $mc_lists->subscribe( $group_map[$group_id], array('email' => $email->email), $buffer,'html', false, true, false, false);
           }

          }

        }

      }

    }
    return CRM_Queue_Task::TASK_SUCCESS;
  }

}
