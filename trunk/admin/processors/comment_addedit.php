<?php
/******************************************************************************
newdsb
===============================================================================
File:                       admin/processors/flirts_addedit.php
$Revision: 21 $
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

require_once '../../includes/sessions.inc.php';
require_once '../../includes/vars.inc.php';
db_connect(_DBHOSTNAME_,_DBUSERNAME_,_DBPASSWORD_,_DBNAME_);
require_once '../../includes/classes/phemplate.class.php';
require_once '../../includes/admin_functions.inc.php';
allow_dept(DEPT_ADMIN);

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='comment_addedit.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	$input['m']=sanitize_and_format_gpc($_POST,'m',TYPE_STRING,0,'');

	$default['defaults']=array();
	if ($input['m']=='blog') {
		require_once '../../includes/tables/blog_comments.inc.php';
		$default=$blog_comments_default;
		$table="`{$dbtable_prefix}blog_comments`";
	} elseif ($input['m']=='photo') {
		require_once '../../includes/tables/photo_comments.inc.php';
		$default=$photo_comments_default;
		$table="`{$dbtable_prefix}photo_comments`";
	} elseif ($input['m']=='user') {
		require_once '../../includes/tables/profile_comments.inc.php';
		$default=$profile_comments_default;
		$table="`{$dbtable_prefix}profile_comments`";
	}
	foreach ($default['types'] as $k=>$v) {
		$input[$k]=sanitize_and_format_gpc($_POST,$k,$__field2type[$v],$__field2format[$v],$default['defaults'][$k]);
	}
	if (isset($_POST['return']) && !empty($_POST['return'])) {
		$input['return']=sanitize_and_format($_POST['return'],TYPE_STRING,$__field2format[FIELD_TEXTFIELD] | FORMAT_RUDECODE);
		$nextpage=$input['return'];
	}

// check for input errors
	if (empty($input['comment'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']='Please enter the comment';
		$input['error_comment']='red_border';
	}

	if (!$error) {
		if (!empty($input['comment_id'])) {
			unset($input['fk_parent_id'],$input['fk_user_id']);
			$input['status']=STAT_APPROVED;
			$query="UPDATE $table SET ";
			foreach ($default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.="`$k`='".$input[$k]."',";
				}
			}
			$query=substr($query,0,-1);
			$query.=" WHERE `comment_id`='".$input['comment_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']='Comment changed.';
		} else {
			$query="INSERT INTO $table SET ";
			foreach ($default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.="`$k`='".$input[$k]."',";
				}
			}
			$query=substr($query,0,-1);
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']='Comment added.';
		}
	} else {
		$nextpage='comment_addedit.php';
// 		you must re-read all textareas from $_POST like this:
//		$input['x']=addslashes_mq($_POST['x']);
		$input['flirt_text']=addslashes_mq($_POST['flirt_text']);
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
	}
}
$nextpage=_BASEURL_.'/admin/'.$nextpage;
redirect2page($nextpage,$topass,'',true);
?>