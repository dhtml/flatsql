<?php
//error_reporting(1);
require_once('../ffdb/dbase.php');

$db = new Flatsql();

//create a database called school
$db->query("use dbase school");

//insert records into a table called phone
$db->query("insert into phone('uid','first','phone') value(Null,'Tony','070603223456')");
//$db->query("insert into phone(uid,first,phone) value(Null,'Tony','070603223456')");

//select all data
$row=$db->query("select * from phone");

//dump all data
echo "<pre>";
var_dump($row);
?>
