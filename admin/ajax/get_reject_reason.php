<?php
/******************************************************************************
Etano
===============================================================================
File:                       admin/ajax/get_reject_reason.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once dirname(__FILE__).'/../../includes/common.inc.php';
require_once dirname(__FILE__).'/../../includes/admin_functions.inc.php';
allow_dept(DEPT_ADMIN);

$amtpl_id=sanitize_and_format_gpc($_POST,'amtpl_id',TYPE_INT,0,0);
$output='';

$query="SELECT `subject`,`message_body` FROM `{$dbtable_prefix}admin_mtpls` WHERE `amtpl_id`=$amtpl_id";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
if (mysql_num_rows($res)) {
	$output.='<reason_title>'.rawurlencode(mysql_result($res,0,0)).'</reason_title>';
	$output.='<reject_reason>'.rawurlencode(mysql_result($res,0,1)).'</reject_reason>';
}

header('Content-type: text/xml');
echo '<result>'.$output.'</result>';
