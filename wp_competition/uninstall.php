<?php 
global $wpdb;
$table1 = $wpdb->prefix."competition_data";
$table2 = $wpdb->prefix."competition_items";
$table3 = $wpdb->prefix."competition_table";
$wpdb->query("DROP TABLE if exists $table1");
$wpdb->query("DROP TABLE if exists $table2");
$wpdb->query("DROP TABLE if exists $table3");
?>