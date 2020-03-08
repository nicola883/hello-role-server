<?php
	
interface iDb
{
	
	public function execQuery($query);
	
	public function insert($table, $array);
	
	public function getRow();
	
	public function numRows();
	
	public function backup($filename);
	
	public function escapeString($string);
	
	public function createRestrictedViews();
	
	public function setRestrictQueriesAndParameter($queries, $param);
	
}

?>
