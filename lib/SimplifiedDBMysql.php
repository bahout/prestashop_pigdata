<?php
/**
* SimplifiedDB - Library of PHP functions for intracting Mysql database using Mysql extension
* File: SimplifiedDB.php
* Author: Pritesh Gupta
* Version: 1.0.1
* Date: 10/20/2012
* Copyright (c) 2012 Pritesh Gupta. All Rights Reserved.

/* ABOUT THIS FILE:
   -------------------------------------------------------------------------
* SimplifiedDB Class provides set of functions for interacting database using Mysql extension.
* You don't need to write any query to perform insert, update, delete and select operations(CRUD operations).
* You need to call these functions with appropriate parameters and these functions will perform required 
* Database operations. These functions will also take some basic actions for preventing Mysql injection
* There are also some useful functions which helps you to create html forms, show results in table format etc. by
* directly interacting with Database tables or from array of result of select query. 
   -------------------------------------------------------------------------
*/
class SimplifiedDB
{
	public $error_info;				// Display the error message, if any. Use this for debugging purpose 
	public $message_info;           // Display the last message associated with the task like success message, or connected to database etc
	public $dbh;                    // Used for database connection object
	public $user_name;	            // Username for the database
	public $password;               // Password for database
	public $host_name;              // hostname/server for database
	public $db_name;                // Database name 
	public $query;                  // Display the last query executed
	public $rows_affected;          // Display the no. of rows affected
	public $last_insert_id;         // Display the insert id of last insert operation executed
	public $output_array=false;     // Returns the output as array for select operation instead of result set if true 	
	public $and_or_condition="and"; // Use 'and'/'or' in where condition of select statement, default is 'and'	
	public $group_by_column="";     // Set it to column names you wants to GROUP BY e.g. 'gender' where gender is column name
	public $order_by_column="";     // Set it to column names you wants to ORDER BY e.g. 'firstname DESC' where firstname is column name	
	public $limit_val="";           // Set it to limit the no. of rows returned e.g. '0,10', it generates 'LIMIT 0,10'
	public $having="";              // Set it to use 'HAVING' keyword in select query e.g. $having="sum(col1)>1000"	
	public $between_columns=array();// Set it to use 'BETWEEN' keyword in select query e.g. $between=array ("col1"=>val1,"col1"=>val2)
	public $in=array();             // Set it to use 'IN' keyword in select query e.g. $in=array("col1"=>"val1,val2,val3")
	public $not_in=array();         // Set it to use 'NOT IN' keyword in select query e.g. $not_in=array("col1"=>"val1,val2,val3")
	public $like_cols=array();      // Set it to use 'LIKE' keyword in select query e.g. $like_col=array("col1"=>"%v%","col2"=>"c%")
	public $is_sanitize=true;       // Checks whether basic sanitization of query varibles needs to be done or not.
	public $backticks="`";          // Backtick for preventing error if columnname contains reserverd mysql keywords. If you wants to use alias
									// for column names then set it empty string.


	/******************************************** Mysql Functions **********************************************************/
	/**
	 * Connects to database

	 * @param   string  $hostname          Host/Server name 
	 * @param   string  $user_name         User name 
	 * @param   string  $password          Password 
	 * @param   string  $database          Database-name
	 * returns  mysql resource object	 
	*/	
	function dbConnect($hostname,$user_name,$password,$dbname)
	{	
		$this->host_name=$hostname;
	   	$this->user_name=$user_name;
	   	$this->password=$password;
	   	$this->db_name=$dbname;	

		$con=mysql_connect($this->host_name,$this->user_name,$this->password);
		if(!$con) 
			{
				die('Could not connect: ' . mysql_error());
			}			
		mysql_select_db($this->db_name,$con);			

	}
	
		
	/**
	 * Insert new records in a table using associative array. Instead of writing long insert queries, you needs to pass
	 * array of keys(columns) and values(insert values). This function will automatically create query for you and inserts data.
	 * @param   string   $table_name              The name of the table to insert new records.
	 * @param   array    $insert_array            Associative array with key as column name and values as column value.
	 *
 	 */

