<?php

$plugin_info = array(
	'pi_name'			=> 'Membership Expiry',
	'pi_version'		=> '1.1',
	'pi_author'			=> 'Nathan Pitman',
	'pi_author_url'		=> 'http://www.nathanpitman.com/',
	'pi_description'	=> 'Return the expiry date of a paid membership account. Works in tandem with the NP Paid Members extension',
	'pi_usage'			=> np_membership_expiry::usage()
);

class np_membership_expiry {

	var $username = "";
	var $format = "";
	
	function np_membership_expiry()
	{
		global $TMPL, $LOC, $DB;

		// Get username from template
		$username 	= $TMPL->fetch_param('username');
		$format 	= $TMPL->fetch_param('format');
        
		// If username is set
		if ($username != "") {
			$sql = "SELECT exp_members.*, exp_paid_members.* FROM exp_paid_members, exp_members WHERE (exp_members.username='".$DB->escape_str($username)."' AND exp_paid_members.member_id=exp_members.member_id)";
			$DB->fetch_fields = TRUE;
			$query = $DB->query($sql);
			
			// If the username exist in the exp_members table
			// and the corresponding member_id exists in the exp_paid_members table
			if ($query->num_rows == 1) {
			
				// convert date to timestamp
     			$time = $query->row['renew_date'];
     			if ($time) {
      				$this->return_data = $LOC->decode_date($format,$time);
      				return;
      			}
			}
		// Username not set, return an error in place of the status
		} else {
			$this->return_data = "Error: The username parameter is required!";
			return;
		}
		
	}
	

// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.
//  Make sure and use output buffering

function usage()
{
ob_start(); 
?>

BASIC USAGE:

{exp:np_membership_expiry username='nathanpitman' format="%F %j%S, %Y"}

PARAMETERS:

username = 'nathanpitman' (no default - must be specified)
 - The username parameter defines what username you want to return the online status for.
	
return = 'd/m/Y' (default - '%F %j%S, %Y')
 - The return parameter defines how to format the date that is returned.
	
RELEASE NOTES:

1.0 - Initial Release.

For updates and support check the developers website: http://nathanpitman.com


<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}


}
?>