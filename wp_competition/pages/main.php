<?php
global $wpdb;

global $fm_MEMBERS_EXISTS;

//ADD NEW
//for now just add blank forms for 'Add New'
if(isset($_POST['fm-add-new'])){	
	global $wpdb;
	$q = "SELECT * from wp_competition_table ORDER BY id DESC LIMIT 1";
	$result=$wpdb->get_row($q);	
	if($result==0){
		$wpdb->insert('wp_competition_table', array('id' => 1,'c_name' =>'New Competition', 'c_slug'=>'form-1'));		
	}	
	else{ 
		$intID = (int)$result->id;
		$nextID = $intID + 1;
		$slug="form-".$nextID;
		$wpdb->insert('wp_competition_table', array('id' => $nextID,'c_name' =>'New Competition', 'c_slug'=>$slug));		
	}
}	
//APPLY ACTION
if(isset($_POST['fm-doaction'])){	
	if($_POST['fm-action-select'] == "delete"){		
		$fList = getFormList();
		$deleteIds = array();
		foreach($fList as $form){
			if(isset($_POST['fm-checked-'.$form->id])) $deleteIds[] = $form->id;
		}		
		if(sizeof($deleteIds)>0) $currentDialog = "verify-delete";
	}
}

//SINGLE DELETE
if(isset($_POST['fm-action']) && $_POST['fm-action'] == "delete"){
	$deleteIds = array();
	$deleteIds[0] = $_POST['fm-id'];
	$currentDialog = "verify-delete";
}

//VERIFY DELETE
if(isset($_POST['fm-delete-yes'])){
	global $wpdb;	
	$index=0;

	while(isset($_POST['fm-delete-id-'.$index])){			
		$data="DELETE FROM  wp_competition_data where parent_id ='".$_POST['fm-delete-id-'.$index]."'";
		$items="DELETE FROM  wp_competition_items where id = '".$_POST['fm-delete-id-'.$index]."'";
		$table="DELETE FROM wp_competition_table WHERE id = '".$_POST['fm-delete-id-'.$index]."'";	
	    $wpdb->get_results($data);
		$wpdb->get_results($items);
		$wpdb->get_results($table);
		$index++;
	}
}
// DISPLAY

 $formList = getFormList();
 function getFormList(){ 
 		global $wpdb;	 	
		$q = "SELECT * FROM wp_competition_table WHERE id >= 0 ORDER BY id ASC";
		$results = $wpdb->get_results($q);
		$formList=array();
		foreach ( $results as $row ){
			$formList[]=$row;		
		}		
		return $formList;		
	}
?>

<?php
// FORM EDITOR 
// VERIFY DELETE ////////////////////////////////////////////////////////////

if($currentDialog == "verify-delete"):?>
<form name="fm-main-form" id="fm-main-form" action="" method="post">
<div class="wrap">
	<div id="icon-edit-pages" class="icon32"></div>
	<h2 style="margin-bottom:20px"><?php _e("Competition", 'competition-form');?></h2>
	<div class="form-wrap">
		<h3><?php _e("Are you sure you want to delete:", 'competition-form');?> </h3>
	
		<ul style="list-style-type:disc;margin-left:30px;">
		<?php
		foreach($formList as $form){
			if(in_array($form->id, $deleteIds, true)){
			echo "<li>".$form->c_name."</li>";	
			}
		}
		?>
		</ul>
		
		<br />
		<?php $index=0; foreach($deleteIds as $id): ?>
			<input type="hidden" value="<?php echo $id;?>" name="fm-delete-id-<?php echo $index++;?>" />
		<?php endforeach; ?>
		<input type="submit" value="<?php _e("Yes", 'competition-form');?>" name="fm-delete-yes" />
		<input type="submit" value="<?php _e("Cancel", 'competition-form');?>" name="fm-delete-cancel"  />
	</div>
</div>
</form>
<?php

/////////////////////////////////////////////////////////////////////////////
// MAIN EDITOR //////////////////////////////////////////////////////////////

