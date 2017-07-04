<script language="javascript" type="text/javascript" src="design/js/tinymce/tinymce.min.js"></script>
<script language="javascript">
tinymce.init({
    selector: "textarea.editor_large,textarea.editor_small",
    language : "ru",
	image_caption: true,
    plugins: [
        "advlist autolink lists link image charmap print preview hr anchor pagebreak",
        "searchreplace wordcount visualblocks visualchars code fullscreen",
        "insertdatetime media nonbreaking save table contextmenu directionality",
        "emoticons template paste textcolor colorpicker textpattern responsivefilemanager",
		//"codemirror"
   ],
   toolbar1: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | styleselect formatselect",
   toolbar2: "| responsivefilemanager | link unlink anchor | image media | forecolor backcolor  | print preview code ",
   image_advtab: true ,
   
   external_filemanager_path:"/simpla/design/js/filemanager/",
   filemanager_title:"Менеджер файлов" ,
   external_plugins: { "filemanager" : "../../../../simpla/design/js/filemanager/plugin.min.js"},
		setup : function(ed) {
		if(typeof set_meta == 'function')
		{
			ed.on('keyUp', function() {
    			set_meta();
			});
			ed.on('change', function() {
    			set_meta();
			});
		}
	}
	{literal}}{/literal});
	function myCustomGetContent( id ) {
		if( typeof tinymce != "undefined" ) {
			var editor = tinymce.get( id );
			if( editor && editor instanceof tinymce.Editor ) {
				return editor.getContent{literal}({format : 'text'}{/literal}).substr(0, 512);
			} else {
				return  jQuery('textarea[name='+id+']').val().replace(/(<([^>]+)>)/ig," ").replace(/(\&nbsp;)/ig," ").replace(/^\s+|\s+$/g, '').substr(0, 512);
			}
		}
		return '';
	}
</script>