	function dbInsert($table_name,$insert_array)
	{		

		$columns="";
		$values="";
		
		if(is_array($insert_array))
		{
			foreach($insert_array as $col => $val)
			{
				$columns=$columns."`".trim($col)."`,";
				$values=$values."'".$this->sanitize($val)."',";
			}
			
			$columns=rtrim($columns,",");
			$values=rtrim($values,",");
			
			$this->query="INSERT INTO `".trim($table_name)."`(".$columns.")"." VALUES (".$values.")";		
			$res=mysql_query($this->query);				
			$this->rows_affected=mysql_affected_rows();
			$this->last_insert_id=mysql_insert_id();
			$this->error_info=mysql_error();
		
			return $res;
		}
		else
		{
			$this->error_info="Insert elements(insert_array) must be associative array";
		}
	}
	
		/**
	 * Insert new records in a table using associative array. Instead of writing long insert queries, you needs to pass
	 * array of keys(columns) and values(insert values). This function will automatically create query for you and inserts data.
	 * @param   string   $table_name              The name of the table to insert new records.
	 * @param   array    $insert_batch_array      Array of associative array with key as column name and values as column value.
	 *
 	 */

	function dbInsertBatch($table_name,$insert_batch_array)
	{	
		foreach($insert_batch_array as $insert_array)
		{
			$columns="";
			$values="";
			
			foreach($insert_array as $col => $val)
			{
				$columns=$columns."`".trim($col)."`,";
				$values=$values."'".$this->sanitize($val)."',";
			}
			
			$columns=rtrim($columns,",");
			$values=rtrim($values,",");
			
			$this->query="INSERT INTO `".trim($table_name)."`(".$columns.")"." VALUES (".$values.")";		
			$res=mysql_query($this->query);				
			$this->rows_affected=mysql_affected_rows();
			$this->last_insert_id=mysql_insert_id();
			$this->error_info=mysql_error();
		}
	}
	
	/**
	 * Update existing records in a table using associative array. Instead of writing long update queries, you needs to pass
	 * array of keys(columns) and values(update values) and associative array of conditions with keys as columns and value as column value.
	 * This function will automatically create query for you and updates data.
	 * Note: The WHERE clause specifies which record or records that should be updated. If you omit the WHERE clause, 
	 * all records will be updated!
	 * @param   string   $table_name                  The name of the table to update old records.
	 * @param   array    $update_array                Associative array with key as column name and values as column value.
	 * @param   array    $update_condition_array      Associative array with key as column name and values as column value for where clause.	
	 * returns  mysql    resource object
	 */
	function dbUpdate($table_name,$update_array,$update_condition_array=array())
	{

		$colums_val="";
		$where_condition="";
		
		foreach($update_array as $col => $val)
		{
			$colums_val=$colums_val."`".trim($col)."`='".$this->sanitize($val)."',";			
		}
		$colums_val=rtrim($colums_val,",");
		
		foreach($update_condition_array as $col => $val)
		{
			$where_condition=$where_condition."`".trim($col)."`='".$val."',";			
		}
		
		if($where_condition)
			$where_condition=" WHERE ".rtrim($where_condition,",");
		
		$this->query="UPDATE `".trim($table_name)."` SET ".$colums_val.$where_condition;		
		$res=mysql_query($this->query);				
		$this->rows_affected=mysql_affected_rows();
		$this->last_insert_id=mysql_insert_id();
		$this->error_info=mysql_error();
		
		return $res;		
	}
	
	/**
	 * Delete records in a table using associative array. Instead of writing long delete queries, you needs to pass
	 * associative array of conditions with keys as columns and value as column value.
	 * This function will automatically create query for you and deletes records.
	 * Note: The WHERE clause specifies which record or records that should be deleted. If you omit the WHERE clause, 
	 * all records will be deleted!	 
	 * @param   string   $table_name                  The name of the table to delete records.
	 * @param   array    $delete_where_condition      Associative array with key as column name and values as column value for where clause.	
	 *
	 * return   resource                              returns result of query.
	*/
	function dbDelete($table_name,$delete_where_condition=array())
	{
		$where_condition="";
		$and_val="";
		foreach($delete_where_condition as $col => $val)
		{
			$where_condition=$where_condition.$and_val." `".trim($col)."`='".$val."' ";			
			$and_val=$this->and_or_condition;
		}
		if($where_condition)
			$where_condition=" WHERE ".rtrim($where_condition,",");	
		
		$this->query="DELETE FROM `".trim($table_name)."`".$where_condition;
		$res=mysql_query($this->query);				
		$this->rows_affected=mysql_affected_rows();
		$this->last_insert_id=mysql_insert_id();
		$this->error_info=mysql_error();
		
		return $res;		
	}

