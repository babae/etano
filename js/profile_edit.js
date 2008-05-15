$(function() {
	$('#edit_form input:visible:first').focus();
});

function req_update_location(str_field,val) {
	$('#'+str_field).before('<span class="loading"></span>');
	$.post(baseurl+'/ajax/location.php',
			{'field':str_field,'val':val},
			function(data) {
				if (data!=null && data!='') {
					var allopts=data.split("\n");
					var str_field=allopts[0];
					var toshow=allopts[1].split('|');
					$('#row_'+str_field+'state').addClass('invisible').removeClass('visible');
					$('#row_'+str_field+'city').addClass('invisible').removeClass('visible');
					$('#row_'+str_field+'zip').addClass('invisible').removeClass('visible');
					for (i=0;i<toshow.length;i++) {
						$('#row_'+toshow[i]).addClass('visible').removeClass('invisible');
					}
					if (allopts.length>3) {
						var to_update=$('#'+str_field+allopts[2]);
						to_update.html('<option>Loading</option>');
						var towrite='';
						for (i=3;i<allopts.length;i++) {
							oneopt=allopts[i].split('|');
							towrite+='<option value="'+oneopt[0]+'">'+oneopt[1]+'</option>';
						}
						to_update.html(towrite);
						to_update.focus();
					}
					$('.loading').remove();
				}
			}
	);
}

/*
function field_empty(dbfield,field_type,form_id) {
	myreturn=false;
	if (field_type==104 || field_type==105) {	// FIELD_INT,FIELD_FLOAT
		if ($('#'+dbfield)[0].value==0) {
			myreturn=true;
		}
	}
	return myreturn;
}
*/