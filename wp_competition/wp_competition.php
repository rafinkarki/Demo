<?php
  /*
  Plugin Name: WP Competition
  Plugin URI: http://andmine.com/
  Description: Simple competition plugin with dynamic form field using shortcode.
  Version: 0.1.0
  Author: Andmine Plugin
  Author URI: http://andmine.com/
  */
define('WP_DEBUG', true);
session_start();
$_SESSION = array();
include("simple-php-captcha.php");               
$_SESSION['captcha'] = simple_php_captcha();                 
include( plugin_dir_path( __FILE__ ) . 'manipulate.php');
include( plugin_dir_path( __FILE__ ) . 'shortcodes.php');
add_action( 'wp_enqueue_scripts', 'ajax_test_enqueue_scripts' );
function ajax_test_enqueue_scripts() {   
   wp_register_style('bootstrap-css', plugins_url( '/css/bootstrap.css' , __FILE__ ));  
   wp_enqueue_style('bootstrap-css'); 
   wp_register_style('css', plugins_url( '/css/styles.css' , __FILE__ ));  
   wp_enqueue_style('css');    
   wp_enqueue_script('competition_crypto', plugins_url( '/js/crypto.js' , __FILE__ ) , array( 'jquery' )); 
   wp_register_script('plugin', plugins_url( '/js/plugin.js' , __FILE__ ) , array( 'jquery' ));  
   wp_enqueue_script('plugin');     
   wp_localize_script( 'plugin', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php'),'captcha_code'=>$_SESSION['captcha']['code']));  
}
function create_table() {
    global $wpdb;
    $db_name1=$wpdb->prefix . 'competition_table'; 
    $db_name2=$wpdb->prefix . 'competition_items'; 
    $db_name3=$wpdb->prefix . 'competition_data'; 
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    if($wpdb->get_var("show tables like '$db_name1'") != $db_name1) 
    {
        $sql_c = "CREATE TABLE  ".$db_name1." (
        `id` int NOT NULL AUTO_INCREMENT,        
        `c_name` varchar(40) NOT NULL,
        `c_slug` varchar(40) NOT NULL,
        `s_date` varchar(10) NOT NULL,  
        `e_date` varchar(10) NOT NULL,
        `captcha` int NOT NULL, 
        `captcha_label` varchar(50) NOT NULL, 
        `no_of_fields` int NOT NULL, 
        `form_html` varchar(10000) NOT NULL,  
        `modified_form` varchar(10000) NOT NULL,  
        PRIMARY KEY id (id)
        );";         
        dbDelta($sql_c);    
    } 
   
    if($wpdb->get_var("show tables like '$db_name2'") != $db_name2) 
    {
        $sql_i = "CREATE TABLE  ".$db_name2." (
        `id` int NOT NULL,
        `field_id` int,
        `type` varchar(20) NOT NULL,
        `label` varchar(50) NOT NULL,
        `placeholder` varchar(60) NOT NULL,
        `extra` varchar(500) NOT NULL,
        `required` int NOT NULL,        
        `sort_order` int NOT NULL,       
        `field_index` int NOT NULL, 
         PRIMARY KEY field_id (field_id),
         FOREIGN KEY (id)
         REFERENCES ".$db_name1." (id)
        );";         
        dbDelta($sql_i);    
    } 
    
    if($wpdb->get_var("show tables like '$db_name3'") != $db_name3) 
    {
       $sql_d= "CREATE TABLE  ".$db_name3." (
        `id` int NOT NULL AUTO_INCREMENT,
        `parent_id` int NOT NULL,       
        `Textfields` varchar(400) NOT NULL,
        `Textareas` varchar(500) NOT NULL,
        `Dropdowns` varchar(200) NOT NULL,
        `Radiobuttons` varchar(200) NOT NULL,
        `Checkboxes` varchar(200) NOT NULL,
        FOREIGN KEY (parent_id)
        REFERENCES ".$db_name1." (id),       
        PRIMARY KEY id (id)
        );";         
        dbDelta($sql_d);      
    } 
}
register_activation_hook(__FILE__,'create_table');
//Back end setting registration
class MySettingsPage
{
    private $options;
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }
    public function add_plugin_page()
    {   
        
        add_object_page('Settings Admin','Competition','manage_options','my-setting-admin',array( $this, 'create_admin_page' ),plugins_url( '/images/formmanager.png', __FILE__ ));
        add_submenu_page( 'my-setting-admin', 'Competition Settings','Competition Settings','manage_options', 'add-competition',array( $this, 'create_add_competition' ));
    }
   
    public function create_add_competition()
    { 
        $this->options = get_option( 'my_option_name' );?>
        <div class="wrap">
            <?php
            if($_REQUEST['id']==NULL):
                $title=__('Add Competition','competition');
            else:
                $title=__('Edit Competition','competition');
            endif;?>           
            <form method="post" action="options.php">
             <h2><?php echo esc_attr($title);?></h2>          
            <?php
                settings_fields( 'my_option_group' );   
                do_settings_sections( 'my-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php       
    }
    public function create_admin_page()
    {       
        include 'pages/main.php'; 
    }
    public function page_init()
    {  
                
        wp_enqueue_style('admin-css', plugins_url( '/css/admin.css' , __FILE__ ), false, '1.0.0'); 
        wp_enqueue_style('competition_ui-css', plugins_url( '/css/jquery-ui.css' , __FILE__ ), false, '1.0.0'); 
        wp_enqueue_script('competition_ui-js', plugins_url( '/js/jquery-ui.js' , __FILE__ ) , array( 'jquery' ));  
        wp_enqueue_script('competition_admin', plugins_url( '/js/admin.js' , __FILE__ ) , array( 'jquery' ));
        register_setting('my_option_group','my_option_name',array( $this, 'sanitize' ));
        add_settings_section('heading_id','Competition Name',array( $this, 'competition_name' ),'my-setting-admin');  
        add_settings_section('setting_section_id','General Settings',array( $this, 'print_section_info' ),'my-setting-admin'); 
        if($_REQUEST['id']!=NULL){     
            add_settings_section('form_section_id','Form Settings',array( $this, 'form_info' ),'my-setting-admin');  
            add_settings_field('form','Display Form Fields',array( $this, 'form_callback' ),'my-setting-admin','form_section_id'); 
            add_settings_field('html','Form Html code',array( $this, 'form_html' ),'my-setting-admin','form_section_id'); 
        }
        add_settings_field('s_date','Start Date',array( $this, 's_date_callback' ),'my-setting-admin','setting_section_id');
        add_settings_field('e_date','End Date',array( $this, 'e_date_callback' ),'my-setting-admin','setting_section_id');          
    }
    public function sanitize( $input )
    {      
        global $wpdb;
            
        $textfield=[];$data=[];
        $textfield_checkbox=[];
        $new_input = array();
        
        if( isset( $input['c_title'] ) )
            $new_input['c_title'] = sanitize_text_field( $input['c_title'] );
        if( isset( $input['s_date'] ) )
            $new_input['s_date'] = sanitize_text_field( $input['s_date'] );            
        if( isset( $input['e_date'] ) )
            $new_input['e_date'] = sanitize_text_field( $input['e_date'] );
        if( isset( $input['captcha_checkbox'] ) )
            $new_input['captcha_checkbox'] = $input['captcha_checkbox'] ;
        if( isset( $input['captcha'] ) )
            $new_input['captcha'] =mysql_real_escape_string($input['captcha']) ;

        //Register form into database
        if($input['id']==NULL){
            global $wpdb;
            $q = "SELECT * from wp_competition_table ORDER BY id DESC LIMIT 1";
            $result=$wpdb->get_row($q); 
            if($result==0){
                $wpdb->insert('wp_competition_table', array('id' => 1,'c_name' =>$new_input['c_title'], 'c_slug'=>'form-1','s_date'=>$new_input['s_date'],'e_date'=>$new_input['e_date'],'captcha'=>$new_input['captcha_checkbox'], 'captcha_label'=>$new_input['captcha']));       
            }   
            else{ 
                $intID = (int)$result->id;
                $nextID = $intID + 1;
                $slug="form-".$nextID;
                if(!empty($input['c_title']))
                $wpdb->insert('wp_competition_table', array('id' => $nextID,'c_name' =>$new_input['c_title'], 'c_slug'=>$slug,'s_date'=>$new_input['s_date'],'e_date'=>$new_input['e_date'],'captcha'=>$new_input['captcha_checkbox'],'captcha_label'=>$new_input['captcha']));        
            } 
                
        }
        else{
            $fields_num=count($input['textfield'])+count($input['textarea'])+count($input['ddfield'])+count($input['radiobutton'])+count($input['checkbox']);
            if($new_input['captcha_checkbox']==1) ++$fields_num;
            $no_of_fields=$this->getItems($input['id']);
            $new_input['no_of_fields']=$no_of_fields[5];

            if(!empty($input['c_title']))
                $this->updateItems($new_input['c_title'], $new_input['s_date'], $new_input['e_date'],$new_input['captcha_checkbox'],$new_input['captcha'],$fields_num,$input['form_textarea'],$input['id']);
            //for dynamic form element    
            //Text field sanitization
            if( isset( $input['textfield_id'] ) ){
                $textfield_id=$input['textfield_id']; 
                $x=0;                
                foreach($textfield_id as $c=>$tid){ 
                    $new_input['textfield_id'.$x] = sanitize_text_field($tid);
                    $x++;
                }
            }
            if( isset( $input['textfield_type'] ) ){
                $textfield_type=$input['textfield_type']; 
                $x=0;                
                foreach($textfield_type as $c=>$tt){ 
                    $new_input['textfield_type'.$x] = sanitize_text_field($tt);
                    
                    $x++;

                }
            }
            if( isset( $input['textfield'] ) ){
                $textfield=$input['textfield'];            
                $count=count($input['textfield']);
                $new_input['count_textfield']=$count;
                foreach($textfield as $c=>$text){
                    $new_input['textfield'.$c] = sanitize_text_field($text);
                                 
                }
               
            }
            if( isset( $input['textfield_placeholder'] ) ){
                $textfield_placeholder=$input['textfield_placeholder'];      
                foreach($textfield_placeholder as $p=>$tp){
                    $new_input['textfield_placeholder'.$p] = sanitize_text_field($tp);
                }
            }
            if( isset( $input['textfield_checkbox'] ) ){
                $textfield_checkbox=$input['textfield_checkbox']; 
                $x=0;           
                foreach($textfield_checkbox as $c=>$t_c){  
                    if($textfield_checkbox!=NULL){        
                        $new_input['textfield_checkbox'.$x] = $t_c ;  
                        $x++;
                    }             
                }
            }
            //Textarea field sanitization
            if( isset( $input['textarea_id'] ) ){
                $textarea_id=$input['textarea_id']; 
                $x=0;                
                foreach($textarea_id as $c=>$tid){ 
                    $new_input['textarea_id'.$x] = sanitize_text_field($tid);
                    $x++;
                }
            }
            if( isset( $input['textarea'] ) ){
                $textarea=$input['textarea'];            
                $count=count($input['textarea']);
                $new_input['count_textarea']=$count;
                foreach($textarea as $c=>$ta){
                    $new_input['textarea'.$c] = sanitize_text_field($ta);
                }
            }
            if( isset( $input['textarea_placeholder'] ) ){
                $textarea_placeholder=$input['textarea_placeholder']; 
                foreach($textarea_placeholder as $tp=>$ttp){
                    $new_input['textarea_placeholder'.$tp] = sanitize_text_field($ttp);
                }
            }
            if( isset( $input['textarea_checkbox'] ) ){
                $textarea_checkbox=$input['textarea_checkbox'];  
                $x=0;          
                foreach($textarea_checkbox as $c=>$t_c){ 
                    if($textarea_checkbox!=NULL){                   
                        $new_input['textarea_checkbox'.$x] = $t_c ;               
                        $x++;    
                    }
                }
            }
            //Dropdown field sanitization
            if( isset( $input['ddfield_id'] ) ){
                $ddfield_id=$input['ddfield_id']; 
                $x=0;                
                foreach($ddfield_id as $c=>$did){ 
                    $new_input['ddfield_id'.$x] = sanitize_text_field($did);
                    $x++;
                }
            }
            if( isset( $input['ddfield'] ) ){
                $ddfield=$input['ddfield'];            
                $count=count($input['ddfield']);
                $new_input['count_ddfield']=$count;
                foreach($ddfield as $c=>$dd){
                    $new_input['ddfield'.$c] = sanitize_text_field($dd);
                }
            }
            if( isset( $input['ddfield_option'] ) ){
                $dd_option_array=$input['ddfield_option'] ;         
                $count=count($dd_option_array);
                $new_input['count_ddfield_option']=$count;
               foreach($dd_option_array as $c=>$dd){
                    $new_input['ddfield_option'.$c]= wp_kses_post($dd);
                } 
            }
            
            //Radiobutton field sanitization
            if( isset( $input['radiobutton_id'] ) ){
                $radio_id=$input['radiobutton_id']; 
                $x=0;                
                foreach($radio_id as $c=>$rid){ 
                    $new_input['radiobutton_id'.$x] = sanitize_text_field($rid);
                    $x++;
                }
            }
            if( isset( $input['radiobutton'] ) ){
                $radiobutton=$input['radiobutton'];            
                $count=count($input['radiobutton']);
                $new_input['count_radiobutton']=$count;
                foreach($radiobutton as $c=>$rd){
                    $new_input['radiobutton'.$c] = sanitize_text_field($rd);
                }
            }
            if( isset( $input['radiobutton_option'] ) ){
                $rd_option_array=$input['radiobutton_option'] ;         
                $count=count($rd_option_array);
                $new_input['count_radiobutton_option']=$count;
               foreach($rd_option_array as $c=>$rd){
                    $new_input['radiobutton_option'.$c]= wp_kses_post($rd);
                } 
            }
             //Checkbox field sanitization
            
             if( isset( $input['checkbox_id'] ) ){
                $check_id=$input['checkbox_id']; 
                $x=0;                
                foreach($check_id as $c=>$cid){ 
                    $new_input['checkbox_id'.$x] = sanitize_text_field($cid);
                    $x++;
                }
            }
            if( isset( $input['checkbox'] ) ){
                $checkbox=$input['checkbox'];            
                $count=count($input['checkbox']);
                $new_input['count_checkbox']=$count;
                foreach($checkbox as $c=>$cb){
                    $new_input['checkbox'.$c] = sanitize_text_field($cb);
                }
            }
            if( isset( $input['checkbox_option'] ) ){
                $cb_option_array=$input['checkbox_option'] ;         
                $count=count($cb_option_array);
                $new_input['count_checkbox_option']=$count;
               foreach($cb_option_array as $c=>$cb){
                    $new_input['checkbox_option'.$c]= wp_kses_post($cb);
                } 
            }
            if( isset( $input['checkbox_checkbox'] ) ){
                $checkbox_checkbox=$input['checkbox_checkbox'];  
                $x=0;          
                foreach($checkbox_checkbox as $c=>$cb_c){   
                    if($checkbox_checkbox!=NULL){                         
                        $new_input['checkbox_checkbox'.$x] = $cb_c ;  
                        $x++;     
                    }        
                }
            }
           
            $sql=("SELECT count(*) as total from wp_competition_items where id=".$input['id']."");
            $t_i=$wpdb->get_results($sql);
            $this->insertItems($input,$new_input);
            if($input['delete_field']){
                $delete_id=explode(",",$input['delete_field']);
                foreach($delete_id as $did):
                     $wpdb->query("Delete from wp_competition_items WHERE field_id=".$did." and id=".$input['id']."");
                endforeach;
            }
            $id_ary = explode(",",$input['row_order']);
            for($i=0;$i<count($id_ary);$i++) {
                $x=$i;  
                $wpdb->query("UPDATE wp_competition_items SET sort_order=" . $i . " WHERE field_index=". $id_ary[$x]." and id=".$input['id']."");
            } 
        }
       
        return $new_input;

       
    }
    public function sort_id($id){
        global $wpdb;  $sort_id="";
        $tb_name=$wpdb->prefix.'competition_items';
        $sql= "SELECT MAX(field_index) as sid FROM $tb_name where id=".$id.""; 
        $result=$wpdb->get_results($sql);
        if($result[0]->sid==NULL){
            $sort_id=0;
        }
        else{
            $sort_id=$result[0]->sid;
            $sort_id++;
        }
        return $sort_id;
        
    }
    public function insertItems($input,$new_input){
        global $wpdb; $temp=[];
        $val=get_option( "my_option_name" ); 
        $textfield=$input['textfield'];
        $textfield_placeholder=$input['textfield_placeholder'];
        $textfield_checkbox=$input['textfield_checkbox'];
        $textarea=$input['textarea'];
        $textarea_placeholder=$input['textarea_placeholder'];
        $textarea_checkbox=$input['textarea_checkbox'];
        $ddfield=$input['ddfield'];
        $dd_option_array=$input['ddfield_option'] ;
        $ddfield_checkbox=$input['ddfield_checkbox'];
        $radiobutton=$input['radiobutton'];
        $rd_option_array=$input['radiobutton_option'];
        $checkbox=$input['checkbox'];
        $cb_option_array=$input['checkbox_option'] ;
        $checkbox_checkbox=$input['checkbox_checkbox'];
        $tb_name=$wpdb->prefix.'competition_items';
        array_push($temp,count($input['textfield']));array_push($temp,count($input['textarea']));array_push($temp,count($input['ddfield']));
        array_push($temp,count($input['radiobutton']));array_push($temp,count($input['checkbox']));
        $total=array_sum($temp);
        $x=0;$y=0;$z=0;$u=0;$v=0;
        $id_ary = explode(",",$input['row_order']);
        for($i=0;$i<$total;$i++){
            $type=$new_input['textfield_type'.$i]; 
            
            switch($type){
                case 'text': 
                case 'url':
                case 'email': 
                case 'number': 
                case 'password': 
                case 'date':           
                        $sort_id=$this->sort_id($input['id']);
                        $sql1 = "SELECT * FROM $tb_name WHERE id=".$input['id']." and field_id='".$new_input['textfield_id'.$x]."'"; 
                        $check1=$wpdb->get_results($sql1); 
                        if($check1){
                            $wpdb->query("UPDATE $tb_name SET id=".$input['id'].",type='".$type."', label='".mysql_real_escape_string($textfield[$x])."',placeholder='".mysql_real_escape_string($textfield_placeholder[$x])."',required=".$new_input['textfield_checkbox'.$x]." WHERE field_id=".$new_input['textfield_id'.$x]."");             
                        
                        } 
                        else{
                            $wpdb->insert($tb_name, array('id' =>$input['id'],'field_id'=>$new_input['textfield_id'.$x],'type' =>$type, 'label' => sanitize_text_field($textfield[$x]),'placeholder'=>sanitize_text_field($textfield_placeholder[$x]),'extra'=>'','required'=>$new_input['textfield_checkbox'.$x],'sort_order'=>$id_ary[$i],'field_index'=>$id_ary[$i]));  
                      
                        } 
                    $x++;
                break;
                case 'textarea':
                   
                        $sort_id=$this->sort_id($input['id']); 
                        $sql2 = "SELECT * FROM $tb_name WHERE id=".$input['id']." and field_id='".$new_input['textarea_id'.$y]."'"; 
                        $check2=$wpdb->get_results($sql2); 
                        if($check2){
                            $wpdb->query("UPDATE $tb_name SET id=".$input['id'].", label='".mysql_real_escape_string($textarea[$y])."',placeholder='".mysql_real_escape_string($textarea_placeholder[$y])."',required=".$new_input['textarea_checkbox'.$y]." WHERE field_id=".$new_input['textarea_id'.$y].""); 
                        }
                        else{
                            $wpdb->insert($tb_name, array('id' =>$input['id'],'field_id'=>$new_input['textarea_id'.$y],'type' =>$type, 'label' => sanitize_text_field($textarea[$y]),'placeholder'=>sanitize_text_field($textarea_placeholder[$y]),'extra'=>'','required'=>$new_input['textarea_checkbox'.$y],'sort_order'=>$id_ary[$i],'field_index'=>$id_ary[$i]));
                       
                        }  
                    $y++;
                break;
                case 'dropdown':
                        $sort_id=$this->sort_id($input['id']);
                        $sql3 = "SELECT * FROM $tb_name WHERE id=".$input['id']." and field_id='".$new_input['ddfield_id'.$z]."'"; 
                        $check3=$wpdb->get_results($sql3); 
                        if($check3){
                            $wpdb->query("UPDATE $tb_name SET id=".$input['id'].",label='".mysql_real_escape_string($ddfield[$z])."',placeholder='',extra='".mysql_real_escape_string($dd_option_array[$z])."' WHERE field_id=".$new_input['ddfield_id'.$z].""); 
                        }
                        else{
                            $wpdb->insert($tb_name, array('id' =>$input['id'],'field_id'=>$new_input['ddfield_id'.$z],'type' =>$type, 'label' => sanitize_text_field($ddfield[$z]),'placeholder'=>'','extra'=>$dd_option_array[$z],'required'=>0,'sort_order'=>$id_ary[$i],'field_index'=>$id_ary[$i]));  
                        }
                        $z++;
                    
                break;
                case'radiobutton': 
                   
                        $sort_id=$this->sort_id($input['id']);
                        $sql4 = "SELECT * FROM $tb_name WHERE id=".$input['id']." and field_id='".$new_input['radiobutton_id'.$u]."'"; 
                        $check4=$wpdb->get_results($sql4); 
                        if($check4){
                            $wpdb->query("UPDATE $tb_name SET id=".$input['id'].", label='".mysql_real_escape_string($radiobutton[$u])."',extra='".mysql_real_escape_string($rd_option_array[$u])."' WHERE field_id=".$new_input['radiobutton_id'.$u]."");
                        }
                        else{
                          $wpdb->insert($tb_name, array('id' =>$input['id'],'field_id'=>$new_input['radiobutton_id'.$u],'type' =>$type, 'label' => sanitize_text_field($radiobutton[$u]),'placeholder'=>'','extra'=>$rd_option_array[$u],'required'=>0,'sort_order'=>$id_ary[$i],'field_index'=>$id_ary[$i])); 
                        } 
                        $u++;
                    
                break;
                case 'checkbox':
                    
                        $sort_id=$this->sort_id($input['id']); 
                        $sql5 = "SELECT * FROM $tb_name WHERE id=".$input['id']." and field_id='".$new_input['checkbox_id'.$v]."'"; 
                        $check5=$wpdb->get_results($sql5); 
                        if($check5){
                            $wpdb->query("UPDATE $tb_name SET id=".$input['id'].", label='".mysql_real_escape_string($checkbox[$v])."',extra='".mysql_real_escape_string($cb_option_array[$v])."',required=".$new_input['checkbox_checkbox'.$v]." WHERE field_id=".$new_input['checkbox_id'.$v]."");
                        }
                        else{
                            $wpdb->insert($tb_name, array('id' =>$input['id'],'field_id'=>$new_input['checkbox_id'.$v],'type' =>$type, 'label' => sanitize_text_field($checkbox[$v]),'placeholder'=>'','extra'=>$cb_option_array[$v],'required'=>$new_input['checkbox_checkbox'.$v],'sort_order'=>$id_ary[$i],'field_index'=>$id_ary[$i]));  
                        }
                        $v++;
                   
                break;
                default:
                break;
            }
        }            
       
    }
   
    public function form_html(){
        global $wpdb;
        ob_start();
        $unique_id=$_REQUEST['id'];
        $d_form="SELECT modified_form from wp_competition_table where id='".$_REQUEST['id']."'";
        $d_form=$wpdb->get_results($d_form);
        $var=[]; $form="";
        $value=get_option( "my_option_name" );
        $textfield=$value['count_textfield'];
        $textarea=$value['count_textarea'];
        $ddfield=$value['count_ddfield'];
        $radiobutton=$value['count_radiobutton'];
        $checkbox=$value['count_checkbox'];
        $s_date=strtotime($value['s_date']);
        $e_date=strtotime($value['e_date']);        
        $captcha=$this->getItems($_REQUEST['id']);       
        array_push($var,$textfield);
        array_push($var,$textarea);
        array_push($var,$ddfield);
        array_push($var,$radiobutton);
        array_push($var,$checkbox);
        $phpvar=max($var);       
        $fieldvalues=$this->getValues($_REQUEST['id']);
        $count=count($fieldvalues);  
        $j=0;$k=0;$l=0;$m=0;$n=0;
        if($count>0 || $captcha[6]==1):
 $form.='<form id="competition-form'.$unique_id.'" method="post">'; 
 $form.='<div class="extra-field">';  
            for($i=0;$i<$count;$i++){ 
                if($fieldvalues[$i]->type=='text' ||$fieldvalues[$i]->type=='url' || $fieldvalues[$i]->type=='date'|| $fieldvalues[$i]->type=='number' || $fieldvalues[$i]->type=='password' || $fieldvalues[$i]->type=='email' ){
                    $form.='<div class="text-field">'; 
                        $textfield_type=$fieldvalues[$i]->type;                            
                        $type=($textfield_type=='password')?'password':'text';
                        $form.='<input type="hidden" value="'.$textfield_type.'"  id="textfield_type'.$unique_id.$j.'" name="textfield_type"  />';
                        $textfield_checkbox=$fieldvalues[$i]->required; 
                        $form.='<label id="textfieldlabel'.$unique_id.$j.'">'.mysql_real_escape_string($fieldvalues[$i]->label).'</label>:';
                        $form.= (($textfield_checkbox==1)?'<em> * </em>':'');
                        if($textfield_checkbox==1){ 
                            $form.='<input type="hidden" value="'.$textfield_checkbox.'"  id="textfield_req'.$unique_id.$j.'" name="textfield_req"  />';
                        }
                        $form.='<input placeholder="'.mysql_real_escape_string($fieldvalues[$i]->placeholder).'" value="" class="input" id="textfield'.$unique_id.$j.'" type="'.$type.'" name="textfield[]"/><br />';
                        $form.='<div class="alert alert-danger field_message" id="textfield'.$unique_id.$j.'_message" style="display:none;"></div>';
                    $form.='</div><br/>'; 
                    $j++; 
                }
                if($fieldvalues[$i]->type=='textarea'){
                    $form.='<div class="text-area">';
                        $textarea_checkbox=$fieldvalues[$i]->required; 
                        $form.='<label id="textarealabel'.$unique_id.$k.'">'.mysql_real_escape_string($fieldvalues[$i]->label).'</label>:';   
                        $form.=(($textarea_checkbox==1)?'<em> * </em>':'');
                        if($textarea_checkbox==1){ 
                            $form.='<input type="hidden" value="'.$textarea_checkbox.'"  id="textarea_req'.$unique_id.$k.'" name="textarea_req"  />';
                        }                            
                        $form.='<textarea class="textarea" id="textarea'.$unique_id.$k.'" name="textarea[]" placeholder="'.mysql_real_escape_string($fieldvalues[$i]->placeholder).'"></textarea><br/>';
                        $form.='<div class="alert alert-danger field_message" id="textarea'.$unique_id.$k.'_message" style="display:none;"></div>';               
                    $form.='</div><br/>';   
                    $k++;              
                }
                if($fieldvalues[$i]->type=='dropdown'){
                    $form.='<div class="dd-field">';
                        $ddfield_checkbox=$fieldvalues[$i]->required; 
                        $ddfield_array=explode(PHP_EOL,$fieldvalues[$i]->extra);  
                        $form.='<label id="ddfieldlabel'.$unique_id.$l.'">'.mysql_real_escape_string($fieldvalues[$i]->label).'</label>:'; 
                        $form.='<select id="ddfield'.$unique_id.$l.'" name="ddfield[]">';                       
                            foreach($ddfield_array as $d_a){
                                $form.='<option value="'.mysql_real_escape_string($d_a).'">'.mysql_real_escape_string($d_a).'</option>';
                            }
                        $form.='</select>';                 
                    $form.='</div><br/>'; 
                    $l++;
                }
                if($fieldvalues[$i]->type=='radiobutton'){
                    $form.='<div class="radiobutton">';                       
                        $radiobutton_array = explode("\n", $fieldvalues[$i]->extra); 
                        $form.='<label id="radiobuttonlabel'.$unique_id.$m.'">'.mysql_real_escape_string($fieldvalues[$i]->label).'</label>:';
                            foreach($radiobutton_array as $c=>$d_a){
                                if(!empty($d_a)){
                                    $form.='<input type="radio" name="radiobutton_option'.$unique_id.$m.'" value="'.mysql_real_escape_string($d_a).'" '.(($c==0)?'checked':'').'>'.mysql_real_escape_string($d_a).'';
                                }
                            }
                        $form.='<br>'; 
                    $form.='</div><br>'; 
                    $m++;
                }
                if($fieldvalues[$i]->type=='checkbox'){
                    $form.='<div class="checkbox">';
                        $checkbox_checkbox=$fieldvalues[$i]->required;
                        $checkbox_array=explode("\n",$fieldvalues[$i]->extra); 
                        $form.='<label id="checkboxlabel'.$unique_id.$n.'">'.mysql_real_escape_string($fieldvalues[$i]->label).'</label>:';   
                        $form.=(($checkbox_checkbox==1)?'<em> * </em>':'');
                        if($checkbox_checkbox==1){ 
                            $form.='<input type="hidden" value="'.$checkbox_checkbox.'"  id="checkbox_req'.$unique_id.$n.'" name="checkbox_req"/>';
                        }  
                        foreach($checkbox_array as $cb=>$cb_o):                
                            if(!empty($cb_o)){
                                $form.='<input id="checkbox'.$unique_id.$cb.'" class="input-checkbox" type="checkbox" name="checkbox'.$unique_id.$n.'" value="'.mysql_real_escape_string($cb_o).'">';
                                $form.='<label for="checkbox_checkboxes">'.mysql_real_escape_string($cb_o).'</label>';
                            }
                        endforeach;
                        $form.='<br>'; 
                        $form.='<div class="alert alert-danger field_message" id="checkbox'.$unique_id.$n.'_message" style="display:none;"></div>';
                    $form.='</div><br>'; 
                    $n++;
                }
            }
            if($captcha[6]==1){  
                
                $form.='<label id="captchalabel'.$unique_id.'">'.mysql_real_escape_string($captcha[7]).':<em> * </em></label>';
                $form.='<img class="captcha" src="' . esc_url($_SESSION['captcha']['image_src']) . '" alt="CAPTCHA code">
                        <input id="captcha_text'.$unique_id.'" type="text" maxlength="5" name="captcha" value="">';
                $form.='<div class="alert alert-danger field_message" id="captcha'.$unique_id.'_message" style="display:none;"></div><br/>';
            }
    $form.='<input type="hidden" id="phpVar'.$unique_id.'" value="'.$count.'">
            <input type="hidden" id="c_id" value="'.$_REQUEST['id'].'">
        </div><!--div extra-field--><br/>
            <div id="'.$unique_id.'" class="fbtn">
                <input type="button" value="Register" class="button_register" id="submit'.$_REQUEST['id'].'" name="submit"/>
                <input type="hidden" value="Display" class="button" id="display'.$unique_id.'" name="display"/>
                <div id="success_message'.$unique_id.'" class="alert alert-success success_message" style="display:none;"></div>
                <div id="failed_message'.$unique_id.'" class="alert alert-warning failed_message" style="display:none;"></div>
            </div>    
        </form>'; 
        endif;

    $wpdb->query("UPDATE wp_competition_table SET form_html='".$form."' WHERE id=".$_REQUEST['id']."");
    $form=stripslashes($form);?>
    <?php 
        $content = $d_form[0]->modified_form;
        $editor_id = 'cform_editor'.$unique_id;
        $setting=array('textarea_name'=>'my_option_name[form_textarea]');
        wp_editor( $content, $editor_id,$setting);
    ?>
    <input type="button" tabindex="10" id="get-html<?php echo $unique_id;?>" class="html_button" name="get-html" value="Refresh Textarea">
    <div id="dialog<?php echo $unique_id;?>" title="Confirmation Required">
      <?php _e('All changes will be lost.Are you sure?','competition');?>
    </div>
    <script>
    
    jQuery(document).ready(function() {
            var unique_id='<?php echo $unique_id;?>';
            jQuery('#dialog'+unique_id).dialog({
                autoOpen: false,
                modal: true
            });
        
        jQuery("#get-html"+unique_id).click(function(){
            var textarea_content=jQuery("textarea#cform_editor"+unique_id).val();
            if(textarea_content==""){
                var content=<?php echo json_encode($form);?>;
                jQuery("textarea#cform_editor"+unique_id).val(content);  
            }
            else{ 
                
               jQuery("#dialog"+unique_id).dialog({
                  buttons : {
                    "Refresh" : function() {
                        var content=<?php echo json_encode($form);?>;
                        jQuery("textarea#cform_editor"+unique_id).val(content); 
                        jQuery(this).dialog("close");
                    },
                    "Cancel" : function() {
                      jQuery(this).dialog("close");
                    }
                  }
                });
                jQuery("#dialog"+unique_id).dialog("open");
            }                   
         });
    });
     </script> 
     <?php  
              
    }
    public function print_section_info()
    {
       // print 'Competition dates:';
    }
     public function competition_name()
    {
        include 'pages/title.php';
    }
    public function form_info()
    {
        //print 'Competition form settings:';
    }
    public function s_date_callback()
    {   
        $result=$this->getItems($_REQUEST['id']);       
        printf(
            '<input type="date" tabindex="2" id="s_date'.$unique_id.'" name="my_option_name[s_date]" value="%s" width="300px" />',
            $result[1]
        );
        printf(
            '<input type="hidden" id="id'.$unique_id.'" name="my_option_name[id]" value="%s" />',
            $_REQUEST['id']
        );       
    }
    public function e_date_callback()
    {          
        $result=$this->getItems($_REQUEST['id']);
        printf(
            '<input type="date" tabindex="3" id="e_date'.$unique_id.'" name="my_option_name[e_date]" value="%s" />', $result[2]
        );        
    }  
    public function updateItems($c_name, $s_date, $e_date,$captcha,$c_label,$num,$html, $id)
    {   
        global $wpdb;       
        $wpdb->query("UPDATE wp_competition_table SET c_name='".$c_name."', s_date='".$s_date."', e_date='".$e_date."',captcha='".$captcha."',captcha_label='".$c_label."',no_of_fields='".$num."',modified_form='".mysql_real_escape_string($html)."' WHERE id=".$id.""); 
       
    }   
    public function getItems($id)
    {
        global $wpdb;
        $array=[];
        $sql = "SELECT * FROM wp_competition_table WHERE id=".$id."";  
        $result=$wpdb->get_results($sql);
        foreach($result as $c){ 
           $array[].=$c->c_name;
           $array[].=$c->s_date;
           $array[].=$c->e_date;           
           $array[].=$c->form_html;
           $array[].=$c->modified_form;
           $array[].=$c->no_of_fields;
           $array[].=$c->captcha;
           $array[].=$c->captcha_label;
        }    
       return $array;
    }
    public function getValues($id)
    {
        global $wpdb;
        $sql = "SELECT * FROM wp_competition_items WHERE id=".$id." ORDER BY sort_order ASC;";          
        $result=$wpdb->get_results($sql);
        return $result;
    }
    public function form_callback()
    {   
        $html=""; 
        global $wpdb; 
        $unique_id=$_REQUEST['id'];
        $fieldvalues=$this->getValues($_REQUEST['id']);
        $captcha=$this->getItems($_REQUEST['id']);
        $count=count($fieldvalues);
        $html .= '<div id="draggables'.$unique_id.'" class="draggables"><div class="button-title">Add form element</div><ul id="sortable-button'.$unique_id.'" class="button-list"><li><div class="button-field" id="textfield'.$unique_id.'">Textfield</div></li><li><div class="button-field" id="textarea'.$unique_id.'">Textarea</div></li><li><div class="button-field" id="ddfield'.$unique_id.'">Dropdown</div></li><li><div class="button-field" id="radiobutton'.$unique_id.'">Radiobutton</div></li><li><div class="button-field" id="checkbox'.$unique_id.'">Checkbox</div></li></ul></div>
        <input type = "hidden" name="my_option_name[row_order]" id="row_order'.$unique_id.'" />        
        <div class="cf-editor">
        <input type="hidden" name="my_option_name[delete_field]" id="delete_field'.$unique_id.'"/>
        <input type="hidden" name="my_option_name[unique_id]" id="unique_id" value="'.$unique_id.'"/>
        <ul id="sortable-list'.$unique_id.'" class="list form-list">';
        //Textfield
        if($count<=0)$html.='<li class="placeholder">Form elements here.</li>';
        $j=0;$k=0;$l=0;$m=0;$n=0;$f_i=-1;
        for($i=0;$i<$count;$i++){           
            if(!empty($fieldvalues[$i]->type)){                 
            $html.='<li id="'.$fieldvalues[$i]->field_index.'">';
            if($fieldvalues[$i]->type=='text' ||$fieldvalues[$i]->type=='url'|| $fieldvalues[$i]->type=='number' || $fieldvalues[$i]->type=='date'|| $fieldvalues[$i]->type=='password' || $fieldvalues[$i]->type=='email' ){
                
                $text="";$url="";$number="";$password=""; $email="";$date="";$flag=0;
                if($flag==0 && $fieldvalues[$i]->type=='text'){
                    $text="selected";$flag=1;
                }
                if($flag==0 && $fieldvalues[$i]->type=='email'){
                    $email="selected";$flag=1;
                }
                if($flag==0 && $fieldvalues[$i]->type=='url'){
                    $url="selected";$flag=1;
                }
                if($flag==0 && $fieldvalues[$i]->type=='number'){
                    $number="selected";$flag=1;
                }
                if($flag==0 && $fieldvalues[$i]->type=='date'){
                    $date="selected";$flag=1;
                }
                if($flag==0 && $fieldvalues[$i]->type=='password'){
                    $password="selected";$flag=1;
                }

                $html.='<div class="textfield-layout">
                        <input type="hidden" name="my_option_name[textfield_id][]" value="'.$fieldvalues[$i]->field_id.'"/>
                        <table class="add-field">
                            <tr>
                                <td class="label"><label class="textfield_label" for="label">'.$fieldvalues[$i]->label.'';
                                if($fieldvalues[$i]->required==1)$html.='<em> * </em>';
                                $html.=': </label></td>
                                <td class="editor-main"><input class="input" type="text" name="textfield" readonly="readonly" placeholder="'.$fieldvalues[$i]->placeholder.'"></td>
                                <td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-textfield'.$unique_id.'" class="anchor">Edit</a></td>
                                <td class="editor-item-buttons"><a href="#" class="anchor" id="remove_textfield'.$unique_id.'" data-id="'.$fieldvalues[$i]->field_id.'">Remove</a></td>
                            </tr>
                        </table>
                        <div class="content">
                            <table class="dynamic">
                                <tr class="textfield-type">
                                    <td><label for="type">Validation: </label></td>
                                    <td><select name="my_option_name[textfield_type][]"><option value="text" '.$text.'>Text</option><option value="email" '.$email.'>Email</option><option value="number" '.$number.'>Number</option><option value="url" '.$url.'>Url</option><option value="date" '.$date.'>Date(DD/MM/YYYY)</option><option value="password" '.$password.'>Password</option><select></td>
                                </tr>
                                <tr>
                                    <td><label for="check_box">Required: </label></td>
                                    <td><input type="hidden" id="checkbox'.$unique_id.$j.'" name="my_option_name[textfield_checkbox]['.$j.']"  value="0"><input class="input-checkbox" id="textfield_checkbox'.$unique_id.$j.'" type="checkbox" name="my_option_name[textfield_checkbox]['.$j.']" value="1"' . checked( 1, $fieldvalues[$i]->required, false ) . '"/></td>
                                </tr>
                                <tr>
                                    <td><label for="label">Label:</label></td>
                                    <td><input value="'.$fieldvalues[$i]->label.'" placeholder="Label" class="input" id="textfield'.$unique_id.$j.'" type="text" name="my_option_name[textfield][]" required/></td>
                                </tr>
                                <tr>
                                    <td><label for="placeholder">Placeholder:</label></td>
                                    <td><input value="'.$fieldvalues[$i]->placeholder.'" placeholder="Placeholder" class="input" id="textfield_placeholder'.$unique_id.$j.'" type="text" name="my_option_name[textfield_placeholder][]" /></td>
                                </tr>
                            </table>
                        </div>
                    </div>';
                $j++;
                $order[] = $fieldvalues[$i]->field_index;
                } //textfield check end
                if($fieldvalues[$i]->type=='textarea'){
                    
                $html.='
                    <div class="textarea-layout">
                    <input type="hidden" name="my_option_name[textarea_id][]" value="'.$fieldvalues[$i]->field_id.'"/>
                    <input type="hidden" name="my_option_name[textfield_type][]" value="'.$fieldvalues[$i]->type.'"/>
                        <table class="add-field">
                            <tr>
                                <td class="label" style="vertical-align:top;"><label class="textarea_label" for="label">'.$fieldvalues[$i]->label.'';
                                if($fieldvalues[$i]->required==1)$html.='<em> * </em>';
                                $html.=': </label></td>
                                <td class="editor-main"><textarea class="input" type="text" name="textarea-placeholder" readonly="readonly" placeholder="'.$fieldvalues[$i]->placeholder.'" rows="4" cols="20"></textarea></td>
                                <td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" id="edit-toggle-textarea'.$unique_id.'" class="anchor">Edit</a></td>
                                <td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" class="anchor" id="remove_textarea'.$unique_id.'" data-id="'.$fieldvalues[$i]->field_id.'">Remove</a></td>
                            </tr>
                        </table>
                        <div class="content">
                            <table class="dynamic">
                                <tr>
                                    <td><label for="check_box">Required: </label></td>
                                    <td><input type="hidden" id="checkbox'.$unique_id.$k.'" name="my_option_name[textarea_checkbox]['.$k.']"  value="0">
                                    <input class="input-checkbox" id="textarea_checkbox'.$unique_id.$i.'" type="checkbox" name="my_option_name[textarea_checkbox]['.$k.']" value="1"' .checked( 1,$fieldvalues[$i]->required, false ) . '"/></td>
                                </tr>                            
                                <tr>
                                    <td><label for="label">Label:</label></td>
                                    <td><input value="'.$fieldvalues[$i]->label.'" placeholder="Label" class="input" id="textarea'.$unique_id.$k.'" type="text" name="my_option_name[textarea][]" required/></td>
                                </tr>
                                <tr>
                                    <td><label for="placeholder">Placeholder:</label></td>
                                    <td><input value="'.$fieldvalues[$i]->placeholder.'" placeholder="Placeholder" class="input" id="textarea_placeholder'.$unique_id.$k.'" type="text" name="my_option_name[textarea_placeholder][]" /></td>
                                </tr>
                            </table>
                        </div>
                    </div>';  
                    $k++;
                    $order[] = $fieldvalues[$i]->field_index;
                }//textarea check end
                if($fieldvalues[$i]->type=='radiobutton'){
                    
                $radiobutton_option = explode("\n", $fieldvalues[$i]->extra); 
                $html.='
                <div class="radiobutton-layout">
                <input type="hidden" name="my_option_name[radiobutton_id][]" value="'.$fieldvalues[$i]->field_id.'"/>
                <input type="hidden" name="my_option_name[textfield_type][]" value="'.$fieldvalues[$i]->type.'"/>
                    <table class="add-field">
                        <tr>
                            <td class="label"><label class="radiobutton_label" for="label">'.$fieldvalues[$i]->label.': </label></td><td class="editor-main">';
                            foreach($radiobutton_option as $rd=>$rd_o):                
                                if($rd_o!=""){
                                    $html.='<input type="radio" name="my_option_name[radiobutton_options]['.$l.']" value="'.$rd_o.'" '.(($rd==0)? 'checked':'').' disabled>'.$rd_o.'<br>';
                                }
                            endforeach;                            
                    $html.='</td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-radio'.$unique_id.'" class="anchor">Edit</a></td>
                            <td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_radiobutton'.$unique_id.'" data-id="'.$fieldvalues[$i]->field_id.'">Remove</a></td>
                        </tr>
                    </table>
                    <div class="content">
                        <table class="dynamic">            
                            <tr>
                                <td><label for="label">Label:</label></td>
                                <td><input value="'.$fieldvalues[$i]->label.'" placeholder="Label" class="radio-label" id="radiobutton'.$unique_id.$l.'" type="text" name="my_option_name[radiobutton][]" required/></td>
                            </tr>
                            <tr>
                                <td style="vertical-align:top;"><label for="option">Options:</label></td>
                                <td><textarea class="option" placeholder="One option per line" rows="5" cols="20"  id="radiobutton_option'.$unique_id.$l.'" name="my_option_name[radiobutton_option][]" required>';            
                                $html.=( $fieldvalues[$i]->extra);              
                                $html.='</textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>';  
                $l++;
                $order[] = $fieldvalues[$i]->field_index; 
                }//radiobutton check end
                if($fieldvalues[$i]->type=='dropdown'){
                   
                $ddfield_array=explode("\n",$fieldvalues[$i]->extra); 
                $html.='
                <div class="ddfield-layout">
                <input type="hidden" name="my_option_name[ddfield_id][]" value="'.$fieldvalues[$i]->field_id.'"/>
                <input type="hidden" name="my_option_name[textfield_type][]" value="'.$fieldvalues[$i]->type.'"/>
                    <table class="add-field">
                        <tr>
                            <td class="label"><label class="ddfield_label" for="label">'.$fieldvalues[$i]->label.'';                            
                            $html.=': </label></td>
                            <td class="editor-main"><select name="ddfield-options" disabled>';
                                    foreach($ddfield_array as $d_a){
                        $html.='<option value="'.$d_a.'">'.$d_a.'</option>';
                                    }
                        $html.='</select></td>
                            <td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-ddfield'.$unique_id.'" class="anchor">Edit</a></td>
                            <td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_ddfield'.$unique_id.'" data-id="'.$fieldvalues[$i]->field_id.'">Remove</a></td>
                        </tr>
                    </table>
                    <div class="content">
                        <table class="dynamic">                                                    
                            <tr>
                                <td><label for="label">Label:</label></td>
                                <td><input value="'.$fieldvalues[$i]->label.'" placeholder="Label" class="ddfield-label" id="ddfield'.$unique_id.$m.'" type="text" name="my_option_name[ddfield][]" required/></td>
                            </tr>
                            <tr>
                                <td style="vertical-align:top;"><label for="option">Options:</label></td>
                                <td><textarea class="option" placeholder="One option per line" rows="5" cols="20"  id="ddfield_option'.$unique_id.$m.'" name="my_option_name[ddfield_option][]" required>';            
                                $html.=( $fieldvalues[$i]->extra);              
                                $html.='</textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>';
                 $m++;
                $order[] = $fieldvalues[$i]->field_index;
                }//ddfield check end
                if($fieldvalues[$i]->type=='checkbox'){
                    
                $checkbox_option = explode("\n", $fieldvalues[$i]->extra);  
                $html.='
                <div class="checkbox-layout">
                <input type="hidden" name="my_option_name[checkbox_id][]" value="'.$fieldvalues[$i]->field_id.'"/>
                <input type="hidden" name="my_option_name[textfield_type][]" value="'.$fieldvalues[$i]->type.'"/>
                    <table class="add-field">
                        <tr>
                            <td class="label"><label class="checkbox_label" for="label">'.$fieldvalues[$i]->label.'';
                            if($fieldvalues[$i]->required==1)$html.='<em> * </em>';
                            $html.=': </label></td><td class="editor-main">';
                            foreach($checkbox_option as $rd=>$cb_o):             
                                if(!empty($cb_o)){
                                    $html.='<input class="input-checkbox" type="checkbox" name="my_option_name[checkbox_checkboxes]['.$n.']" value="1" disabled/>';
                                    $html.='<label for="checkbox_checkboxes">'.$cb_o.'</label><br>';
                                }
                            endforeach;                            
                    $html.='</td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-checkbox'.$unique_id.'" class="anchor">Edit</a></td>
                            <td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_checkbox'.$unique_id.'" data-id="'.$fieldvalues[$i]->field_id.'">Remove</a></td>
                        </tr>
                    </table>
                    <div class="content">
                        <table class="dynamic"> 
                            <tr>
                                <td class="label"><label for="check_box">Required: </label></td>
                                <td><input type="hidden" id="checkbox'.$unique_id.$n.'" name="my_option_name[checkbox_checkbox]['.$n.']"  value="0"><input class="input-checkbox" id="checkbx_checkbox'.$unique_id.$n.'" type="checkbox" name="my_option_name[checkbox_checkbox]['.$n.']" value="1"' . checked( 1, $fieldvalues[$i]->required, false ).'"/></td>
                            </tr>            
                            <tr>
                                <td><label for="label">Label:</label></td>
                                <td><input value="'.$fieldvalues[$i]->label.'" placeholder="Label" class="checkbox-label" id="checkbox'.$unique_id.$n.'" type="text" name="my_option_name[checkbox][]" required/></td>
                            </tr>
                            <tr>
                                <td style="vertical-align:top;"><label for="option">Options:</label></td>
                                <td><textarea class="option" placeholder="One option per line" rows="5" cols="20"  id="checkbox_option'.$unique_id.$n.'" name="my_option_name[checkbox_option][]" required>';            
                                $html.=( $fieldvalues[$i]->extra);              
                                $html.='</textarea></td>
                            </tr>
                        </table>
                    </div>
                </div>';
                $n++;
                $order[] = $fieldvalues[$i]->field_index;  
                }//checkbox check end
            }//not empty label
            $html.='</li>';
        if($order){$f_i=   max($order);}
        }//for loop
        $html.='</ul>';
        $html.='</div>'; 
        $html.='
        <div class="captcha-field">
            <table class="captcha">
                <tr class="captcha_checkbox">
                    <td style="width:165px;"><label for="captcha_label">'.(($captcha[7]=="")?'Captcha':$captcha[7]).'<em> * </em>: </label></td>
                    <td class="editor-main"><label for="captcha_here">(CAPTCHA field)</label></td>
                    <td><input type="checkbox" tabindex="4" id="captcha_checkbox'.$unique_id.'" name="my_option_name[captcha_checkbox]" value="1"' . checked( 1, $captcha[6], false ) . '"/></td>
                    <td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-captcha'.$unique_id.'" class="anchor">Edit</a></td>                     
                </tr>
            </table>
            <div class="content">
                <table class="dynamic"> 
                                
                    <tr>
                        <td><label for="label">Label:</label></td>
                        <td><input value="'.(($captcha[7]=="")?'Captcha':$captcha[7]).'" placeholder="Label" class="captcha-label" id="captcha'.$unique_id.'" type="text" name="my_option_name[captcha]" required/></td>
                    </tr>
                    
                </table>
            </div>
        </div>';
        if($_REQUEST['id']!=NULL)
        echo $html;  
       
    ?>
        <script>       
            jQuery(document).ready(function(){
               var unique_id='<?php echo $unique_id;?>'; 
               var wrapper_textfield = jQuery(".textfield"); 
               var wrapper_formlist = jQuery(".form-list"); 
               var wrapper_textarea = jQuery(".textarea"); 
               var wrapper_urlfield = jQuery(".urlfield"); 
               var wrapper_ddfield = jQuery(".ddfield"); 
               var wrapper_radiobutton = jQuery(".radiobutton"); 
               var wrapper_checkbox= jQuery(".checkbox");
               var textfield = jQuery("#textfield"+unique_id);
               var textarea = jQuery("#textarea"+unique_id);  
               var urlfield = jQuery("#urlfield"+unique_id); 
               var ddfield = jQuery("#ddfield"+unique_id);
               var radiobutton = jQuery("#radiobutton"+unique_id); 
               var checkbox = jQuery("#checkbox"+unique_id);                     
               var x='<?php echo $j;?>';            
               var y='<?php echo $k;?>'; 
               var z='<?php echo $m;?>';
               var u='<?php echo $l;?>'; 
               var v='<?php echo $n;?>';               
               var delete_id = [];
               var field_id='<?php echo $f_i;?>';
               var temp=field_id;
               var count=[];
               var temp_x=x;var temp_y=y; var temp_z=z;var temp_u=u;var temp_v=v;
               //Textfield           
               
                jQuery(textfield).click(function(e){  
                    e.preventDefault();                     
                    var rand =  Math.floor(Math.random() * 1000);
                    jQuery(".placeholder" ).remove();
                    
                    while(field_id<=temp){++field_id;}
                    while(x<temp_x){++x}
                    jQuery(".form-list").append('<li id="'+field_id+'"><div class="textfield-layout"><input id="textfield_id'+unique_id+x+'" type="hidden" name="my_option_name[textfield_id][]" value="'+rand+'"/><table class="add-field"><tr><td class="label"><label for="textfield-name">New Textfield:</td><td class="editor-main"><input class="input" type="text" name="textfield" readonly="readonly"></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-textfield'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="#" class="anchor" id="remove_textfield'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr class="textfield-type"><td><label for="type">Validation: </label></td><td><select name="my_option_name[textfield_type][]"><option value="text">Text</option><option value="email">Email</option><option value="number">Number</option><option value="url">Url</option><option value="date">Date(DD/MM/YYYY)</option><option value="password">Password</option><select></td></tr><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+x+'" name="my_option_name[textfield_checkbox]['+x+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+x+' " type="checkbox" name="my_option_name[textfield_checkbox]['+x+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input placeholder="Label" class="input" id="textfield'+unique_id+x+' " type="text" name="my_option_name[textfield][]" value="New Textfield" required/></td></tr><tr><td><label for="placeholder">Placeholder:</label></td><td><input value="" placeholder="Placeholder" class="input" id="textfield_placeholder'+unique_id+x+' " type="text" name="my_option_name[textfield_placeholder][]" /></td></tr></table></div></div></li>');         
                    ++x;
                    temp=field_id;
                });
                jQuery(wrapper_formlist).on("click","#remove_textfield"+unique_id, function(e){ 
                    e.preventDefault(); jQuery(this).closest('li').remove();--x; --field_id;
                    delete_id.push(jQuery(this).attr('data-id'));
                    addFiledToHidden();
                });
                jQuery(wrapper_formlist).on("click","#edit-toggle-textfield"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");                     
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                //Textarea field
                jQuery(textarea).click(function(e){                     
                    e.preventDefault();
                    var rand =  Math.floor(Math.random() * 1000); 
                    jQuery(".placeholder" ).remove();  
                    
                    while(field_id<=temp){++field_id;}
                    while(y<temp_y){++y}                                           
                    jQuery(".form-list").append('<li id="'+field_id+'"><div class="textarea-layout"><input type="hidden" name="my_option_name[textarea_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="textarea"/><table class="add-field"><tr><td class="label" style="vertical-align:top;"><label for="textarea-name">New Textarea:</label></td><td class="editor-main"><textarea value="" class="input" name="textarea-field" required rows="4" cols="20" readonly="readonly"></textarea></td><td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" id="edit-toggle-textarea'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" class="anchor" id="remove_textarea'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+y+'" name="my_option_name[textarea_checkbox]['+y+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+y+' " type="checkbox" name="my_option_name[textarea_checkbox]['+y+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input placeholder="Label" class="input" id="textarea'+unique_id+y+'" type="text" name="my_option_name[textarea][]" value="New Textarea" required/></td></tr><tr><td><label for="placeholder">Placeholder:</label></td><td><input value="" placeholder="Placeholder" class="input" id="textarea_placeholder'+unique_id+y+' " type="text" name="my_option_name[textarea_placeholder][]" /></td></tr></table></div></div></li>');
                    ++y;temp=field_id;
                });
                jQuery(wrapper_formlist).on("click","#remove_textarea"+unique_id, function(e){
                    e.preventDefault(); jQuery(this).closest('li').remove();--y;--field_id;
                    delete_id.push(jQuery(this).attr('data-id'));
                    addFiledToHidden();
                });
                jQuery(wrapper_formlist).on("click","#edit-toggle-textarea"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                //Dropdown
                jQuery(ddfield).click(function(e){                     
                    e.preventDefault(); 
                    var rand =  Math.floor(Math.random() * 1000); 
                    jQuery(".placeholder" ).remove();   
                    
                    while(field_id<=temp){++field_id;}
                    while(z<temp_z){++z}                                        
                    jQuery(".form-list").append('<li id="'+field_id+'"><div class="ddfield-layout"><input type="hidden" name="my_option_name[ddfield_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="dropdown"/><table class="add-field"><tr><td class="label"><label for="ddfield-name">New Dropdown:</label></td><td class="editor-main"><select disabled="disabled"><option value="0">Option</option></select></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-ddfield'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_ddfield'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="label">Label:</label></td><td><input value="New Dropdown" placeholder="Dropdown Field Label" class="ddfield-label" id="ddfield'+unique_id+z+' " type="text" name="my_option_name[ddfield][]" required/></td></tr><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One option per line" rows="5" cols="20" id="ddfield_option'+unique_id+z+'" name="my_option_name[ddfield_option][]" required></textarea></td></tr></table></div></div></li>'); 
                    ++z;temp=field_id;
                });
                jQuery(wrapper_formlist).on("click","#remove_ddfield"+unique_id, function(e){
                    e.preventDefault(); jQuery(this).closest('li').remove();--z;--field_id;
                    delete_id.push(jQuery(this).attr('data-id'));
                    addFiledToHidden();
                });
                jQuery(wrapper_formlist).on("click","#edit-toggle-ddfield"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                //Radiobutton
                jQuery(radiobutton).click(function(e){                     
                    e.preventDefault(); 
                    var rand =  Math.floor(Math.random() * 1000); 
                    jQuery(".placeholder" ).remove();  
                      
                    while(field_id<=temp){++field_id;}
                    while(u<temp_u){++u}                                         
                    jQuery(".form-list").append('<li id="'+field_id+'"><div class="radiobutton-layout"><input type="hidden" name="my_option_name[radiobutton_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="radiobutton"/><table class="add-field"><tr><td class="label"><label for="radiobutton-name">New Radiobutton:</label></td><td class="editor-main"><input type="radio" disabled="disabled"/></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-radio'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_radiobutton'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="label">Label:</label></td><td><input value="New Radiobutton" placeholder="Raiobutton Label" class="radio-label" id="radiobutton'+unique_id+u+' " type="text" name="my_option_name[radiobutton][]" required/></td></tr><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One option per line" rows="5" cols="20" id="radiobutton_option'+unique_id+u+'" name="my_option_name[radiobutton_option][]" required></textarea></td></tr></table></div></div></li>'); 
                    ++u;temp=field_id;
                });
                jQuery(wrapper_formlist).on("click","#remove_radiobutton"+unique_id, function(e){
                    e.preventDefault(); jQuery(this).closest('li').remove();--u;--field_id;
                    delete_id.push(jQuery(this).attr('data-id'));
                    addFiledToHidden();
                });
                jQuery(wrapper_formlist).on("click","#edit-toggle-radio"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                //Checkbox
                jQuery(checkbox).click(function(e){                     
                    e.preventDefault();   
                    var rand =  Math.floor(Math.random() * 1000);
                    jQuery(".placeholder" ).remove();       
                     
                    while(field_id<=temp){++field_id;}
                    while(v<temp_v){++v}                                   
                    jQuery(".form-list").append('<li id="'+field_id+'"><div class="checkbox-layout"><input type="hidden" name="my_option_name[checkbox_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="checkbox"/><table class="add-field"><tr><td class="label"><label for="checkbox-name">New Checkbox:</label></td><td class="editor-main"><input type="checkbox" disabled="disabled"/></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-checkbox'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_checkbox'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+v+'" name="my_option_name[checkbox_checkbox]['+v+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+v+' " type="checkbox" name="my_option_name[checkbox_checkbox]['+v+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input value="New Checkbox" placeholder="Checkbox Label" class="checkbox-label" id="checkbox'+unique_id+v+' " type="text" name="my_option_name[checkbox][]" required/></td></td><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One item per line" rows="5" cols="20" id="checkbox_option'+unique_id+v+'" name="my_option_name[checkbox_option][]" required></textarea></td></tr></table><div></div></li>'); 
                    ++v;temp=field_id;
                });
                jQuery(wrapper_formlist).on("click","#remove_checkbox"+unique_id, function(e){
                    e.preventDefault(); jQuery(this).closest('li').remove();--v;--field_id;
                    delete_id.push(jQuery(this).attr('data-id'));
                    addFiledToHidden();
                });
                jQuery(wrapper_formlist).on("click","#edit-toggle-checkbox"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                jQuery(".captcha-field").on("click","#edit-toggle-captcha"+unique_id, function(e){
                    e.preventDefault(); 
                    jQuery(this).closest('div').children('.content').slideToggle("toggled");
                    jQuery(this).html()== "Edit" ? jQuery(this).html('Hide') : jQuery(this).html('Edit');
                });
                jQuery("#edit-toggle"+unique_id).click(function(e) {
                    e.preventDefault();
                    jQuery("#content"+unique_id).show();
                });

                //for delete id                
                function addFiledToHidden(){
                    jQuery("#delete_field"+unique_id).val(delete_id);
                }
                //Sorting and dragging form element to add the elements
                jQuery('#sortable-list'+unique_id+', #sortable-button'+unique_id).sortable({
                    connectWith: ".list",
                    placeholder: 'ui-state-highlight',  
                    start: function(event, ui) {   
                        ui.placeholder.height(ui.item.height());  
                    } ,
                    tolerance: 'pointer',
                }).disableSelection();
               
                jQuery('#sortable-button'+unique_id).bind('sortstop', function(event, ui) {
                    var idx = jQuery('#sortable-list'+unique_id).children().index(jQuery(ui.item[0]))-1,
                    elm = jQuery(ui.item[0]).clone(true); 
                    console.log(idx);
                    //if(idx<0) idx=0;
                    if(idx>=-1){
                        if(idx<0) idx=0;
                        if(elm.text()=='Textfield')
                        {   var rand =  Math.floor(Math.random() * 1000); 
                            
                            while(field_id<=temp){++field_id;}
                            while(x<temp_x){++x}
                            jQuery('#sortable-list'+unique_id).children(':eq('+idx+')').after('<li id="'+field_id+'"><div class="textfield-layout"><input id="textfield_id'+unique_id+x+'" type="hidden" name="my_option_name[textfield_id][]" value="'+rand+'"/><table class="add-field"><tr><td class="label"><label for="textfield-name">New Textfield:</td><td class="editor-main"><input class="input" type="text" name="textfield" readonly="readonly"></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-textfield'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="#" class="anchor" id="remove_textfield'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr class="textfield-type"><td><label for="type">Validation: </label></td><td><select name="my_option_name[textfield_type][]"><option value="text">Text</option><option value="email">Email</option><option value="number">Number</option><option value="url">Url</option><option value="date">Date(DD/MM/YYYY)</option><option value="password">Password</option><select></td></tr><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+x+'" name="my_option_name[textfield_checkbox]['+x+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+x+' " type="checkbox" name="my_option_name[textfield_checkbox]['+x+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input placeholder="Label" class="input" id="textfield'+unique_id+x+' " type="text" name="my_option_name[textfield][]" value="New Textfield" required/></td></tr><tr><td><label for="placeholder">Placeholder:</label></td><td><input value="" placeholder="Placeholder" class="input" id="textfield_placeholder'+unique_id+x+' " type="text" name="my_option_name[textfield_placeholder][]" /></td></tr></table></div></div></li>');
                            ++x;temp=field_id;
                        }
                        if(elm.text()=='Textarea'){
                            var rand =  Math.floor(Math.random() * 1000);                             
                            while(field_id<=temp){++field_id;}
                            while(y<temp_y){++y}  
                            jQuery('#sortable-list'+unique_id).children(':eq('+idx+')').after('<li id="'+field_id+'"><div class="textarea-layout"><input type="hidden" name="my_option_name[textarea_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="textarea"/><table class="add-field"><tr><td class="label" style="vertical-align:top;"><label for="textarea-name">New Textarea:</label></td><td class="editor-main"><textarea value="" class="input" name="textarea-field" required rows="4" cols="20" readonly="readonly"></textarea></td><td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" id="edit-toggle-textarea'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons" style="vertical-align:top;"><a href="void:javascript(0)" class="anchor" id="remove_textarea'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+y+'" name="my_option_name[textarea_checkbox]['+y+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+y+' " type="checkbox" name="my_option_name[textarea_checkbox]['+y+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input placeholder="Label" class="input" id="textarea'+unique_id+y+'" type="text" name="my_option_name[textarea][]" value="New Textarea" required/></td></tr><tr><td><label for="placeholder">Placeholder:</label></td><td><input value="" placeholder="Placeholder" class="input" id="textarea_placeholder'+unique_id+y+' " type="text" name="my_option_name[textarea_placeholder][]" /></td></tr></table></div></div></li>');
                            ++y;temp=field_id;
                        }
                        if(elm.text()=='Dropdown'){
                            var rand =  Math.floor(Math.random() * 1000);
                            
                            while(field_id<=temp){++field_id;}
                            while(z<temp_z){++z} 
                            jQuery('#sortable-list'+unique_id).children(':eq('+idx+')').after('<li id="'+field_id+'"><div class="ddfield-layout"><input type="hidden" name="my_option_name[ddfield_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="dropdown"/><table class="add-field"><tr><td class="label"><label for="ddfield-name">New Dropdown:</label></td><td class="editor-main"><select disabled="disabled"><option value="0">Option</option></select></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-ddfield'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_ddfield'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="label">Label:</label></td><td><input value="New Dropdown" placeholder="Dropdown Field Label" class="ddfield-label" id="ddfield'+unique_id+z+' " type="text" name="my_option_name[ddfield][]" required/></td></tr><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One option per line" rows="5" cols="20" id="ddfield_option'+unique_id+z+'" name="my_option_name[ddfield_option][]" required></textarea></td></tr></table></div></div></li>');
                            ++z;temp=field_id;
                        }
                        if(elm.text()=='Radiobutton'){
                            var rand =  Math.floor(Math.random() * 1000);
                            
                            while(field_id<=temp){++field_id;}
                            while(u<temp_u){++u}  
                            jQuery('#sortable-list'+unique_id).children(':eq('+idx+')').after('<li id="'+field_id+'"><div class="radiobutton-layout"><input type="hidden" name="my_option_name[radiobutton_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="radiobutton"/><table class="add-field"><tr><td class="label"><label for="radiobutton-name">New Radiobutton:</label></td><td class="editor-main"><input type="radio" disabled="disabled"/></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-radio'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_radiobutton'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="label">Label:</label></td><td><input value="New Radiobutton" placeholder="Raiobutton Label" class="radio-label" id="radiobutton'+unique_id+u+' " type="text" name="my_option_name[radiobutton][]" required/></td></tr><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One option per line" rows="5" cols="20" id="radiobutton_option'+unique_id+u+'" name="my_option_name[radiobutton_option][]" required></textarea></td></tr></table></div></div></li>');
                            ++u;temp=field_id;
                        }
                        if(elm.text()=='Checkbox'){
                            var rand =  Math.floor(Math.random() * 1000); 
                            
                            while(field_id<=temp){++field_id;}
                            while(v<temp_v){++v}  
                            jQuery('#sortable-list'+unique_id).children(':eq('+idx+')').after('<li id="'+field_id+'"><div class="checkbox-layout"><input type="hidden" name="my_option_name[checkbox_id][]" value="'+rand+'"/><input type="hidden" name="my_option_name[textfield_type][]" value="checkbox"/><table class="add-field"><tr><td class="label"><label for="checkbox-name">New Checkbox:</label></td><td class="editor-main"><input type="checkbox" disabled="disabled"/></td><td class="editor-item-buttons"><a href="void:javascript(0)" id="edit-toggle-checkbox'+unique_id+'" class="anchor">Edit</a></td><td class="editor-item-buttons"><a href="void:javascript(0)" class="anchor" id="remove_checkbox'+unique_id+'" data-id="">Remove</a></td></tr></table><div class="content"><table class="dynamic"><tr><td><label for="check_box">Required: </label></td><td><input type="hidden" id="checkbox'+unique_id+v+'" name="my_option_name[checkbox_checkbox]['+v+']"  value="0"><input class="input-checkbox" id="checkbox'+unique_id+v+' " type="checkbox" name="my_option_name[checkbox_checkbox]['+v+']" value="1"/></td></tr><tr><td><label for="label">Label:</label></td><td><input value="New Checkbox" placeholder="Checkbox Label" class="checkbox-label" id="checkbox'+unique_id+v+' " type="text" name="my_option_name[checkbox][]" required/></td></td><tr><td style="vertical-align:top;"><label for="option">Options:</label></td><td><textarea class="option" placeholder="One item per line" rows="5" cols="20" id="checkbox_option'+unique_id+v+'" name="my_option_name[checkbox_option][]" required></textarea></td></tr></table><div></div></li>');
                             ++v;temp=field_id;
                        }
                        
                        jQuery(".placeholder" ).remove(); 
                    }//main if end to check index
                    jQuery(this).sortable('cancel');
                });
                jQuery( "#sortable-list"+unique_id).sortable({                          
                    revert:true,
                     update: function(event, ui) {
                      var order = [];
                       jQuery('#sortable-list'+unique_id+' li').each( function(e) {
                      order.push(  jQuery(this).attr('id') );
                      });
                       jQuery('#row_order'+unique_id).val(order);
                    }
                 
                });
                
            });
        </script><?php      
    }    
   
}
if( is_admin() )
    $my_settings_page = new MySettingsPage();