$(function() {
	oFCKeditor=new FCKeditor('message_body');
	oFCKeditor.BasePath=document.location.pathname.substring(0,document.location.pathname.lastIndexOf('email_send.php'))+'fckeditor/';
	oFCKeditor.Config["CustomConfigurationsPath"] = oFCKeditor.BasePath+'../js/fckconfig.js';
	oFCKeditor.Config['FullPage']=true;
	oFCKeditor.ToolbarSet='datemill';
	oFCKeditor.Height=500;
	oFCKeditor.ReplaceTextarea();

	$('#subject').focus();
});

