<?php
/******************************************************************************
Etano
===============================================================================
File:                       includes/classes/fields/field_textarea.class.php
$Revision: 207 $
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/


class field_textarea extends iprofile_field {
	var $empty_value=array('edit'=>'','display'=>'','search'=>'');

	function field_textarea($config=array(),$is_search=false) {
		$this->config=$config;
		$this->is_search=$is_search;
		if (isset($this->config['default_value'])) {
			$this->value=$this->config['default_value'];
		} else {
			$this->value=$this->empty_value['edit'];
		}
	}

	function set_value(&$all_values,$sanitize=true) {
		if ($sanitize) {
			$this->value=sanitize_and_format_gpc($all_values,$this->config['dbfield'],TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTAREA],$this->empty_value['edit']);
			if (!empty($this->config['ta_len'])) {
				$this->value=substr($this->value,0,$this->config['ta_len']);
			}
			$this->value=remove_banned_words($this->value);
		} elseif (isset($all_values[$this->config['dbfield']])) {
			$this->value=$all_values[$this->config['dbfield']];
		}
		return true;
	}

	function edit($tabindex=1) {
		$myreturn='<textarea name="'.$this->config['dbfield'].'" id="'.$this->config['dbfield'].'" rows="" cols="" tabindex="'.$tabindex.'">'.sanitize_and_format($this->value,TYPE_STRING,$GLOBALS['__field2format'][TEXT_DB2EDIT]).'</textarea>';
		if (!empty($this->config['ta_len'])) {
			$myreturn.='<p class="comment char_counter">'.$GLOBALS['_lang'][125].' <span id="'.$this->config['dbfield'].'_chars">'.((int)$this->config['ta_len']-strlen($this->value)).'</span></p>';
		}
		return $myreturn;
	}

	function display() {
		$myreturn=sanitize_and_format($this->value,TYPE_STRING,$GLOBALS['__field2format'][TEXT_DB2DISPLAY]);
		if (!empty($this->config['bbcode_profile'])) {
			$myreturn=bbcode2html($myreturn);
		}
		if (!empty($this->config['use_smilies'])) {
			$myreturn=text2smilies($myreturn);
		}
		return $myreturn;
	}

	function search() {
		if ($this->search!=null) {
			return $this->search;
		} elseif (!empty($this->config['search_type']) && is_file(_BASEPATH_.'/includes/classes/fields/'.$this->config['search_type'].'.class.php')) {
			$class_name=$this->config['search_type'];
			$new_config=$this->config;
			if (isset($new_config['search_default'])) {
				$new_config['label']=$new_config['search_label'];
				$new_config['default_value']=$new_config['search_default'];
				unset($new_config['search_default'],$new_config['search_label'],$new_config['searchable'],$new_config['required'],$new_config['search_type'],$new_config['reg_page']);
			}
			$new_config['parent_class']=get_class();
			$this->search=new $class_name($new_config,true);
//			$temp=array($this->config['dbfield']=>$this->value);
//			$this->search->set_value($temp,false);
			return $this->search;
		} else {
			return $this;
		}
	}

	function edit_admin() {
		return '';
	}

	function query_select() {
		return '`'.$this->config['dbfield'].'`';
	}

	function query_set() {
		// $this->value should be sanitized for DB if set_value() didn't sanitize the input.
		// This means that we should call this function only in an addedit processor!!!!
		return '`'.$this->config['dbfield']."`='".$this->value."'";
	}

	function query_search() {
		return ' AND MATCH(`'.$this->config['dbfield']."`) AGAINST ('".$this->value."')";
	}

	function edit_js() {
		$myreturn='';
		if (!empty($this->config['ta_len'])) {
			$myreturn.='$(\'#'.$this->config['dbfield'].'\').bind(\'keyup\',function() {
				var remaining='.$this->config['ta_len'].'-$(this).val().length;
				if (remaining<0) {
					$(this).val($(this).val().substr(0,$(this).val().length+remaining));
					remaining=0;
				}
				$(\'#'.$this->config['dbfield'].'_chars\').html(remaining.toString());
			}).bind(\'blur\',function() {
				var remaining='.$this->config['ta_len'].'-$(this).val().length;
				if (remaining<0) {
					$(this).val($(this).val().substr(0,$(this).val().length+remaining));
					remaining=0;
				}
				$(\'#'.$this->config['dbfield'].'_chars\').html(remaining.toString());
			});';
		}
		if ($this->config['required']) {
			$myreturn.='$(\'#'.$this->config['dbfield'].'\').parents(\'form\').bind(\'submit\',function() {
				if ($(\'#'.$this->config['dbfield'].'\',this).val()==\''.$this->empty_value['edit'].'\') {
					alert(\'"'.$this->config['label'].'" cannot be empty\');
					$(\'#'.$this->config['dbfield'].'\',this).focus();
					return false;
				}
			});';
		}
		return $myreturn;
	}

	function validation_server() {
		$myreturn=true;
		if (!empty($this->config['required']) && $this->value==$this->empty_value['edit']) {
			$myreturn=false;
		}
		return $myreturn;
	}

	function get_value($as_array=false) {
		if ($as_array) {
			return array($this->config['dbfield']=>$this->value);
		} else {
			return $this->value;
		}
	}
}
