jQuery(document).ready(function($){
	$("#bwlms-message-success").show().delay(5000).queue(function(n) {
	  $(this).hide(); n();
	});
});