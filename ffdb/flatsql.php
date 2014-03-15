<?php
/*
Copyright (c) 2012 Ogundipe Anthony (dhtmlextreme) <L.Plant.98@cantab.net>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and 
associated documentation files (the "Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject 
to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial
 portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN 
NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/**
 * Simple but powerful flatfile database
 * See http://www.blogs.dhtmlextreme.net/flatsql for documentation and examples
 *
 * @license http://www.opensource.org/licenses/mit-license.php
 */

define('FDPATH',dirname(__FILE__));
define( 'DS', DIRECTORY_SEPARATOR );

define('DATADIR', FDPATH.DS.'datadir'.DS);


class flatsql extends Flatfile {
var $version="1.00";

function stripspaces($str) {
	$str = preg_replace('/\s\s+/', ' ', $str);
	return $str;
}
 
function stripspaces2($str) {
	$str = str_replace(' ', '', $str);
	return $str;
}
  
function query($sql) {
$this->sql=$sql;
$sql2=$this->stripspaces(strtolower("$sql"));
if (preg_match("/use dbase/i", "$sql2")) {
	return $this->loadDbase("$sql2");
}

if (preg_match("/insert into/i", "$sql2")) {
	return $this->insertData("$sql");
}

if (preg_match("/drop table/i", "$sql2")) {
	return $this->dropTable("$sql");
}

if (preg_match("/truncate table/i", "$sql2")) {
	return $this->truncTable("$sql");
}

if (preg_match("/select/i", "$sql2")&&preg_match("/from/i", "$sql2")) {
	return $this->selectData("$sql");
}


if (preg_match("/update /i", "$sql2")) {
	return $this->updateData("$sql");
}

if (preg_match("/delete /i", "$sql2")) {
	return $this->deleteData("$sql");
}

}


function loadDbase($sql) {
$dbname=trim(str_replace("use dbase ","",$sql));

//create dbase folder
$dbdir=DATADIR."$dbname".DS;

//create dbase if not exist
if (!file_exists($dbdir)) {mkdir("$dbdir");}

//select dbase
$this->datadir = $dbdir;
}


function insertData($sql) {
$sql=trim($sql);

//get table
$i=strpos($sql,'into');
$i=strpos($sql,' ',$i+1);
$j=strpos($sql,'(');
$tbl=trim(substr($sql,$i,$j-$i));

//get field
$k=strpos($sql,')');
$field=trim(substr($sql,$j+1,$k-$j-1));

//get data
$l=strpos($sql,'(',$j+1);
$m=strpos($sql,')',$l+1);
$data=trim(substr($sql,$l+1,$m-$l-1));


//preprocess fields
$field=str_replace(Array('\'','"','`'),'',$field);
$f=explode(',',$field);
foreach($f as $k=>$v) {
$f2[$k]="'$v'";
}
$field=implode(',',$f2);
//echo "<li>".$tbl;
//echo "<li>".$field;
//echo "<li>".$data;

ob_start();
eval('$f = array('.$field.');');
eval('$d = array('.$data.');');
ob_end_clean();

//check if table has schema / create schema
$schema=$this->datadir."$tbl".'.schema';
if (!file_exists($schema)) {file_put_contents("$schema", serialize($f));}

//check schema error
if(count($f)!=count($d)) {return $this->dbError("The column count does not match.");}
	
//insert into table
$tblfile=$tbl.'.txt';
$this->newId = $this->insertWithAutoId($tblfile, 0, $d);
return $this->newId; //the last i.d that was inserted
}


function dropTable($sql) {
$tbl=trim(str_replace("drop table ","",$sql));


$lock=$this->datadir."$tbl".'.txt.lock';
$schema=$this->datadir."$tbl".'.schema';
$data=$this->datadir."$tbl".'.txt';

//delete table
$this->dropFile($lock);
$this->dropFile($schema);
$this->dropFile($data);
}

function truncTable($sql) {
$tbl=trim(str_replace("truncate table ","",$sql));
$data=$this->datadir."$tbl".'.txt';

file_put_contents($data,"");
}


function dbError($err) {
die("Flat SQL Error :<b>".$err.'</b>.<br/>'.$this->sql);
}

function dropFile($file) {
	if (file_exists($file)) {
		chmod("$file", 0755);
		unlink("$file");}
}

function deleteData($sql) {
$this->prepcmd($sql,'delete from');

$i=strpos($sql,' ');
$tbl=substr($sql,0,$i);

$compClause=NULL;

//parse the schema here
$this->parseSchema($tbl);

//where
$i=strpos($sql,'where');
if($i) {
$pos=$i;
$i=strpos($sql,' ',$i+1);
$cond=trim(substr($sql,$i));
$sql=substr($sql,0,$pos);

//get clause since schema has been parsed
$compClause=$this->parseWhere($cond);
}


$this->deleteWhere("$tbl.txt",$compClause);
}

function updateData($sql) {
$this->prepcmd($sql,'update');

$i=strpos($sql,' ');
$tbl=trim(substr($sql,0,$i));

$compClause=NULL; //preset condition2

//parse the schema here
$this->parseSchema($tbl);


//where
$i=strpos($sql,'where');
if($i) {
$pos=$i;
$i=strpos($sql,' ',$i+1);
$cond=trim(substr($sql,$i));
$sql=substr($sql,0,$pos);

//get clause since schema has been parsed
$compClause=$this->parseWhere($cond);
}

//get data
$i=strpos($sql,'set');
$i=strpos($sql,' ',$i+1);
$j=strrpos($sql,' ',$i+1);
$data=trim(substr($sql,$i,$j-$i));


//preprocess
$this->parseScheme($data);
$data=str_replace('=','=>',$data);
eval('$data=array('.$data.');');

$this->updateSetWhere("$tbl.txt", $data,$compClause);
}

function selectData($sql) {
$this->prepcmd($sql,'select');

$i=strpos($sql,' ');
$data=trim(substr($sql,0,$i));

//variables
$compClause=NULL;
$limit=-1; //default
$order=NULL;


$j=strpos($sql,'from');
$j=strpos($sql,' ',$j+1);
$k=strpos($sql,' ',$j+1);
$tbl=trim(substr($sql,$j,$k-$j));

//get limit
$i=strpos($sql,'limit ');
if($i) {
$pos=$i;
$i=strpos($sql,' ',$i+1);
$limit=trim(substr($sql,$i));
$sql=substr($sql,0,$pos).' ';
}


//get order by here
$i=strpos($sql,'order by');
if($i) {
$pos=$i;
$i=strpos($sql,' ',$i+1);
$i=strpos($sql,' ',$i+1);
$ord=trim(substr($sql,$i));
$sql=substr($sql,0,$pos).' ';
}

//where
$i=strpos($sql,'where');
if($i) {
$i=strpos($sql,' ',$i+1);
$cond=trim(substr($sql,$i));
}

//parse the schema here
$this->parseSchema($tbl);

//parse where clause
if(!empty($cond)) {
$compClause=$this->parseWhere($cond);
}

//execute order here
if(!empty($ord)) {

$ord=str_replace(Array('asc','desc',' '),Array('ASCENDING','DESCENDING',','),$ord);

$ord='$order= new OrderBy('.$ord.');';
$ord= strtr("$ord", $this->trans);

eval($ord);
}

//run query here
$rows = $this->selectWhere("$tbl.txt", $compClause, $limit, $order);


$alldata=implode(',',array_keys($this->schema));
if($data=="*") {$data=$alldata;}

$keys=explode(",",$data);

$newschema=Array();
foreach($keys as $k) {
$newschema["$k"] = $this->schema[$k];	
}

$nrows=Array();
foreach($rows as $row) {

$r=Array();
foreach($newschema as $k=>$v) {
$r["$k"]=$row[$v];
}

$nrows[]=$r;
}

						  
return $nrows;
}

function parseScheme(&$s) {
$s= strtr("$s", $this->trans);
return $s;
}

function parseSchema($tbl) {
//prepare parser here
$schema=$this->datadir."$tbl".'.schema';
$schema=unserialize(file_get_contents($schema));

$schema=array_flip($schema);
$this->schema=$schema;

//preparse schema variables
//externalize variables
$pos=-1;
foreach($schema as $field=>$val) {$pos++;
eval('$'."$field='$pos';");
eval('$trans["'.$field.'"]="$'.$field.'";');
}
$this->trans=$trans; //schema translation	
}

function parseWhere($cond) {
$cond = str_replace('=',',\'=\',',$cond);
$cond = str_replace('>',',\'>\',',$cond);
$cond = str_replace('<',',\'<\',',$cond);

$conds=explode("and",$cond);

$c2=Array();
foreach($conds as $c) {
if(strpos($c,"'='")) {$c.=',STRING_COMPARISON';}
else {$c.=',INTEGER_COMPARISON';}

$c2[]='new SimpleWhereClause('.$c.')';
}
$c=implode(',',$c2);

//evaluate conditions
$cond2='$compClause = new AndWhereClause( '.$c.' );';
$cond2= strtr("$cond2", $this->trans);
eval($cond2);
return $compClause;
}



function prepcmd(&$sql,$cmd) {
	$i=intval(strpos($sql,$cmd));
	$i=intval(strpos($sql,' ',$i+strlen($cmd)));
	$sql=substr($sql,$i);
	$sql=trim($sql).' ';
}


}
?>