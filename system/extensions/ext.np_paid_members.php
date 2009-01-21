<?php

if ( ! defined('EXT') )
{
	exit('Invalid file request.  Sorry');
}

/*
=============================================================
	Paid Member Group Expiry
	- Nathan Pitman, www.nathanpitman.com
	- Greg Aker, www.gregaker.net
-------------------------------------------------------------
	Copyright (c) 2008 Nathan Pitman & Greg Aker
=============================================================
	File:			ext.np_paid_members.php
=============================================================
	Version:		1.0.0 // Edited 121208
-------------------------------------------------------------
	Compatibility:	EE 1.6.x
-------------------------------------------------------------
	Purpose:		Adds paid members to a table to track 
					renewal dates such that they can be
					automatically reverted back to non paid
					status via a CRON script. Also facilitates
					renewal of existing subscriptions.
-------------------------------------------------------------
	History:		This extension is based for the most part
					on the good work of Greg Aker and has been
					modified, extended and re-released with
					his blessing.					
=============================================================
*/

class Np_paid_members
{
	var $settings		=	array();
	var $name			=	'Paid Member Group Expiry';
	var $version		=	'1.0.0';
	var $description	=	'Extension for the Simple Commerce Module to handle paid members and membership expiry.';
	var $settings_exist	=	'y';
	var $docs_url		=	'http://www.nathanpitman.com';
	
	// ----------------------------------------
	//	Constructor - Extension use for settings
	// ----------------------------------------	
	
	function Np_paid_members($settings='')
	{
		$this->settings = $settings;
	}
	
	// ----------------------------------------
	//	add_paid_members - Called by Extension Hook
	// ----------------------------------------	
	
	function add_paid_members()
	{
		global $DB, $SESS;
		
		// Check we have the required settings
		if ($this->settings['subscription'] == '') {
			return(false);
		} else {
			
			// Continue
			$memberId = $_POST['custom']; // member id passed from SCM
			$itemID = $_POST['item_number']; // SC item id passed from SCM
			
			$ifModifyGroup = $DB->query("SELECT new_member_group FROM exp_simple_commerce_items WHERE item_id='".$itemID."'");
			
			// Is there a matching item in Simple Commerce table
			if ($ifModifyGroup->num_rows >= 1)
			{
			
				// Does the matching item have 'new_member_group' on purchase enabled?
				if ($ifModifyGroup->row['new_member_group'] != '' && $ifModifyGroup->row['new_member_group'] != 0)
				{
					// Get todays time string and add the subscription time period
					$today = time();
					$renew = $today+$this->settings['subscription'];
				
					// Now check if the current member is already a paid member
					$isMember = $DB->query("SELECT * FROM exp_paid_members WHERE member_id='".$memberId."'");
					
					if ( $isMember->num_rows == 0 ) // If they are a new paid member
					{
						$paidUsersInfo = array(
											'id'			=> '',
											'member_id'		=> $memberId,
											'item_id'		=> $itemID,
											'join_date'		=> $today,
											'renew_date'	=> $renew,
											'state'			=> 'active'
						);
							
						$DB->query($DB->insert_string('exp_paid_members', $paidUsersInfo)); // Adds new users to the exp_paid_members db
						
					} else {  // If they are renewing their subscription
						
						$renew = ($isMember->row['renew_date']+$this->settings['subscription']);
						$returningMember = array(
											'renew_date' => $renew,
											'state'		=> 'active'
						);
						
						$sql = $DB->update_string('exp_paid_members', $returningMember, "member_id = $memberId");
						$DB->query($sql);
						
					}
				}
			}
		}
	}
	
	
	// For this function to work you will need to manually add a hook to cp.myaccount.php that will be called when an admin updates a users member group
	// See this URL - http://expressionengine.com/forums/viewthread/102037/
	
