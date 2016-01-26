<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($_REQUEST["action"])) {
	$_REQUEST["action"]="";
}
if(!isset($_REQUEST['gridid'])) {
	trigger_error("Grid Not Found");
}

include_once __DIR__."/api.php";

switch($_REQUEST["action"]) {
	case "fetchGrid":
		$reportKey=$_REQUEST['gridid'];
		if(!isset($_SESSION['REPORT'][$reportKey])) {
			trigger_error("Sorry, grid report key not found.");
		}
		$reportConfig=$_SESSION['REPORT'][$reportKey];

		$dbKey=$reportConfig['dbkey'];
		if($dbKey==null) $dbKey="app";


		$source=$reportConfig['source'];
		$searchCols=$reportConfig['searchCols'];

		if(!isset($_REQUEST['page'])) $_REQUEST['page']=0;
		if(!isset($_REQUEST['limit'])) $_REQUEST['limit']=$source['limit'];

		$source['limit']=$_REQUEST['limit'];
		$source['index']=$_REQUEST['page']*$source['limit'];

		if(!isset($source['type'])) {
			trigger_error("Corrupt Report Configuration");
		}

		$data=[];
		switch ($source['type']) {
			case 'sql':
				$sql=QueryBuilder::fromArray($source,_db($dbKey));
				if(isset($_POST['filter']) && count($_POST['filter'])>0) {
					$whereFilters=[];
					foreach ($_POST['filter'] as $key => $value) {
						$whereFilters[$key]=array("VALUE"=>$value,"OP"=>"SW");
					}
					$sql->_where($whereFilters);
				}
				if(isset($_POST['search']) && count($_POST['search'])>0) {
					if(isset($_POST['search']['q']) && count($_POST['search']['q'])>0) {
						$searchArr=[];
						$q=$_POST['search']['q'];
						foreach ($searchCols as $col) {
							$searchArr[$col]=array("VALUE"=>$q,"OP"=>"LIKE");
						}
						$sql->_where($searchArr,"AND","OR");
					}
				}
				if(isset($_POST['orderby']) && count($_POST['orderby'])>0) {
					$sql->_orderby($_POST['orderby']);
				}
				//exit($sql->_SQL());
				
				$res=_dbQuery($sql,$dbKey);
				$data=_dbData($res,$dbKey);
				_dbFree($res,$dbKey);
				break;

			case 'php':
				$file=APPROOT.$source['file'];
				if(file_exists($file) && is_file($file)) {
					$data=include_once($file);
				} else {
					trigger_error("Report Source File Not Found");
				}
				break;		
			
			case 'model':

			default:
				trigger_error("Report Source Not Supported");
				break;
		}

		switch ($_REQUEST['format']) {
			case 'html':
				if(count($data)>0) {
					if($reportConfig['showExtraColumn']) {
						if(strpos($reportConfig['showExtraColumn'],"<")===0) {
							$firstColumn="<td class='tableColumn rowSelector'>{$reportConfig['showExtraColumn']}</td>";
						} else {
							$firstColumn="<td class='tableColumn rowSelector'><input name='rowSelector' type='{$reportConfig['showExtraColumn']}' /></td>";
						}
					} else {
						$firstColumn="";
					}
					$dataKey=array_keys($reportConfig['datagrid'])[0];
					$otherKey=array_keys($data[0])[0];

					foreach ($data as $record) {
						if(isset($record['id'])) {
							$hashid=$record['id'];
						} elseif(isset($record[$dataKey])) {
							$hashid=md5($record[$dataKey]);
						} else {
							$hashid=md5($record[$otherKey]);
						}
						echo "<tr class='tableRow' data-hash='{$hashid}'>";
						echo $firstColumn;
						foreach ($reportConfig['datagrid'] as $key => $column) {
							if(!isset($column['formatter'])) $column['formatter']="text";
							if(!isset($column['hidden'])) $column['hidden']=false;
							if(isset($record[$key])) {
								echo formatReportColumn($key,$record[$key],$column['formatter'],$column['hidden']);
							} else {
								echo formatReportColumn($key,"",$column['formatter'],$column['hidden']);
							}
						}
						echo "</tr>";
					}
				} else {
					if($_REQUEST['page']>0) {

					} else {
						echo "<tr class='norecords'><td colspan=1000000 align=center>No Record Found</td></tr>";
					}
				}
				
				break;
			
			default:
				printServiceMsg($data);
				break;
		}
		//printArray($data);
	break;
	default:
		trigger_error("Action Not Defined or Not Supported");
}
?>
