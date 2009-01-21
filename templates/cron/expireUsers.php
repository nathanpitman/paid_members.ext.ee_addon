<?php
	global $DB, $REGX, $PREFS;
	$currentTime = time();
	$query = $DB->query("SELECT * FROM exp_paid_members WHERE state!='expired'");

	foreach ($query->result as $row) {
	
		// Get the diff between today and the expiry date
		$difference = ($row['renew_date']-$currentTime);
		
		// Pending
		if (($difference <= 864000) && ($difference >= 1) && ($row['state']!="pending")) {

			$data = array('state'=>'pending');
			$sql = $DB->update_string('exp_paid_members', $data, "member_id = ".$row['member_id']);
			$update = $DB->query($sql);
			
			if ( ! class_exists('EEmail')) {
			    require PATH_CORE.'core.email'.EXT;
			}
			
			// Get the member email address
			$sql_member = "SELECT email FROM exp_members WHERE member_id='".$row['member_id']."'";
			$sql_query = $DB->query($sql_member);
			
			// Assemble the email
			$receipient = $sql_query->row['email'];
			$bcc_emails = $PREFS->core_ini['webmaster_email'];
			$email_subject = "Your Be Fabulous subscription expires in 10 days";
			$email_msg = "Hello\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."This is just a brief email to let you know that your Be Fabulous 12 month subscription expires in 10 days.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."To renew your subscription in advance of it's expiry visit http://www.be-fabulous.co.uk and log-in to your account. You will see an option on the right hand side of the 'All about you' page to 'Extend your Subscription'.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."If you need any help or have any questions please don't hesitate to contact us by replying to this email or calling Karen on +44 (0) 7970 732057.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg." - End of Message - \n";
			
			// Send the Email
			$email = new EEmail;
			$email->wordwrap = false;
			$email->mailtype = 'text';
			$email->validate = true;
			$email->from($PREFS->core_ini['webmaster_email'], $PREFS->core_ini['webmaster_name']);
			$email->to($receipient);
			$email->bcc($bcc_emails);
			$email->subject($email_subject);
			$email->message($REGX->entities_to_ascii($email_msg));
			$email->Send();
			$email->initialize();

		// Expire
		} else if ($difference <= 0) {

			// Set the state in exp_paid_members to expired to prevent further notices
			$data = array(
				'state'		=> 'expired'
			);
			$sql = $DB->update_string('exp_paid_members', $data, "member_id = ".$row['member_id']);
			$update = $DB->query($sql);
			
			// Revert back to the standard member group
			$data = array(
				'group_id'		=> '5'
			);
			$sql = $DB->update_string('exp_members', $data, "member_id = ".$row['member_id']);
			$update = $DB->query($sql);

		
			if ( ! class_exists('EEmail')) {
			    require PATH_CORE.'core.email'.EXT;
			}
			
			// Get the member email address
			$sql_member = "SELECT email FROM exp_members WHERE member_id='".$row['member_id']."'";
			$sql_query = $DB->query($sql_member);
			
			// Assemble the email
			$receipient = $sql_query->row['email'];
			$bcc_emails = $PREFS->core_ini['webmaster_email'];
			$email_subject = "Your Be Fabulous subscription has expired";
			$email_msg = "Hello\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."This is just a brief email to let you know that your Be Fabulous 12 month subscription has expired.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."To re-subscribe simply visit http://www.be-fabulous.co.uk and log-in to your account. You will see an option on the right hand side of the 'All about you' page to 'Upgrade your subscription'.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg."If you need any help or have any questions please don't hesitate to contact us by replying to this email or calling Karen on +44 (0) 7970 732057.\n";
			$email_msg = $email_msg."\n";
			$email_msg = $email_msg." - End of Message - \n";
			
			// Send the Email
			$email = new EEmail;
			$email->wordwrap = false;
			$email->mailtype = 'text';
			$email->validate = true;
			$email->from($PREFS->core_ini['webmaster_email'], $PREFS->core_ini['webmaster_name']);
			$email->to($receipient);
			$email->bcc($bcc_emails);
			$email->subject($email_subject);
			$email->message($REGX->entities_to_ascii($email_msg));
			$email->Send();
			$email->initialize();

		}
	}
	
	
?>