	function update_paid_members()
	{
		global $DB;
		
		// available vars from $_POST
		$memberId = $_POST['id'];
		$groupId = $_POST['group_id'];
		
		// Set a default item id for manual upgrades
		$itemID = 0;
		
		// Manual upgrade, add the member to the exp_paid_members table
		if ($groupId == 7)
		{
			// Get todays time string and add the subscription time period
			$today = time();
			$renew = $today+$this->settings['subscription'];
		
			// Now check if the current member is already a paid member
			$isMember = $DB->query("SELECT * FROM exp_paid_members WHERE member_id='".$memberId."'");
			
			if ( $isMember->num_rows == 0 ) // If they are being upgraded to premium membership
			{
				$paidUsersInfo = array(
									'id'			=> '',
									'member_id'		=> $memberId,
									'item_id'		=> $itemID,
									'join_date'		=> $today,
									'renew_date'	=> $renew,
									'state'			=> 'active'
				);
					
				$DB->query($DB->insert_string('exp_paid_members', $paidUsersInfo)); // Adds new users to the exp_paid_members db
				
			}
		} elseif ($groupId == 5) {
			// User returned to standard member group, check if they have a premium membership, if so then remove it.
			$isMember = $DB->query("SELECT * FROM exp_paid_members WHERE member_id='".$memberId."'");
			
			if ( $isMember->num_rows == 1 ) // If they are being downgraded
			{
				$query = $DB->query("DELETE FROM exp_paid_members WHERE member_id='".$memberId."'"); 
			}
		}   

	}
	
	function delete_paid_members()
	{
		global $DB;
		
		// Get all the posted member IDs which we need to check for and delete from exp_paid_members
		
		$ids = array();
        $mids = array();
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val) AND $val != '')
            {
                $ids[] = "member_id = '".$DB->escape_str($val)."'";
                $mids[] = $DB->escape_str($val);
            }        
        }
        
        $IDS = implode(" OR ", $ids);
		$query = $DB->query("DELETE FROM exp_paid_members WHERE ".$IDS);        

	}
	
	// ----------------------------------------
	//	Activate Extension
	// ----------------------------------------	
	
	function activate_extension()
	{
	    global $DB;

	    $DB->query($DB->insert_string('exp_extensions',
	                                  array(
	                                        'extension_id' => '',
	                                        'class'        => "Np_paid_members",
	                                        'method'       => "add_paid_members",
	                                        'hook'         => "simple_commerce_perform_actions_end",
	                                        'settings'     => '',
	                                        'priority'     => 10,
	                                        'version'      => $this->version,
	                                        'enabled'      => "n"
	                                      )
	                                 )
	              );
	              
	    $DB->query($DB->insert_string('exp_extensions',
	                                  array(
	                                        'extension_id' => '',
	                                        'class'        => "Np_paid_members",
	                                        'method'       => "update_paid_members",
	                                        'hook'         => "admin_update_options",
	                                        'settings'     => '',
	                                        'priority'     => 10,
	                                        'version'      => $this->version,
	                                        'enabled'      => "n"
	                                      )
	                                 )
	              );
	              
	    $DB->query($DB->insert_string('exp_extensions',
	                                  array(
	                                        'extension_id' => '',
	                                        'class'        => "Np_paid_members",
	                                        'method'       => "delete_paid_members",
	                                        'hook'         => "cp_members_member_delete_end",
	                                        'settings'     => '',
	                                        'priority'     => 10,
	                                        'version'      => $this->version,
	                                        'enabled'      => "n"
	                                      )
	                                 )
	              );
		
		$DB->query("CREATE TABLE IF NOT EXISTS `exp_paid_members` (
			`id` int(11) NOT NULL auto_increment,
			`member_id` int(11) NOT NULL default '0',
			`item_id` int(11) NOT NULL default '0',
			`join_date` int(11) NOT NULL,
			`renew_date` int(11) NOT NULL,
			`state` varchar(255) NOT NULL default 'active',
			PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
	}
	// END


	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension()
	{
	    global $DB;
		
		$sql[] = "DELETE FROM exp_extensions WHERE class = 'Np_paid_members'";
		$sql[] = "DROP TABLE IF EXISTS exp_paid_members";

		foreach ( $sql as $query )
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
	// --------------------------------
    //  Settings
    // --------------------------------  
    
    function settings()
    {
    	$settings = array();
		$settings['subscription'] = "";
		return($settings);	
    }
    // END	
	
} // End Class
?>