	/**
	 * Select records from the single table. You can provide columns to be selected and where clause with
	 * associative array of conditions with keys as columns and value as column value.
	 * Along with these function parameters, you can set group by columnname, order by columnname, limit, like, in , not in, between clause etc.
	 * This function will automatically creates query for you and select data.
	 * @param   string   $table_name                  The name of the table to select records.
	 * @param   array    $columns                     Array of columns to be selected
	 * @param   array    $select_where_condition      Associative array with key as column name and values as column value for where clause.	
	 * @param   string   $group_by_col                The name of column of table to perform group by
	 * @param   string   $order_by_col                The name of column of table to perform order by
	 * @param   string   $limit                       Limits the result between two number
	 *
	 * return   resource                              returns result of query.
	*/

	function dbSelect($table_name,$columns=array(),$select_where_condition=array(),$group_by_col="",$order_by_col="",$limit="")
	{
		/* Get Columns */
		$col=$this->getColumns($columns);
		
		/* Add where condition */
		$where_condition=$this->getWhereCondition($select_where_condition);
		
		/* Add like condition */
		$where_condition=$this->getLikeCondition($where_condition);
		
		/* Add Between condition */		
		$where_condition=$this->getBetweenCondition($where_condition);		
		
		/* Add In condition */				
		$where_condition=$this->getInCondition($where_condition);
		
		/* Add Not In condition */						
		$where_condition=$this->getNotInCondition($where_condition);
		
		/* Add Group By and Having condition */						
		$where_condition=$this->getGroupByCondition($where_condition);		
		
		/* Add Order By condition */						
		$where_condition=$this->getOrderbyCondition($where_condition);					
		
		/* Add Limit condition */								
		$where_condition=$this->getLimitCondition($where_condition);
		
		$this->query="SELECT ".$col." FROM ".$this->backticks.trim($table_name).$this->backticks." ".$where_condition;
		
		$result=mysql_query($this->query);		
		
		 if($this->output_array)
			 {
				 $output_array=array();
				 while ($rows = mysql_fetch_array($result))
				 {
					$output_array[]=$rows;
				 }
				 $result=$output_array;
			 }
			 
		$this->error_info=mysql_error();
		return $result;
	}
	
	/**
	 * Select records from the multiple table with join keyword. You can provide columns to be selected and where clause with
	 * associative array of conditions with keys as columns and value as column value, group by, order by , limit etc.
	 * You needs to specify join condition between different tables and join type (left join, right join etc.) to select data. 
	 * This function will automatically creates query for you and select data.
	 * @param   array    $table_name                  Array of tables to be joined
	 * @param   array    $join_conditions             Array of join conditions between tables
	 * @param   array    $join_type                   Array of join types
	 * @param   array    $columns                     Array of columns to be selected
	 * @param   array    $select_where_condition      Associative array with key as column name and values as column value for where clause.	
	 * @param   string   $group_by_col                The name of column of table to perform group by
	 * @param   string   $order_by_col                The name of column of table to perform order by
	 * @param   string   $limit                       Limits the result between two number
	 *
	 * return   resource                              returns result of query.
	*/

	function dbSelectJoin($table_names,$join_conditions,$join_type,$columns=array(),$select_where_condition=array())
	{
		/* Get Join condition */
		$table_join=$this->getTableJoins($table_names,$join_conditions,$join_type);
		
		/* Get Columns */
		$col=$this->getColumns($columns);
		
		/* Add where condition */
		$where_condition=$this->getWhereCondition($select_where_condition);
		
		/* Add like condition */
		$where_condition=$this->getLikeCondition($where_condition);
		
		/* Add Between condition */		
		$where_condition=$this->getBetweenCondition($where_condition);		
		
		/* Add In condition */				
		$where_condition=$this->getInCondition($where_condition);
		
		/* Add Not In condition */						
		$where_condition=$this->getNotInCondition($where_condition);
		
		/* Add Group By and Having condition */						
		$where_condition=$this->getGroupByCondition($where_condition);		
		
		/* Add Order By condition */						
		$where_condition=$this->getOrderbyCondition($where_condition);					
		
		/* Add Limit condition */								
		$where_condition=$this->getLimitCondition($where_condition);
		
		$this->query="SELECT ".$col." FROM ".$table_join." ".$where_condition;
		
		$result=mysql_query($this->query);		
		
		if($this->output_array)
			 {
				 $output_array=array();
				 while ($rows = mysql_fetch_array($result))
				 {
					$output_array[]=$rows;
				 }
				 $result=$output_array;
			 }
			 
		$this->error_info=mysql_error();
		return $result;
	}
	
