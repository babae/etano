<?php
/******************************************************************************
Etano
===============================================================================
File:                       includes/search_functions.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

function search_results($search,$my_membership=1) {
	global $dbtable_prefix;
	global $_pfields;
	$myreturn=array();
	$input['acclevel_code']='search_advanced'; // default access level is the one for advanced search!!!!
	$search_fields=array();
	$continue=false;	// for searches not based on search_fields
	$select="a.`fk_user_id`";
	$from="`{$dbtable_prefix}user_profiles` a";
	$where='1';
	if (isset($search['min_user_id'])) {
		$where.=" AND a.`fk_user_id`>".$search['min_user_id'];
	}
//	if (!empty($_SESSION[_LICENSE_KEY_]['user']['user_id'])) {
//		$where.=" AND a.`fk_user_id`<>'".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
//	}
	$where.=' AND a.`status`='.STAT_APPROVED.' AND a.`del`=0';
	$orderby="ORDER BY a.`score` DESC";

	// define here all search types
	// you can either add fields to be read into $search_fields or build the query directly
	if (isset($search['st'])) {
		switch ($search['st']) {

			case 'basic':
				$input['acclevel_code']='search_basic';
				$search_fields=$GLOBALS['basic_search_fields'];
				if (isset($search['wphoto'])) {
					$where.=" AND a.`_photo`<>''";
				}
				break;

			case 'adv':
				$input['acclevel_code']='search_advanced';
				// for advanced search we get all fields
				foreach ($_pfields as $field_id=>$field) {
					if (isset($field['searchable'])) {
						$search_fields[]=$field_id;
					}
				}
				if (isset($search['wphoto'])) {
					$where.=" AND a.`_photo`<>''";
				}
				break;

			case 'user':
				$input['acclevel_code']='search_advanced';
				$continue=true;
				$input['user']=sanitize_and_format_gpc($search,'user',TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTFIELD],'');
				if (strlen($input['user'])<=3) {
					$where='';	// force no results returned.
				} else {
					$where.=" AND a.`_user` LIKE '".$input['user']."%'";
				}
				break;

			case 'net':
				$input['acclevel_code']='search_basic';
				$continue=true;
				$input['fk_user_id']=sanitize_and_format_gpc($search,'uid',TYPE_INT,0,0);
				$input['fk_net_id']=sanitize_and_format_gpc($search,'nid',TYPE_INT,0,0);
				$select="b.`fk_user_id_other`";
				$from="`{$dbtable_prefix}user_networks` b,".$from;
				$where="b.`fk_user_id`=".$input['fk_user_id']." AND b.`fk_net_id`=".$input['fk_net_id']." AND b.`nconn_status`=1 AND b.`fk_user_id_other`=a.`fk_user_id` AND ".$where;
				break;

			case 'new':
				$input['acclevel_code']='search_basic';
				$continue=true;
				$orderby="ORDER BY a.`date_added` DESC";
				break;

			case 'online':
				$input['acclevel_code']='search_basic';
				$continue=true;
				$from="`{$dbtable_prefix}online` b,".$from;
				$where="b.`fk_user_id`<>0 AND b.`fk_user_id`=a.`fk_user_id` AND ".$where;
				$orderby="GROUP BY b.`fk_user_id` ".$orderby;
				break;

			case 'vote':
			case 'views':
			case 'comm':
// TODO
				break;

			default:
				break;

		}
	}
	if (allow_at_level($input['acclevel_code'],$my_membership)) {
		for ($i=0;isset($search_fields[$i]);++$i) {
			$field=$_pfields[$search_fields[$i]];
			switch ($field['search_type']) {

				case FIELD_SELECT:
					$input[$field['dbfield']]=sanitize_and_format_gpc($search,$field['dbfield'],TYPE_INT,0,0);
					if (!empty($input[$field['dbfield']])) {
						if ($field['field_type']==FIELD_SELECT) {
							$where.=" AND `".$field['dbfield']."`=".$input[$field['dbfield']];
						} elseif ($field['field_type']==FIELD_CHECKBOX_LARGE) {
							$where.=" AND `".$field['dbfield']."` LIKE '%|".$input[$field['dbfield']]."|%'";
						}
	//				} else {
	//					unset($input[$field['dbfield']]);
					}
					break;

				case FIELD_CHECKBOX_LARGE:
					$input[$field['dbfield']]=sanitize_and_format_gpc($search,$field['dbfield'],TYPE_INT,0,0);
					if (!empty($input[$field['dbfield']])) {
						if ($field['field_type']==FIELD_SELECT) {
							if (count($input[$field['dbfield']])) {
								$where.=" AND (";
								for ($j=0;isset($input[$field['dbfield']][$j]);++$j) {
									$where.="`".$field['dbfield']."`=".$input[$field['dbfield']][$j]." OR ";
								}
								$where=substr($where,0,-4);	// substract the last ' OR '
								$where.=')';
							}
						} elseif ($field['field_type']==FIELD_CHECKBOX_LARGE) {
							if (count($input[$field['dbfield']])) {
								$where.=" AND (";
								for ($j=0;isset($input[$field['dbfield']][$j]);++$j) {
									$where.="`".$field['dbfield']."` LIKE '%|".$input[$field['dbfield']][$j]."|%' OR ";
								}
								$where=substr($where,0,-4);	// substract the last ' OR '
								$where.=')';
							}
						}
					} else {
						unset($input[$field['dbfield']]);
					}
					break;

				case FIELD_RANGE:
					$input[$field['dbfield'].'_min']=sanitize_and_format_gpc($search,$field['dbfield'].'_min',TYPE_INT,0,0);
					$input[$field['dbfield'].'_max']=sanitize_and_format_gpc($search,$field['dbfield'].'_max',TYPE_INT,0,0);
					$now=gmdate('YmdHis');
					if (!empty($input[$field['dbfield'].'_max'])) {
						if ($field['field_type']==FIELD_DATE) {
							$where.=" AND `".$field['dbfield']."`>=DATE_SUB('$now',INTERVAL ".$input[$field['dbfield'].'_max']." YEAR)";
						} elseif ($field['field_type']==FIELD_SELECT) {
							$where.=" AND `".$field['dbfield']."`<=".$input[$field['dbfield'].'_max'];
						}
					} else {
						unset($input[$field['dbfield'].'_max']);
					}
					if (!empty($input[$field['dbfield'].'_min'])) {
						if ($field['field_type']==FIELD_DATE) {
							$where.=" AND `".$field['dbfield']."`<=DATE_SUB('$now',INTERVAL ".$input[$field['dbfield'].'_min']." YEAR)";
						} elseif ($field['field_type']==FIELD_SELECT) {
							$where.=" AND `".$field['dbfield']."`>=".$input[$field['dbfield'].'_min'];
						}
					} else {
						unset($input[$field['dbfield'].'_min']);
					}
					break;

				case FIELD_LOCATION:
					$input[$field['dbfield'].'_country']=sanitize_and_format_gpc($search,$field['dbfield'].'_country',TYPE_INT,0,0);
					if (!empty($input[$field['dbfield'].'_country'])) {
						$where.=" AND `".$field['dbfield']."_country`=".$input[$field['dbfield'].'_country'];
						$query="SELECT `prefered_input`,`num_states` FROM `{$dbtable_prefix}loc_countries` WHERE `country_id`=".$input[$field['dbfield'].'_country'];
						if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
						if (mysql_num_rows($res)) {
							list($prefered_input,$num_states)=mysql_fetch_row($res);
							if ($prefered_input=='s' && !empty($num_states)) {
								$input[$field['dbfield'].'_state']=sanitize_and_format_gpc($search,$field['dbfield'].'_state',TYPE_INT,0,0);
								if (!empty($input[$field['dbfield'].'_state'])) {
									$where.=" AND `".$field['dbfield']."_state`=".$input[$field['dbfield'].'_state'];
									$query="SELECT `num_cities` FROM `{$dbtable_prefix}loc_states` WHERE `state_id`=".$input[$field['dbfield'].'_state'];
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
									if (mysql_num_rows($res)) {
										$input[$field['dbfield'].'_city']=sanitize_and_format_gpc($search,$field['dbfield'].'_city',TYPE_INT,0,0);
										if (!empty($input[$field['dbfield'].'_city'])) {
											$where.=" AND `".$field['dbfield']."_city`=".$input[$field['dbfield'].'_city'];
	//									} else {
	//										unset($input[$field['dbfield'].'_city']);
										}
									}
	//							} else {
	//								unset($input[$field['dbfield'].'_state']);
								}
							} elseif ($prefered_input=='z') {
								$input[$field['dbfield'].'_zip']=sanitize_and_format_gpc($search,$field['dbfield'].'_zip',TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTFIELD],'');
								$input[$field['dbfield'].'_dist']=sanitize_and_format_gpc($search,$field['dbfield'].'_dist',TYPE_INT,0,0);
								if (!empty($input[$field['dbfield'].'_zip']) && !empty($input[$field['dbfield'].'_dist'])) {
									$query="SELECT `rad_latitude`,`rad_longitude` FROM `{$dbtable_prefix}loc_zips` WHERE `zipcode`='".$input[$field['dbfield'].'_zip']."'";
									if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
									if (mysql_num_rows($res)) {
										list($rad_latitude,$rad_longitude)=mysql_fetch_row($res);
										// WE USE ONLY MILES HERE. IF YOU WANT KM YOU NEED TO CONVERT MILES TO KM
										// earth radius=3956 miles =6367 km; 3956*2=7912
										// Haversine Formula: (more exact for small distances)
										$where.=" AND a.`rad_latitude`<>-a.`rad_longitude` AND asin(sqrt(pow(sin((".(float)$rad_latitude."-a.`rad_latitude`)/2),2)+cos(".(float)$rad_latitude.")*cos(a.`rad_latitude`)*pow(sin((".(float)$rad_longitude."-a.`rad_longitude`)/2),2)))<=".(((int)$input[$field['dbfield'].'_dist'])/7912);
										// Law of Cosines for Spherical Trigonometry; 60*1.1515=69.09; 1.1515 miles in a degree
	//										$where.=" AND DEGREES(ACOS(SIN(".(float)$rad_latitude.")*SIN(a.`rad_latitude`)+COS(".(float)$rad_latitude.")*COS(a.`rad_latitude`)*COS(".(float)$rad_longitude."-a.`rad_longitude`)))<=".(int)$input[$field['dbfield'].'_dist']/69.09;
									} else {
	// should not return any result or at least warn the member that the zip code was not found.
									}
	//							} else {
	//								unset($input[$field['dbfield'].'_zip'],$input[$field['dbfield'].'_dist']);
								}
							}
						}
	//				} else {
	//					unset($input[$field['dbfield'].'_country']);
					}	// if (!empty($input[$field['dbfield'].'_country']))
					break;

			}	//switch ($field['search_type'])
		} // the for() that constructs the where

		if (!empty($where)) {	// if $where is empty then a condition above prevents us from searching.
			$query="SELECT $select FROM $from WHERE $where $orderby";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			for ($i=0;$i<mysql_num_rows($res);++$i) {
				$myreturn[]=mysql_result($res,$i,0);
			}
		}
	}
	return $myreturn;
}
