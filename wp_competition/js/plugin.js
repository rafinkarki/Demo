jQuery(document).ready(function(){
	jQuery(".button_register").click(function(){
	var id=jQuery(this).closest("div").attr('id');
	console.log(id);
	var x=0;var y=0; var z=0;var textfield=[]; var textarea=[]; var ddfield=[]; var radiobutton=[];var checkbox=[];var count=0; var field_loop=0;
    jQuery("#success_message"+id).hide();
	jQuery("#failed_message"+id).hide(); 
    var captcha_code=MyAjax.captcha_code;
    console.log(captcha_code);
    count=jQuery("#phpVar"+id).val(); 
    var flag=0;	
		function isDate(txtDate)
		{
		    var currVal = txtDate;
		    if(currVal == '')
		        return false;		    
		    var rxDatePattern = /^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/; 
		    var dtArray = currVal.match(rxDatePattern); // is format OK?		    
		    if (dtArray == null) 
		        return false;		    
		    //Checks for dd/mm/yyyy format.
		    dtDay = dtArray[1];
		    dtMonth= dtArray[3];
		    dtYear = dtArray[5]; 	    
		    if (dtMonth < 1 || dtMonth > 12) 
		        return false;
		    else if (dtDay < 1 || dtDay> 31) 
		        return false;
		    else if ((dtMonth==4 || dtMonth==6 || dtMonth==9 || dtMonth==11) && dtDay ==31) 
		        return false;
		    else if (dtMonth == 2) 
		    {
		        var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
		        if (dtDay> 29 || (dtDay ==29 && !isleap)) 
		            return false;
		    }
		    return true;
		}
		var numericReg = /^\d*[0-9](|.\d*[0-9]|,\d*[0-9])?$/;	
		var characterReg = /^\s*[a-zA-Z0-9,\s]+\s*$/;
		var fakeReg = /(.)\1{2,}/;
		var urlReg = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/|www\.)[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;
		var captcha_text=jQuery('#captcha_text'+id).val();
		jQuery("#captcha"+id+"_message").hide();
		if(captcha_text==""){
			jQuery("#captcha"+id+"_message").show();
	      	jQuery("#captcha"+id+"_message").html("* Please enter the CAPTCHA code in the field provided.");		
	      	flag=1;
	    }
	    if(captcha_text!="" && typeof(captcha_text) != 'undefined'){
		    if((captcha_text.toLowerCase()!=captcha_code.toLowerCase()) && flag==0){
		    	jQuery("#captcha"+id+"_message").show();
		      	jQuery("#captcha"+id+"_message").html("* Sorry, the CAPTCHA code is incorrect!");		
		      	flag=1;
		    }
		}
        for(var i=0; i<count; i++){ 
        	field_loop=1;
        	jQuery("#textfield"+id+i+"_message").hide();
        	jQuery("#textarea"+id+i+"_message").hide();
        	jQuery("#ddfield"+id+i+"_message").hide();
        	jQuery("#checkbox"+id+i+"_message").hide();
        	var textfield_type=jQuery('#textfield_type'+id+i).val();
        	if(jQuery('#textfield'+id+i).val() == ""){
				if(jQuery('#textfield_req'+id+i).val()==1){
					jQuery("#textfield"+id+i+"_message").show();
					jQuery("#textfield"+id+i+"_message").html("* "+jQuery("#textfieldlabel"+id+i).text()+" field is required.");				        
				    flag=1;
				}
			} 	
			if(jQuery('#textfield'+id+i).val()){ 	
			 	textfield[i] =jQuery('#textfield'+id+i).val();		 	 
    			if(textfield_type=='password'){  
    				textfield[i]= CryptoJS.MD5(textfield[i]).toString();
    			}	
				textfield_temp=textfield[i];	 	
				textfield[i]=jQuery('#textfieldlabel'+id+i).text()+': '+textfield[i];
			 	if(textfield_type=='text'){
    				if(numericReg.test(textfield_temp)) { 
    					jQuery("#textfield"+id+i+"_message").show();
				    	jQuery("#textfield"+id+i+"_message").html("* "+jQuery("#textfieldlabel"+id+i).text()+" field is numeric.");				        
				        flag=1;
				    }
				    if(!characterReg.test(textfield_temp)) {
				    	jQuery("#textfield"+id+i+"_message").show();
				    	jQuery("#textfield"+id+i+"_message").html("* Special characters in "+jQuery("#textfieldlabel"+id+i).text()+" field.");				        
				        flag=1;
				    }	
    			}

    			if(textfield_type=='number'){ 
    				var value = jQuery('#textfield'+id+i).val().replace(/^\s\s*/, '').replace(/\s\s*$/, '');
				    var intRegex = /^\d+$/;
				    if(!intRegex.test(value)) {
				    	jQuery("#textfield"+id+i+"_message").show();
				    	jQuery("#textfield"+id+i+"_message").html("* Enter numbers only.");
				        success = false;
				        flag=1;
				    }	
    			}	
    			if(textfield_type=='date'){ 
				    if(!isDate(textfield_temp)) {
				    	jQuery("#textfield"+id+i+"_message").show();
				    	jQuery("#textfield"+id+i+"_message").html("* Enter valid date.");
				        flag=1;
				    }	
    			}

    			if(textfield_type=='url'){ 
    				if(!urlReg.test(textfield_temp)) {
    					jQuery("#textfield"+id+i+"_message").show();
				    	jQuery("#textfield"+id+i+"_message").html("* "+jQuery("#textfieldlabel"+id+i).text()+" field is invalid.");				        
				        flag=1;
				    }
    			}
    			if(textfield_type=='email'){ 
    				if(!validateEmail(textfield_temp) ){
			        	jQuery("#textfield"+id+i+"_message").show();
						jQuery("#textfield"+id+i+"_message").html("* "+jQuery("#textfieldlabel"+id+i).text()+" field is invalid.");				        
				        flag=1;				
					}
    			}
			}
			
			if(jQuery('#textarea'+id+i).val()){
				 textarea[i]=jQuery('#textarea'+id+i).val(); 
				 textarea[i]=jQuery("#textarealabel"+id+i).text()+": "+textarea[i];			 			 
			}
			if(jQuery('#textarea'+id+i).val() == "") {
				if(jQuery('#textarea_req'+id+i).val()==1){
					jQuery("#textarea"+id+i+"_message").show();
					jQuery("#textarea"+id+i+"_message").html("* "+jQuery("#textarealabel"+id+i).text()+" field is required.");				        
				    flag=1;
				}
			}					
			if(jQuery( "#ddfield"+id+i+" option:selected" ).text()){
				ddfield[i] =jQuery( "#ddfield"+id+i+" option:selected" ).text();	
				ddfield[i]= jQuery("#ddfieldlabel"+id+i).text()+": "+ddfield[i]; 			    
			}
			
			if(jQuery( "input[name=radiobutton_option"+id+i+"]:checked").val()){
				radiobutton[i] =jQuery( "input[name=radiobutton_option"+id+i+"]:checked").val();	
				radiobutton[i]= jQuery("#radiobuttonlabel"+id+i).text()+": "+radiobutton[i]; 
			}
				
			checkbox[i]=jQuery("input[name=checkbox"+id+i+"]:checked").map(function() {
			    return this.value;
			}).get().join(",");
			if(checkbox[i]=="" && jQuery('#checkbox_req'+id+i).val()==1){
				jQuery("#checkbox"+id+i+"_message").show();
				jQuery("#checkbox"+id+i+"_message").html("* "+jQuery("#checkboxlabel"+id+i).text()+" field is required.");				        
				flag=1;
			}
			if(checkbox[i]!=""){
				checkbox[i]= jQuery("#checkboxlabel"+id+i).text()+": "+checkbox[i]; 
			}			    
			
		}
		
		if(flag==0){
			if(field_loop==1){	
				ajax();	
				clearField(id,count);
			}
			else{
				jQuery("#failed_message"+id).show();
				jQuery("#failed_message"+id).html("No data to register.");
				clearField(id,count);
			}
		}
		
		function clearField(id,total){
			jQuery('#captcha_text'+id).val("");
			for(var i=0; i<count; i++){ 
				jQuery('#textfield'+id+i).val('');
				jQuery('#textarea'+id+i).val(''); 
				jQuery( "#ddfield"+id+i+" option:selected" ).removeAttr('selected');
				jQuery("input[name=radiobutton_option"+id+i+"]:checked").prop('checked', 'checked');
				jQuery("input[name=radiobutton_option"+id+i+"]:First").attr('checked', true);
				jQuery("input[name=checkbox"+id+i+"]:checked").removeAttr('checked');
			}
		}
		function validateEmail($email) {
		  	var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
		  	return emailReg.test( $email );			
		}
		function ajax(){
			jQuery.ajax({
				type: 'POST',
				url: MyAjax.ajaxurl,
				data: {"action": "insert" ,"c_id":id,"textfield":textfield,"textarea":textarea,"ddfield":ddfield,"radiobutton":radiobutton,"checkbox":checkbox},
				success: function(response){
					if(response=='1'){
						jQuery("#success_message"+id).show();
						jQuery("#failed_message"+id).hide();
						jQuery("#success_message"+id).html('Successfully registered');	
					}
					else{
						jQuery("#failed_message"+id).show();
						jQuery("#success_message"+id).hide();
						jQuery("#failed_message"+id).html('Failed to register');
					}						
				}
			});
		}
});
});

	