else: ?>


<form name="fm-main-form" id="fm-main-form" action="" method="post">
	<div class="wrap">
		<div id="icon-edit-pages" class="icon32"></div>		
		
		<h2 style="margin-bottom:20px"><?php _e("Competitions", 'competition-form');?>
			<input type="submit" class="button-secondary" name="fm-add-new" value="<?php _e("Add New", 'competition-form');?>" />		
		</h2>
		
		<?php if(sizeof($formList)>0): ?>
		<div class="tablenav">
		
			<div class="alignleft actions">
				<select name="fm-action-select">
				<option value="-1" selected="selected"><?php _e("Bulk Actions", 'competition-form');?></option>
				<option value="delete"><?php _e("Delete", 'competition-form');?></option>
				</select>
				<input type="submit" value="<?php _e("Apply", 'competition-form');?>" name="fm-doaction" id="fm-doaction" class="button-secondary action" />
			</div>
				
			<div class="clear"></div>
		</div>		

		<table class="widefat post fixed">
			<thead>
			<tr>
				<th scope="col" class="manage-column column-cb check-column">&nbsp;</th>
				<th><?php _e("Name", 'competition-form');?></th>
				<th><?php _e("Shortcode", 'competition-form');?></th>	
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th scope="col" class="manage-column column-cb check-column">&nbsp;</th>
				<th><?php _e("Name", 'competition-form');?></th>
				<th><?php _e("Shortcode", 'competition-form');?></th>	
			</tr>
			</tfoot>
			<?php foreach($formList as $form):?>
				<tr class="alternate author-self status-publish iedit">
					<td><input type="checkbox" name="fm-checked-<?php echo $form->id;?>"/></td>
					<td class="post-title column-title">
						<strong><a class="row-title" href="<?php echo get_admin_url(null, 'admin.php')."?page=add-competition&id=".$form->id;?>"><?php echo $form->c_name;?></a></strong>						
						<div class="row-actions">
						<?php if(!$fm_MEMBERS_EXISTS): ?>
							<span class='edit'>
							<a href="<?php echo get_admin_url(null, 'admin.php')."?page=add-competition&sec=design&id=".$form->id;?>" title="<?php _e("Edit this form", 'competition-form');?>"><?php _e("Edit", 'competition-form');?></a> | 
							<a href="#" title="<?php _e("Delete this form", 'competition-form');?>" onClick="fm_deleteFormClick('<?php echo $form->id;?>');return false"><?php _e("Delete", 'competition-form');?></a>
							</span>
						<?php else: ?>
							<span class='edit'>
							<?php $editOptions = array(); ?>
							<?php							
								$editOptions[] = "<a href=\"".get_admin_url(null, 'admin.php')."?page=add-competition&sec=design&id=".$form->id."\" title=\"".__("Edit this form", 'competition-form')."\">".__("Edit", 'competition-form')."</a>";						
								$editOptions[] = "<a href=\"#\" title=\"".__("Delete this form", 'competition-form')."\" onClick=\"fm_deleteFormClick('".$form->id."');return false\">".__("Delete", 'competition-form')."</a>";								
							echo implode("&nbsp;|&nbsp;", $editOptions);
							?>
							</span>
						</div>
						<?php endif; ?>
					</td>
					<td><?php echo '[competition-form '.$form->c_slug.']';?></td>
				</tr>
			<?php endforeach; ?>			
			<input type="hidden" value="" id="fm-action" name="fm-action"/>
			<input type="hidden" value="" id="fm-id" name="fm-id"/>
		</table>	
	<?php else: ?><?php  _e(" No competition forms.", 'competition-form');?><?php endif; ?>
	</div>
</form>
<?php endif; //end if main editor ?>
<script>
	function fm_deleteFormClick(formid){
		document.getElementById('fm-action').value = "delete";
		document.getElementById('fm-id').value = formid;			
		document.getElementById('fm-main-form').submit();
	}
</script>