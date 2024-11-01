<?php
/*
Plugin Name: WP-SendSMS
Plugin URI: http://thedigilife.com/wordpress-sms-plugin-wp-sendsms/
Description: This Plugin Helps for Sending SMS Using SMS Gateway. For more information Visit http://thedigilife.com
Author: Chirag Kalani
Version: 1.1
Author URI: http://thedigilife.com
*/
global $wpsms_options;
$wpsms_options=array(
		'wpsms_api1'=>'http://example.com/smsapi.php?mobile=[Mobile]&sms=[TextMessage]&senderid=[SenderID]&scheduledatetime=[ScheduleTime]');
		
add_action('admin_menu','sms_admin_menu');
register_activation_hook( __FILE__, 'wpsms_activate' );
register_deactivation_hook( __FILE__, 'wpsms_deactivate' );
add_shortcode('wpsms_form', 'sms_form'); 
add_action('init','wpsms_init'); 

add_action( 'wp_enqueue_scripts', 'wpsms_styles' );
add_action( 'admin_enqueue_scripts', 'wpsms_styles' );

add_action('wp_enqueue_scripts', 'wpsms_scripts');
add_action('admin_enqueue_scripts', 'wpsms_scripts'); 

function wpsms_init()
{
	if(!isset($_SESSION))
		session_start();
}
function wpsms_activate()
{
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	// add Default Settings to the options
	$wpsms_options=array(
		'wpsms_api1'=>'http://example.com/smsapi.php?username=yourusername&password=yourpassword&mobile=[Mobile]&sms=[TextMessage]&senderid=[SenderID]',
		'remove_bad_words'=>'1',
		'captcha'=>'1',
		'captcha_width'=>'70',
		'captcha_height'=>'25',
		'captcha_characters'=>'4',
		'maximum_characters'=>'140',
		'confirm_page'=>'1',
		'sender_id'=>'',
		'allow_without_login'=>'0');
	foreach($wpsms_options as $option=>$value)
	{
		add_option($option,$value);
	}
	
	// Create Database Tables 
	$sql='CREATE TABLE '.$wpdb->prefix.'sent_sms (
		`id` INT NOT NULL AUTO_INCREMENT,
		`user_id` INT NOT NULL ,
		`mobile` VARCHAR(15) NOT NULL ,
		`message` TEXT NOT NULL ,
		`response` TEXT NULL ,
		`ip` VARCHAR(20) NOT NULL ,
		`sent_time` DATETIME NOT NULL,
		 PRIMARY KEY  (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1';
	
	dbDelta($sql);
}
function wpsms_deactivate()
{
	global $wpsms_options;
	foreach($wpsms_options as $option=>$value)
	{
		delete_option($option);
	}
}
function sms_admin_menu()
{
	//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	add_menu_page( 'SMS Settings', 'SMS', 'manage_options', 'sms', 'sms_settings');
	//add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
	add_submenu_page( 'sms', 'Sent SMS', 'Sent SMS', 'manage_options', 'sent-sms', 'sms_sent');
	add_submenu_page( 'sms', 'Send SMS', 'Send SMS', 'manage_options', 'send-sms', 'sms_send');
}

function sms_settings()
{
	require('sms_admin_settings.php');
}
function sms_send()
{	
	require('admin_sendsms.php');
}
add_action('init', 'adminsms_send');
function adminsms_send()
{
	if(isset($_POST['admin_send_sms_nonce']))
	{
		if (!wp_verify_nonce($_POST['admin_send_sms_nonce'],'admin_send_sms'))
		{
			$errors.='<p>Sorry, Problem in submitting the form.</p>';
		}
		else
		{
			// Validating form Data
			$_SESSION['message']=filter_var($_POST['adminmessage'], FILTER_SANITIZE_STRING);
			$_SESSION['mobile']=filter_var($_POST['adminmobile'], FILTER_SANITIZE_STRING);
			
			if($_POST['adminmobile']=='' || $_POST['adminmessage']=='') {
				$errors.="<li>Mobile Number and Message are Required.</li>";
			}			
			if(get_option('remove_bad_words')) {
				$_SESSION['message']=sanitize_badwords($_SESSION['message']);
			}
		}
		if(empty($errors))
		{
			$redirection_url=$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];			
			
			//send SMS and Make Database Entry			
			global $admin_sms_response;
			$admin_sms_response=wpsms_send_admin(); // Send SMS
			
			$redirection_url=$_SERVER["REQUEST_URI"];
			if(strpos($redirection_url,'?')) 
				$redirection_url.='&sent=1';
			else	
				$redirection_url.='?sent=1';
			//echo "<script>location.href='$redirection_url';</script>";		
			
		}
	}
}

