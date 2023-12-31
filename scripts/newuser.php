<?php
// ADD NEW ADMIN USER TO WORDPRESS
// ----------------------------------
// Put this file in your Wordpress root directory and run it from your browser.
// Delete it when you're done.

require_once __DIR__ . '/../../../../wp-blog-header.php';
// require_once __DIR__ . '/../../../../wp-includes/registration.php';

// ----------------------------------------------------
// CONFIG VARIABLES
// Make sure that you set these before running the file.
$newusername = 'boctulus1';
$newpassword = 'gogogo2k!';
$newemail = 'boctulus@gmail.com';
// ----------------------------------------------------


// Check that user doesn't already exist
if ( !username_exists($newusername) && !email_exists($newemail) )
{
	// Create user and set role to administrator
	$user_id = wp_create_user( $newusername, $newpassword, $newemail);
	if ( is_int($user_id) )
	{
		$wp_user_object = new WP_User($user_id);
		$wp_user_object->set_role('administrator');
		echo 'Successfully created new admin user. Now delete this file!';
	}
	else {
		echo 'Error with wp_insert_user. No users were created.';
	}
}
else {
	echo 'This user or email already exists. Nothing was done.';
}