	/**
	 * Checks whether particular field of table contains some specific value or not. Most of the times
	 * of we needs to check for specific values like username, passwords etc. You can use the select functions
	 * also for this but this function is added seprately just to simplify it. 
	 * @param   string   $table_name         Table name for the checking value
	 * @param   string   $field_name         Field name for which we will check value against
	 * @param   string   $field_val          Field value which needs to be checked in field name
	 *
	 * return   boolean                      Returns true if value exists else false
	*/	
	
	function dbCheckValue($table_name,$field_name,$field_val)
	{
		$this->query="SELECT ".$this->backticks.$field_name.$this->backticks." FROM ".$this->backticks.trim($table_name).$this->backticks.
				" WHERE ".$this->backticks.trim($field_name).$this->backticks."='".$field_val."'";
		$res=mysql_query($this->query);
		
		$this->error_info=mysql_error();
		
		if(mysql_num_rows($res)>0)
			return true;
		return false;	
	}
	
	/**
	 * Executes any mysql query and returns the result (array in case of select query). 
	 * Use this for running any other queries that can't be run using the other select,insert,update,delete functions
	 * @param   string  $query       Query to be executed
	 *
	 * return   array               result of the query
	*/	
	function dbExecuteQuery($query)
	{
		$this->query=$query;
		$result=mysql_query($query);
		
		if($this->output_array)
			 {
				 $output_array=array();
				 while ($rows = mysql_fetch_array($result))
				 {
					$output_array[]=$rows;
				 }
				 $result=$output_array;
			 }
			 
		$this->rows_affected=mysql_affected_rows();
		$this->last_insert_id=mysql_insert_id();
		$this->error_info=mysql_error();
		
		return $result;
	}
	
	/**
	 * Retrives the column names from a given table
	 * @param   string  $table       The name of the table to get columns.
	 *
	 * return   Resource             result of the query
	*/
	function dbGetColumnName($table_name)
	{
		$this->query="DESCRIBE $table_name";
		$result= mysql_query($this->query);
		
		if($this->output_array)
		 {
			 $output_array=array();
			 while ($rows = mysql_fetch_array($result))
			 {
				$output_array[]=$rows;
			 }
			 $result=$output_array;
		 }
			 
		return $result;
	}
	
	/**
	 * Retrives the primary key of a given table
	 * @param   string  $table       Table name
	 *
	 * return   Resource             result of the query
	*/
	function dbGetPrimaryKey($table_name)
	{
		$query="SHOW INDEXES FROM $table_name WHERE Key_name = 'PRIMARY'";
		$result= mysql_query($query);
		
		if($this->output_array)
			 {
				 $output_array=array();
				 while ($rows = mysql_fetch_array($result))
				 {
					$output_array[]=$rows;
				 }
				 $result=$output_array;
			 }
			 
		return $result;
	}
	
	/**
	 * Generates the display table result directly from the result object of mysql select query.
	 * @param   Resource  $result               Select query result
	 * @param   string    $table_css_class      Css class for table
	 * @param   string    $tr_css_class         Css class for tr
	 *
	 * return   string                 returns the display in table format
	*/
	function getHtmlTableFromResultObj($result,$table_css_class="sdb_tbl_cls",$tr_css_class="sdb_tr_cls")
	{
		$output_array=array();
		while($row=mysql_fetch_assoc($result))
		{
			$output_array[]=$row;
		}	
		
		return $this->getHtmlTableFromArray($output_array,$table_css_class,$tr_css_class);
	}	



