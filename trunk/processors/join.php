<?php
/******************************************************************************
newdsb
===============================================================================
File:                       processors/join.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

require_once '../includes/sessions.inc.php';
require_once '../includes/vars.inc.php';
db_connect(_DBHOSTNAME_,_DBUSERNAME_,_DBPASSWORD_,_DBNAME_);
require_once '../includes/classes/phemplate.class.php';
require_once '../includes/user_functions.inc.php';

$error=false;
$topass=array();
$nextpage='info.php';
$qs='';
$qs_sep='';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	$input['page']=sanitize_and_format_gpc($_POST,'page',TYPE_INT,0,1);
	if ($input['page']==1) {
		$input['user']=strtolower(sanitize_and_format_gpc($_POST,'user',TYPE_STRING,$__html2format[HTML_TEXTFIELD],''));
		$input['pass']=sanitize_and_format_gpc($_POST,'pass',TYPE_STRING,$__html2format[HTML_TEXTFIELD],'');
		$input['email']=strtolower(sanitize_and_format_gpc($_POST,'email',TYPE_STRING,$__html2format[HTML_TEXTFIELD],''));
		$input['email2']=strtolower(sanitize_and_format_gpc($_POST,'email2',TYPE_STRING,$__html2format[HTML_TEXTFIELD],''));
		$input['agree']=sanitize_and_format_gpc($_POST,'agree',TYPE_INT,0,0);

		if (!preg_match('/^[a-z0-9_]+$/',$input['user'])) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='Invalid user name. Please use only letters and digits.';//translate
			$input['error_user']='red_border';
		}
		if (!$error && get_userid_by_user($input['user'])) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='This account already exists. Please choose another one.';
			$input['error_user']='red_border';
		}
		if (!$error && empty($input['pass'])) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='Password cannot be empty. Please enter your password.';
			$input['error_pass']='red_border';
		}
		if (!$error && $input['email']!=$input['email2']) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='Emails do not match. Please check the emails.';
			$input['error_email']='red_border';
		}
		if (!$error && !preg_match('/^[a-z0-9\-\._]+@[a-z0-9\-]+(\.[a-z0-9\-]+)+$/',$input['email'])) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]='Invalid email entered. Please check your email.';
			$input['error_email']='red_border';
		}
		if (!$error) {
			$query="SELECT `user_id` FROM ".USER_ACCOUNTS_TABLE." WHERE `email`='".$input['email']."' LIMIT 1";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			if (mysql_num_rows($res)) {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text'][]=sprintf('The email address %s is already in use.',$input['email']);
				$input['error_email']='red_border';
			}
		}
		if (get_site_option('use_captcha','core')) {
			$captcha=sanitize_and_format_gpc($_POST,'captcha',TYPE_STRING,$__html2format[HTML_TEXTFIELD],'');
			if (!$error && (!isset($_SESSION['captcha_word']) || strcasecmp($captcha,$_SESSION['captcha_word'])!=0)) {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text'][]="The verification code doesn't match. Please enter the new code.";
				$input['error_captcha']='red_border';
			}
		}
		unset($_SESSION['captcha_word']);
		if (!$error && empty($input['agree'])) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text'][]="You must agree to the terms of services before joining the site.";
			$input['error_agree']='red_border';
		}
	}

	$next_join_pages=array();
	$my_fields=array();
	foreach ($_pfields as $field_id=>$field) {
		// what fields should be processed on this page?
		if (isset($field['reg_page'])) {
			if ($field['reg_page']==$input['page']) {
				$my_fields[]=$field_id;
			}
			// what pages are after us?
			if ($field['reg_page']>$input['page']) {
				$next_join_pages[$field['reg_page']]=1;
			}
		}
	}

	$textareas=array();
	$on_changes=array();
	$ch=0;
	for ($i=0;isset($my_fields[$i]);++$i) {
		$field=$_pfields[$my_fields[$i]];
		switch ($field['html_type']) {

			case HTML_DATE:
				$input[$field['dbfield'].'_month']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_month',TYPE_INT,0,0);
				$input[$field['dbfield'].'_day']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_day',TYPE_INT,0,0);
				$input[$field['dbfield'].'_year']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_year',TYPE_INT,0,0);
				if (!empty($input[$field['dbfield'].'_year']) && !empty($input[$field['dbfield'].'_month']) && !empty($input[$field['dbfield'].'_day'])) {
					$input[$field['dbfield']]=$input[$field['dbfield'].'_year'].'-'.str_pad($input[$field['dbfield'].'_month'],2,'0',STR_PAD_LEFT).'-'.str_pad($input[$field['dbfield'].'_day'],2,'0',STR_PAD_LEFT);
				} else {
					$input[$field['dbfield']]='';
				}
				if (isset($field['fn_on_change'])) {
					$on_changes[$ch]['fn']=$field['fn_on_change'];
					$on_changes[$ch]['param2']=array('year'=>$input[$field['dbfield'].'_year'],'month'=>$input[$field['dbfield'].'_month'],'day'=>$input[$field['dbfield'].'_day']);
					$on_changes[$ch]['param3']=$field['dbfield'];
					++$ch;
				}
				break;

			case HTML_LOCATION:
				$input[$field['dbfield'].'_country']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_country',TYPE_INT,0,0);
				$input[$field['dbfield'].'_state']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_state',TYPE_INT,0,0);
				$input[$field['dbfield'].'_city']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_city',TYPE_INT,0,0);
				$input[$field['dbfield'].'_zip']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_zip',TYPE_STRING,$__html2format[HTML_TEXTFIELD],'');
				if (isset($field['fn_on_change'])) {
					$on_changes[$ch]['fn']=$field['fn_on_change'];
					$on_changes[$ch]['param2']=array('country'=>$input[$field['dbfield'].'_country'],'state'=>$input[$field['dbfield'].'_state'],'city'=>$input[$field['dbfield'].'_city'],'zip'=>$input[$field['dbfield'].'_zip']);
					$on_changes[$ch]['param3']=$field['dbfield'];
					++$ch;
				}
				break;

			case HTML_TEXTAREA:
				$textareas[]=$field['dbfield'];
				// no break here, we still need the default behaviour
			default:
				$input[$field['dbfield']]=sanitize_and_format_gpc($_POST,$field['dbfield'],$__html2type[$field['html_type']],$__html2format[$field['html_type']],'');
				if (isset($field['fn_on_change'])) {
					$on_changes[$ch]['fn']=$field['fn_on_change'];
					$on_changes[$ch]['param2']=$input[$field['dbfield']];
					$on_changes[$ch]['param3']=$field['dbfield'];
					++$ch;
				}

		}	// switch
		// check for input errors
		if (isset($field['required'])) {
			if (empty($input[$field['dbfield']]) && $field['html_type']!=HTML_LOCATION) {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text'][]="The fields outlined below are required and must not be empty.";
				$input['error_'.$field['dbfield']]='red_border';
			} elseif ($field['html_type']==HTML_LOCATION && empty($input[$field['dbfield'].'_country'])) {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text'][]='The fields outlined below are required and must not be empty.';
				$input['error_'.$field['dbfield'].'_country']='red_border';
			}
		}
	}

	if (!$error) {
		if ($input['page']==1) {
			$input['temp_pass']=md5(gen_pass(6));
			$query="INSERT IGNORE INTO ".USER_ACCOUNTS_TABLE." SET `user`='".$input['user']."',`pass`=md5('".$input['pass']."'),`email`='".$input['email']."',`membership`='2',`status`='".ASTAT_UNVERIFIED."',`temp_pass`='".$input['temp_pass']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$_SESSION['user']['reg_id']=mysql_insert_id();
			$_SESSION['user']['email']=$input['email'];	// for info_signup.html
			$input['uid']=$_SESSION['user']['reg_id'];
			send_template_email($input['email'],sprintf('%s user registration confirmation',_SITENAME_),'confirm_reg.html',get_my_skin(),$input);
		}
		$query="SELECT `fk_user_id` FROM `{$dbtable_prefix}user_profiles` WHERE `fk_user_id`='".$_SESSION['user']['reg_id']."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		$is_update=false;
		if (mysql_num_rows($res)) {
			$is_update=true;
		}
		$now=gmdate('YmdHis');
		if ($is_update) {
			$query="UPDATE `{$dbtable_prefix}user_profiles` SET `last_changed`='$now'";
		} else {
			$query="INSERT INTO `{$dbtable_prefix}user_profiles` SET `fk_user_id`='".$_SESSION['user']['reg_id']."',`last_changed`='$now',`date_added`='$now'";
		}
		if ($input['page']==1) {
			$query.=",`_user`='".$input['user']."'";
			if (get_site_option('manual_profile_approval','core')==1) {
				$query.=",`status`='".STAT_PENDING."'";
			} else {
				$query.=",`status`='".STAT_APPROVED."'";
			}
		}
		for ($i=0;isset($my_fields[$i]);++$i) {
			if ($_pfields[$my_fields[$i]]['html_type']==HTML_LOCATION) {
				$query.=",`".$_pfields[$my_fields[$i]]['dbfield']."_country`='".$input[$_pfields[$my_fields[$i]]['dbfield'].'_country']."',`".$_pfields[$my_fields[$i]]['dbfield']."_state`='".$input[$_pfields[$my_fields[$i]]['dbfield'].'_state']."',`".$_pfields[$my_fields[$i]]['dbfield']."_city`='".$input[$_pfields[$my_fields[$i]]['dbfield'].'_city']."',`".$_pfields[$my_fields[$i]]['dbfield']."_zip`='".$input[$_pfields[$my_fields[$i]]['dbfield'].'_zip']."'";
			} else {
				$query.=",`".$_pfields[$my_fields[$i]]['dbfield']."`='".$input[$_pfields[$my_fields[$i]]['dbfield']]."'";
			}
		}
		if ($is_update) {
			$query.=" WHERE `fk_user_id`='".$_SESSION['user']['reg_id']."'";
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}

		for ($i=0;isset($on_changes[$i]);++$i) {
			if (function_exists($on_changes[$i]['fn'])) {
				eval($on_changes[$i]['fn'].'($_SESSION[\'user\'][\'reg_id\'],$on_changes[$i][\'param2\'],$on_changes[$i][\'param3\']);');
			}
		}

		ksort($next_join_pages,SORT_NUMERIC);
		if (!empty($next_join_pages)) {
			$page=array_shift(array_keys($next_join_pages));
			$nextpage='join.php';
			$qs.=$qs_sep.'p='.$page;
			$qs_sep='&';
		} else {
			$nextpage='info.php';
			$qs.=$qs_sep.'type=signup';
			$qs_sep='&';
		}
	} else {
		$nextpage='join.php';
// 		you must re-read all textareas from $_POST like this:
//		$input['x']=addslashes_mq($_POST['x']);
		for ($i=0;isset($textareas[$i]);++$i) {
			$input[$textareas[$i]]=addslashes_mq($_POST[$textareas[$i]]);
		}
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
	}
}
redirect2page($nextpage,$topass,$qs);
?>