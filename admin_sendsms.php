<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>Send SMS</h2>
<p>Here you can send sms to any number. This can be used for promotion, Customer notification, Information passing etc.</p>
<?php global $admin_sms_response;  if($admin_sms_response != '') { ?>
<div id="message" class="updated"><p><?php echo $admin_sms_response; 
$admin_sms_response=''; ?></p></div>
<?php } ?>
<form action="" method="post" id="wp-sms-form">
		<table>
			<tr>
				<td>Mobile: </td>
				<td><textarea type="text" name="adminmobile" id="adminmobile" rows="3" cols="30"><?php echo $_SESSION['adminmobile'] ?></textarea><p>Note: Add Multiple Mobile Number in Comma Seperated form. e.g; 9898XXXXXX, 93XXXXXXXX</p></td>
			</tr>
			<tr>
				<td>Message: </td>
				<td>
				<textarea name="adminmessage" id="adminmessage" rows="5" cols="50"><?php echo $_SESSION['adminmessage'] ?></textarea><?php if(get_option('remove_bad_words')) {
					echo "<p>Note: Bad Words in the message will be removed</p>";
				} ?></td>
			</tr>			
			<tr>
				<td>&nbsp;</td>
				<td><input type="submit" id="submit" value="Send SMS" class="button-primary" /></td>
			</tr>
		</table>
		<?php wp_nonce_field('admin_send_sms','admin_send_sms_nonce'); ?>
	</form>
</div>