	/******************************************** General Functions **********************************************************/
	/**
	 * Generate the standard html form based on the columns of the table of database. It will 
	 * by default create input type='text' for all columns of the table. You can copy
	 * source code and modify it as per your requirement.
	 *
	 * @param   string        $table_name         Single Table name for which html form needs to be generated
	 * @param   string        $action_url         Html form action parameter(default is "", i.e. same page)
	 * @param   string        $method             Html form submission method(GET or POST)(Default value=POST)
	 * @param   string        $input_css_class    Css class for all input type text
	 *
	 * return   string                            Html form
	 */	 
	 
	function getHtmlFormWithDBTable($table_name,$action_url="",$method="POST",$input_css_class="textfield")
	{
		
		$columns=$this->dbGetColumnName($table_name);
		
		echo "<form action='$action_url' method='$method' class='frm_simplifieddb'>";
		echo "<fieldset>";
	
		while($column=mysql_fetch_array($columns))
		{		
			?>
			<dl>
				<dt><label for="<?php echo $column['0'];?>"><?php echo ucfirst(str_replace("_"," ",$column['0']));?></label></dt>
				<dd><input type="text" name="<?php echo $column['0'];?>" id="<?php echo $column['0'];?>" class="<?php echo $input_css_class;?>" /></dd>
			</dl>
		<?php
        }
		echo "</fieldset>";
		echo "</form>";
	}

	/**
	 * Generates the html table as output from the array provided.
	 * @param   array     $input_array             Associative array with key as column name and value as table values.
	 * @param   string    $table_css_class         Css class for html table
	 * @param   string    $tr_css_class            Css class for tr
	 *
	 * return   string                 			   returns the display in html table format
	*/	
	function getHtmlTableFromArray($input_array,$table_css_class="sdb_tbl_cls",$tr_css_class="sdb_tr_cls")
	{
		$table_output="<table class='".$table_css_class."'>";
		$table_head="<thead><tr>";
		$table_body="<tbody>";
		$loop_count=0;
		
		foreach($input_array as $k=>$v)
		{
			$table_body.="<tr class='".$tr_css_class."' id='tr_".$loop_count."'>";			
			foreach($v as $col=>$row)
			{
				$table_body.="<td>".$row."</td>";
				if($loop_count==0)
					$table_head.="<td>".$col."</td>";								
			}
			$table_body.="</tr>";
			$loop_count++;
		}		
		
		$table_head.="</thead></tr>";
		$table_body.="</tbody>";
		$table_output=$table_output.$table_head.$table_body."</table>";
		echo $table_output;
		//return $table_output;
	}
	
	/**
	 * Cleans the input to prevent simple sql injection. It does basic cleaning of data before database operations.
	 * You can pass multidimensional array or entire post array, to clean its elements, instead of passing
	 * single elements one by one.
	 * @param   mixed  $val       The variable which needs to be cleaned, It can be an array or single variable.
	 *
	 * return   mixed              Return the cleaned variable
	*/
	function sanitize($val)
	{
		if($this->is_sanitize)
		{
			if(is_array($val))
			{
				$newArr = array();
				foreach( $val as $key => $value )
					{
						$newArr[$key]=(is_array($value)?$this->sanitize($value):mysql_real_escape_string($value));
					}
				return $newArr;
			}
			
			return mysql_real_escape_string($val);	
		}
		else
			return $val;
	}

/*********************************************************** Internal Functions ******************************************/	
 /*Returns column names */
  private function getColumns($columns=array())
   {
	   $col="*";
	   if(is_array($columns)&&count($columns)>0)
		{
			$col="";
			foreach($columns as $column)
			{
				$col=$col.$this->backticks.trim($column).$this->backticks.",";
			}
			$col=rtrim($col,",");
		}
		return $col;
   }
   
  /*Returns where condition */   
  private function getWhereCondition($select_where_condition=array())
   {
		$where_condition="";
		$matches=array();
		
	   	if(is_array($select_where_condition))
		{
			foreach($select_where_condition as $cols => $vals)
			{
				$compare="=";	
				if(preg_match("#([^=<>!]+)\s*(=|<|>|(!=)|(>=)|(<=)|(>=))#", trim($cols), $matches))
				{
					$compare=$matches[2];
					$cols=trim($matches[1]);
				}				
				$vals=$this->sanitize($vals);
				$where_condition=$where_condition." ".$this->backticks.trim($cols).$this->backticks.$compare." '".
								 $vals."' ".$this->and_or_condition;			
			}
			
			if($where_condition)
				$where_condition=" WHERE ".rtrim($where_condition,$this->and_or_condition);	
		}
		return $where_condition;	   
   }
   
