com.sciencegallery.mailchimp
============================

CiviCRM Mailchimp list integration

This CiviCRM extension provides two way synchronisation between CiviCRM groups and MailChimp Lists.

Changes to users in regular groups are synced with MailChimp in real-time, smart groups use CiviCRM's
Scheduled Jobs to sync up.

Changes made on MailChimp are sync'd back to CiviCRM using Webhooks

INSTALLATION
------------
1. Create lists on MailChimp with the same names as the groups you want to sync on CiviCRM
2. Install and enable the extenstion
3. Go to http://<yourdomain>/civicrm/admin/setting/mailchimp and enter your MailChimp API key and select your list
4. Run an initial sync at http://<yourdomain>/civicrm/admin/mailchimp/sync

Due to the way the MailChimp API works, you can't create lists on the fly, there's also a bit of a delay with API calls
being reflected on the MailChimp web interface

