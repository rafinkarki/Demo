<?php
function insert(){
    $id=""; $textarea='';$textfield='';$ddfield='';$radiobutton='';$checkbox='';$temp=[];
    $id=$_POST['c_id'];
    $textfields=$_POST['textfield'];
    if($textfields){$textfield = array_map('trim',$textfields);$count_textfield=count($textfield);}
    $ddfields=$_POST['ddfield'];
    if($ddfields){$ddfield = array_map('trim',$ddfields); $count_ddfield=count($ddfield);}
    $textareas=$_POST['textarea'];
    if($textareas){$textarea = array_map('trim',$textareas);$count_textarea=count($textarea);}
    $radiobuttons=$_POST['radiobutton'];
    if($radiobuttons){$radiobutton = array_map('trim',$radiobuttons);$count_radiobutton=count($radiobutton);}
    $checkboxes=$_POST['checkbox'];
    if($checkboxes){$checkbox = array_map('trim',$checkboxes);$count_checkbox=count($checkbox);}

    global $wpdb;
    $table_data=$wpdb->prefix."competition_data";
    if($count_textfield>0){array_push($temp,$count_textfield);}
    if($count_textarea>0){ array_push($temp,$count_textarea);}
    if($count_ddfield>0){ array_push($temp,$count_ddfield);}
    if($count_radiobutton>0){ array_push($temp,$count_radiobutton);}
    if($count_checkbox>0){ array_push($temp,$count_checkbox);}
    $count=max($temp);
    for($i=0; $i<$count; $i++){ 
        if(!empty($textfield[$i]) || !empty($textarea[$i]) || !empty($ddfield[$i])|| !empty($radiobutton[$i])|| !empty($checkbox[$i])) {
           $checked=$wpdb->insert($table_data, array('parent_id' =>$id,'Textfields' =>stripslashes($textfield[$i]), 'Textareas' =>stripslashes($textarea[$i]),'Dropdowns'=>stripslashes($ddfield[$i]),'Radiobuttons'=>stripslashes($radiobutton[$i]),'Checkboxes'=>stripslashes($checkbox[$i] )));
        }
    }            
    if($checked==true){       
        die('1');           
    }
    if($checked==false){ 
        die('0');
    }
    return true;  
}
add_action('wp_ajax_insert', 'insert');
add_action('wp_ajax_nopriv_insert', 'insert');