function sms_sent()
{
	require('sms_sent.class.php');	
    //Create an instance of our package class...
    $sentSMSListTable = new Sent_SMS_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $sentSMSListTable->prepare_items();   
	?>
	<div class="wrap">	
		<div id="icon-users" class="icon32"><br/></div>
		<h2>Sent Messages</h2>
		<?php if(!empty($sentSMSListTable->notify)) { ?>
		<div id="message" class="updated below-h2">
			<p><?php echo $sentSMSListTable->notify; ?></p>
		</div>
		<?php } ?>
		<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
		<form id="sent-sms-filter" method="get">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<!-- Now we can render the completed list table -->
			<?php $sentSMSListTable->display() ?>
		</form>
		
	</div>
	<?php
}
function sms_form($args)
{	
	require('sms_form.php');
	return $content;
}
function wpsms_styles() {
	wp_register_style('wpsms-style', WP_PLUGIN_URL.'/wp-sendsms/css/wpsms.css');
	wp_enqueue_style('wpsms-style');
}
function wpsms_scripts() {
	wp_register_script('wpsms-script', WP_PLUGIN_URL.'/wp-sendsms/js/wpsms.js', array('jquery'));
	wp_enqueue_script('wpsms-script');
}
function sanitize_badwords($message)
{
	$badwords=file_get_contents(WP_PLUGIN_DIR.'/wp-sendsms/badwords.txt','r');
	$badwords_arr=explode("\r\n",$badwords);
	for($i=0;$i<count($badwords_arr);$i++)
	{
		$message=trim(str_replace($badwords_arr,'',$message));
	}
	return $message;
}

function wpsms_send()
{
	$api=get_option('wpsms_api1');
	$mobile=$_SESSION['mobile'];
	$message=$_SESSION['message'];
	$sender_id=get_option('sender_id');
	
	/* check for allowing user to send sms without login. */
	$current_user = wp_get_current_user();
	$user_id=$current_user->ID;
	if(!get_option('allow_without_login'))
	{
		if(!is_user_logged_in())
		{
			$response="<p>Please Login to send SMS.</p>";
			return $response;
		}
	}
	
	$api=str_replace('[Mobile]',$mobile,$api);
	$api=str_replace('[TextMessage]',urlencode($message),$api);
	$api=str_replace('[SenderID]',$sender_id,$api);
	//$response=file_get_contents(urlencode($api));
	$responseArr=wp_remote_request($api);	
	$response = $responseArr['body'];
	
	/* Make Datbase Entry */
	global $wpdb;
	$table='wp_sent_sms';
	$data=array('user_id'=>$user_id, 'mobile'=>$mobile, 'message'=>$message, 'response'=>$response, 'ip'=>$_SERVER['REMOTE_ADDR'], 'sent_time'=>date_i18n('Y-m-d H:i:s'));
	$wpdb->insert($table, $data);
	
	/* Set Display response according to setting original or custom */
	if(get_option('custom_response'))
	{
		$response='<p id="sms-response">'.get_option('custom_response_text').'</p>';
	}
	
	return $response;
}
function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	echo $url;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	echo $data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
function wpsms_send_admin()
{
	$api=get_option('wpsms_api1');
	$mobiles=explode(",",$_SESSION['mobile']);
	$message=$_SESSION['message'];
	$sender_id=get_option('sender_id');
	
	/* check for allowing user to send sms without login. */
	$current_user = wp_get_current_user();
	$user_id=$current_user->ID;
	$api=str_replace('[TextMessage]',urlencode($message),$api);
	$api=str_replace('[SenderID]',$sender_id,$api);
	foreach ($mobiles as $mobile)
	{
		$api2=str_replace('[Mobile]',trim($mobile),$api);	
		//$response=get_data(urlencode($api2));
		$responseArr=wp_remote_request($api2);	
		$response = $responseArr['body'];
		/* Make Datbase Entry */
		global $wpdb;
		$table=$wpdb->prefix.'sent_sms';
		$data=array('user_id'=>$user_id, 'mobile'=>$mobile, 'message'=>$message, 'response'=>$response, 'ip'=>$_SERVER['REMOTE_ADDR'], 'sent_time'=>date_i18n('Y-m-d H:i:s'));
		$wpdb->insert($table, $data);
	}
	
	/* Set Display response according to setting original or custom */
	if(get_option('custom_response'))
	{
		$response='<p id="sms-response">'.get_option('custom_response_text').'</p>';
	}
	
	return $response;
}

