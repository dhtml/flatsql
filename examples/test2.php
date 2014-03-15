<?php
require_once('../ffdb/dbase.php');

$db = new Flatsql();

//create a database called school
$db->query("use dbase school");

//insert records into a table called phone
$db->query("truncate table phone");

//select all data
$row=$db->query("select * from phone");

//dump all data
echo "<pre>";
var_dump($row);
?>
