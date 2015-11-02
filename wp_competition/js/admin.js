jQuery(document).ready(function(){
  	jQuery("#submit").click(function(){
  		var unique_id=jQuery("#unique_id").val();
	    var sort_id = [];
	    jQuery('ul#sortable-list'+unique_id+' li').each(function() {
	    	sort_id.push(jQuery(this).attr("id"));
	    });
	    jQuery("#row_order"+unique_id).val(sort_id);
	});

});