add_action('init', 'send_sms_form_process');
function send_sms_form_process()
{
	/* Process Front Form */
	if(isset($_POST['send_sms_nonce']))
	{
		if (!wp_verify_nonce($_POST['send_sms_nonce'],'send_sms'))
		{
			$errors.='<p>Sorry, Problem in submitting the form.</p>';
		}
		else
		{
			// Validating form Data
			$_SESSION['message']=filter_var($_POST['message'], FILTER_SANITIZE_STRING);
			$_SESSION['mobile']=filter_var($_POST['mobile'], FILTER_SANITIZE_STRING);
			if(get_option('captcha')) {
				if($_POST['security_code']=='')	{
					$errors.="<li>Security Code is Required.</li>";
				}
				else if(strtolower($_POST['security_code'])!=$_SESSION['security_code']) {
					$errors.="<li>Security Code is wrong.</li>";
				}
			}
			if($_POST['mobile']=='' || $_POST['message']=='') {
				$errors.="<li>Mobile Number and Message are Required.</li>";
			}
			if(strlen($_POST['mobile'])!=10)
			{
				$errors.="<li>Mobile Number must be of 10 Digit</li>";
			}
			$maxlength=get_option('maximum_characters');
			$curlength=strlen($_SESSION['message']);
			if($curlength>$maxlength)
			{
				$errors.="<li>Message length must be less than $maxlength characters.</li>";
			}
			if(get_option('remove_bad_words')) {
				$_SESSION['message']=sanitize_badwords($_SESSION['message']);
			}
		}
		if(empty($errors))
		{
			$redirection_url=$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
			//Check for Confirm page to show
			if(get_option('confirm_page'))
			{
				$_SESSION['confirm_page']=get_option('confirm_page');		
			}
			else 
			{
				//send SMS and Make Dabase Entry
				$_SESSION['response']=wpsms_send(); // Send SMS
				$redirection_url=$_SERVER["REQUEST_URI"];
				if(strpos($redirection_url,'?')) 
					$redirection_url.='&sent=1';
				else	
					$redirection_url.='?sent=1';
				echo "<script>location.href='$redirection_url';</script>";
			}		
			
			
		}
	}
	/* To Process Confirm page submission */
	if(isset($_POST['confirm_send_sms_nonce']))
	{
		if (!wp_verify_nonce($_POST['confirm_send_sms_nonce'],'confirm_send_sms'))
		{
			$errors.='<p>Sorry, Problem in submitting the form.</p>';
		}
		else
		{
			//call the api
			unset($_SESSION['confirm_page']);
			$_SESSION['response']=wpsms_send();
			$redirection_url=$_SERVER["REQUEST_URI"];
			if(strpos($redirection_url,'?')) 
				$redirection_url.='&sent=1';
			else	
				$redirection_url.='?sent=1';
			echo "<script>location.href='$redirection_url'</script>";
		}
	}
}