  /*Returns like condition */      
   private function getLikeCondition($where_condition="")
   {
	  if(is_array($this->like_cols)&&count($this->like_cols)>0)
		{		
			$like="";	
			foreach($this->like_cols as $cols => $vals)
			{
				$like.=$this->backticks.$cols.$this->backticks." LIKE '".$vals."' ".$this->and_or_condition;				
			}
			
			if($where_condition)
				$where_condition.=" ".$this->and_or_condition." ".rtrim($like,$this->and_or_condition);
			else
				$where_condition=" WHERE ".rtrim($like,$this->and_or_condition);
		}
		return $where_condition;
   }
   
  /*Returns between condition */      
   private function getBetweenCondition($where_condition="")
   {
	   if(is_array($this->between_columns)&&count($this->between_columns)>0)
		{	
			
			foreach($this->between_columns as $cols => $vals)
			{			
				$values[]=$vals;
			}
			
			reset($this->between_columns);
			$between=key($this->between_columns)." BETWEEN '".$values[0]."' and '".$values[1]."'";	
			
			if($where_condition)
				$where_condition.=" ".$this->and_or_condition." ".$between;
			else
				$where_condition=" WHERE ".$between;
		}
		
		return $where_condition;	
   }
   
  /*Returns in condition */      
   private function getInCondition($where_condition="")
   {
	  if($this->in&&count($this->in)>0)
		{
			$in="";	
			foreach($this->in as $cols => $vals)
			{
				$in.=$this->backticks.$cols.$this->backticks." IN (".$vals.") ".$this->and_or_condition;
			}
			
			if($where_condition)
				$where_condition.=" ".$this->and_or_condition." ".rtrim($in,$this->and_or_condition);
			else
				$where_condition=" WHERE ".rtrim($in,$this->and_or_condition);
		}
		return $where_condition;	
   }
   
  /*Returns not in condition */      
   private function getNotInCondition($where_condition="")
   {
	   if($this->not_in&&count($this->not_in)>0)
		{
			$not_in="";	
			foreach($this->not_in as $cols => $vals)
			{
				$not_in.=$this->backticks.$cols.$this->backticks." NOT IN (".$vals.") ".$this->and_or_condition;
			}
			
			if($where_condition)
				$where_condition.=" ".$this->and_or_condition." ".rtrim($not_in,$this->and_or_condition);
			else
				$where_condition=" WHERE ".rtrim($not_in,$this->and_or_condition);
		}
		return $where_condition;
   }
   
  /*Returns group by condition */      
   private function getGroupByCondition($where_condition="")
   {
	   	if($this->group_by_column)
			$where_condition.=" GROUP BY ".$this->group_by_column;
			
		if($this->group_by_column&&$this->having)
			$where_condition.=" HAVING ".$this->having;
		
		return $where_condition;	
   }
   
  /*Returns order by  condition */      
   private function getOrderbyCondition($where_condition="")
   {
	   if($this->order_by_column)
			$where_condition.=" ORDER BY ".$this->order_by_column;	
			
	   return $where_condition;	
   }
   
  /*Returns limit condition */      
   private function getLimitCondition($where_condition="")
   {
	   	if($this->limit_val)
			$where_condition.=" LIMIT ".$this->limit_val;
			
	   return $where_condition;	
   }
   
  /*Returns join condition */      
   private function getTableJoins($table_names,$join_conditions,$join_type)
   {	  
		if(is_array($table_names))
		{	 $loop_table=0;
			foreach($table_names as $table_name)
			{
				if($loop_table==0)
					$table_join=$this->backticks.trim($table_name).$this->backticks;
				else
					$table_join.=" ". $join_type[$loop_table-1]." ".$this->backticks.$table_name.
								 $this->backticks." ON ".$join_conditions[$loop_table-1];
					
				$loop_table++;
			}
		}	
		return $table_join;
   }
}
?>