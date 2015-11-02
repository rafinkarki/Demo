<?php
function form($atts){    
    global $wpdb; $html="";
    extract( shortcode_atts( array(        
        'slug' => '',        
    ), $atts ) );
    
    $c_date=strtotime(date("Y-m-d"));
    $value="SELECT * from wp_competition_table where c_slug='".$atts[0]."'"; 
    $value=$wpdb->get_row($value); 
    $uniqueid=$value->id;
    $data=get_option('my_option_name');  
    $num=$data['no_of_fields'];
    if($value):
        $s_date=strtotime($value->s_date);
        $e_date=strtotime($value->e_date);
        $captcha=$value->captcha;
        if($s_date==null || $e_date==null):
            echo 'Please enter competition date.';
        elseif($value->e_date<$value->s_date):     
            echo 'There is no any competition.'; 
        else:   
            if($c_date>=$s_date && $c_date<=$e_date):       
                $sql = "SELECT * FROM wp_competition_items WHERE id=".$value->id.""; 
                $result=$wpdb->get_results($sql);    
                    if(empty($result) && $captcha==0):
                        echo 'No form to display.';
                    else:?>
                        <h1><?php echo $value->c_name;?></h1>
                        <?php                   
                        $d_form="SELECT no_of_fields,form_html,modified_form from wp_competition_table where c_slug='".$atts[0]."'";
                        $d_form=$wpdb->get_results($d_form);
                        $d_num=$d_form[0]->no_of_fields;
                        if(!empty($d_form[0]->modified_form) && $num==$d_num){ 
                            echo $d_form[0]->modified_form;
                        } 
                        else{
                            echo $d_form[0]->form_html;
                        }   
                    endif;
               elseif($c_date<$s_date && $c_date<$e_date && $e_date>=$s_date):
                echo 'Competition is about to begin.';        
            else:
                echo 'Competition has been ended';            
            endif;      
        endif;  
    else:
        echo'There is no such competititon';
    endif;?>
    <script>
        jQuery(document).ready(function(){   
           var count=0;
           var id='<?php echo $uniqueid;?>';           
            count=jQuery("#phpVar"+id).val(); 
            for(var i=0; i<count; i++){ 
                jQuery("#textfield"+id+i+"_message").empty();       
                var textfield_type=jQuery('#textfield_type'+id+i).val();        
                if(textfield_type=='number'){  
                    jQuery('#textfield'+id+i).keypress(function(e) {
                        var verified = (e.which == 8 || e.which == undefined || e.which == 0) ? null : String.fromCharCode(e.which).match(/[^0-9]/);
                        if (verified) {e.preventDefault();}
                    });
                }
            }
        });
    </script>
    <?php
}
add_shortcode('competition-form','form');

// function display($atts){
//     global $wpdb;
//     extract( shortcode_atts( array(        
//         'slug' => '',        
//     ), $atts ) );
//     if(isset($_POST['display'])): 
//         $option=get_option( 'my_option_name' );
//         $value="SELECT * from wp_competition_table where c_slug='".$atts[0]."'"; 
//         $value=$wpdb->get_row($value);
//             $textfield='';$textarea='';$ddfield='';$radiobutton='';$checkbox='';
//             $sql = "SELECT Textfields,Textareas,Dropdowns,Radiobuttons,Checkboxes from wp_competition_data where parent_id='".$value->id."'";
//             $result=$wpdb->get_results($sql);
//             if($result){
//                 for($j=0;$j<count($result);$j++){
                   
//                     if($result[$j]->Textfields)
//                     {
//                         $textfield.=  ($j==0) ? $result[$j]->Textfields: '<br>'.$result[$j]->Textfields;                        
//                     }
                    
//                     if($result[$j]->Textareas)
//                     {
//                         $textarea.=  ($j==0) ? $result[$j]->Textareas : '<br>'.$result[$j]->Textareas;               

//                     }                     
//                     if($result[$j]->Dropdowns)
//                     {
//                         $ddfield.=  ($j==0) ? $result[$j]->Dropdowns : '<br>'.$result[$j]->Dropdowns;                         
//                     } 
//                     if($result[$j]->Radiobuttons)
//                     {
//                         $radiobutton.=  ($j==0) ? $result[$j]->Radiobuttons : '<br>'.$result[$j]->Radiobuttons;                         
//                     }  
//                     if($result[$j]->Checkboxes)
//                     {
//                         $checkbox.=  ($j==0) ? $result[$j]->Checkboxes : '<br>'.$result[$j]->Checkboxes;                         
//                     }                   
//                 }
//                 $textfield.='<br/>';$textarea.='<br/>';$ddfield.='<br/>';$radiobutton.='<br/>';$checkbox.='<br/>';           
//                 printf('%s',$textfield);
//                 printf('%s',$textarea);
//                 printf('%s',$ddfield);
//                 printf('%s',$radiobutton);
//                 printf('%s',$checkbox); 
//             }        
//         else{
//             echo 'No records in database';
//         }
//     endif;
// }
// add_shortcode('display-data','display');