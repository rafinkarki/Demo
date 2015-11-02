<?php $form = getFormList();             
function getFormList(){ 
    global $wpdb;       
    $q = "SELECT * FROM wp_competition_table WHERE id =".$_REQUEST['id']."";
    $results = $wpdb->get_row($q);                  
    return $results;                
}   ?>
 <div id="titlediv">
    <div id="titlewrap">        
        <input type="text" name="my_option_name[c_title]" id="title" size="15" tabindex="1" value="<?php echo $form->c_name;?>" autocomplete="off" />
    </div>
</div>