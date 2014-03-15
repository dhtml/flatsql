<?php
require_once('../ffdb/dbase.php');

$db = new Flatsql();

//create a database called school
$db->query("use dbase school");

//empty the phone table
$db->query("truncate table phone");

//insert records into a table called phone
$db->query("insert into phone(uid,first,phone) value(Null,'Tony','070603223456')");
$db->query("insert into phone(uid,first,phone) value(Null,'DHTML','08120755515')");

//select all data
$row=$db->query("select * from phone");

//loop through each row using associative array
foreach($row as $data) {
echo "<li>".$data['first'] . ' - ' . $data['phone'];
}

?>
