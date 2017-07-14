<?php if(!isset($_UPLOAD_POSTHOOK)) exit('$_UPLOAD_POSTHOOK is not defined'); ?>

    <form id="fileupload_<?php echo $_UPLOAD_POSTHOOK; ?>" action="/upload" method="POST" enctype="multipart/form-data" style="padding: 0; margin: 0;">
        <input type="hidden" name="entity_id" value="<?php echo isset($_UPLOAD_ENTITY_ID) ? $_UPLOAD_ENTITY_ID : ''; ?>" />
        <input type="hidden" name="entity_type_id" value="<?php echo isset($_UPLOAD_ENTITY_TYPE_ID) ? $_UPLOAD_ENTITY_TYPE_ID : ''; ?>" />
        <div class="row fileupload-buttonbar" style="padding: 0; margin: 0;">
            <div class="span7">
                <span class="btn btn-success fileinput-button">
                    <i class="icon-plus icon-white"></i>
                    <span>Add files...</span>
                    <input type="file" name="files[]" multiple>
                </span>
                <button type="submit" class="btn btn-primary start">
                    <i class="icon-upload icon-white"></i>
                    <span>Start upload</span>
                </button>
                <button type="reset" class="btn btn-warning cancel">
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cancel upload</span>
                </button>
            </div>
            <div class="span5 fileupload-progress fade">
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="bar" style="width:0%;"></div>
                </div>
                <div class="progress-extended">&nbsp;</div>
            </div>
        </div>
        <div class="fileupload-loading"></div>
        <br>
        <table role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>
    </form>

<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload">
        <td class="preview"><span class=""></span></td>
        <td class="name"><span>{%=file.name%}</span></td>
        <td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label label-important">{%=locale.fileupload.error%}</span> {%=locale.fileupload.errors[file.error] || file.error%}</td>
        {% } else if (o.files.valid && !i) { %}
            <td>
                <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
            </td>
            <td class="start">{% if (!o.options.autoUpload) { %}
                <button class="btn btn-primary">
                    <i class="icon-upload icon-white"></i>
                    <span>{%=locale.fileupload.start%}</span>
                </button>
            {% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
        <td class="cancel">{% if (!i) { %}
            <button class="btn btn-warning">
                <i class="icon-ban-circle icon-white"></i>
                <span>{%=locale.fileupload.cancel%}</span>
            </button>
        {% } %}</td>
    </tr>
{% } %}
</script>

<script>

/*
 * jQuery File Upload Plugin JS Example 6.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

var <?php echo $_UPLOAD_POSTHOOK; ?>_count = 0;
var FILEUPLOAD_ID = "fileupload_<?php echo $_UPLOAD_POSTHOOK; ?>";
$(function() {
	'use strict';

	$('#' + FILEUPLOAD_ID).fileupload({
		forceIframeTransport: false,
		drop : function (e, data) { <?php echo $_UPLOAD_POSTHOOK; ?>_count += data.files.length; },
		paste : function (e, data) { <?php echo $_UPLOAD_POSTHOOK; ?>_count += data.files.length; },
		failed : function (e, data) { <?php echo $_UPLOAD_POSTHOOK; ?>_count -= data.files.length; },
		change : function (e, data) { <?php echo $_UPLOAD_POSTHOOK; ?>_count += data.files.length; },
	});

	// Initialize the jQuery File Upload widget:
	$('#' + FILEUPLOAD_ID).fileupload();
	$('#' + FILEUPLOAD_ID).bind('fileuploaddone', 
			function (e, data) 
			{

				<?php echo $_UPLOAD_POSTHOOK; ?>_count--;
				if(typeof(<?php echo $_UPLOAD_POSTHOOK; ?>) == 'function')
					<?php echo $_UPLOAD_POSTHOOK; ?>(data.result[0], <?php echo $_UPLOAD_POSTHOOK; ?>_count == 0);	
			}
	);
	
	// Enable iframe cross-domain access via redirect option:
	$('#' + FILEUPLOAD_ID).fileupload('option', 'redirect',
			window.location.href.replace(/\/[^\/]*$/, '/cors/result.html?%s'));

	// Load existing files:
	$('#' + FILEUPLOAD_ID).each(function() {
		var that = this;
		$.getJSON(this.action, function(result) {
			if (result && result.length) {
				$(that).fileupload('option', 'done').call(that, null, {
					result : result
				});
			}
		});
	});

});

</script>