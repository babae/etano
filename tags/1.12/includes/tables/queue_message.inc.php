<?php
/******************************************************************************
Etano
===============================================================================
File:                       includes/tables/queue_message.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

$queue_message_default['defaults']=array('mail_id'=>0,'fk_user_id'=>0,'fk_user_id_other'=>0,'_user_other'=>'','subject'=>'','message_body'=>'','message_type'=>0);
$queue_message_default['types']=array('mail_id'=>FIELD_INT,'fk_user_id'=>FIELD_INT,'fk_user_id_other'=>FIELD_INT,'_user_other'=>FIELD_TEXTFIELD,'subject'=>FIELD_TEXTFIELD,'message_body'=>FIELD_TEXTAREA,'message_type'=>FIELD_INT);