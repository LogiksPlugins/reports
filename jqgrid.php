<?php
if(!defined('ROOT')) exit('No direct script access allowed');

function getWhere($searchOn) {
	$searchOn=Strip($searchOn);
	$wh = "";
	if($searchOn=='true') {
		$sarr = Strip($_REQUEST);
		if(isset($sarr['filters']) && strlen($sarr['filters'])>0) {
			$filter=json_decode(Strip($sarr['filters']),true);
			$wh="(".getGroup($filter).")";			
		} elseif(isset($sarr['searchField']) && strlen($sarr['searchField'])>0) {
			if($sarr["searchOper"]=="nu" || $sarr["searchOper"]=="nn") {
				$k=$sarr["searchField"];
				if($sarr["searchOper"]=="nu") $wh.="$k is NULL";
				if($sarr["searchOper"]=="nn") $wh.="$k is not NULL";
			} else {
				$k=$sarr["searchField"];
				$v=$_REQUEST["searchString"];
				$x=getRelation($_REQUEST["searchOper"],$k,$v);
				$wh.="$x";
			}
		}
	}
	return $wh;
}
function getGroup($filter) {
	if(count($filter)<=0) {
		return;
	}
	$msg="";
	$gops=$filter["groupOp"];
	$rules=$filter["rules"];
	if(isset($filter["groups"])) $groups=$filter["groups"]; else $groups="";
	foreach($rules as $a=>$b) {
		if(sizeOf($rules)-1!=$a)				
			$msg.=getRule($b)." " . strtoupper($gops)." ";
		else
			$msg.=getRule($b)." ";
	}
	if(is_array($groups) && count($groups)>0) {
		$msg.=strtoupper($gops)." ";
		foreach($groups as $a=>$b) {
			if(sizeOf($groups)-1!=$a)				
				$msg.="(".getGroup($b).") " . strtoupper($gops)." ";
			else
				$msg.="(".getGroup($b).") ";
		}
	}
	return $msg;
}
function getRule($arr) {
	$s="";
	$v="";
	if(isset($arr["data"])) $v=$arr["data"];
	
	if(isset($arr["field"])) $s.=$arr["field"];
	if(isset($arr["op"])) $s=" ".getRelation($arr["op"],$arr["field"],$v);
	
	return $s;
}
function processWhere($where) {
	$userid="";
	if(isset($_REQUEST["userid"])) $userid=$_REQUEST["userid"];
	elseif(isset($_SESSION["SESS_USER_ID"])) $userid=$_SESSION["SESS_USER_ID"];
	
	$where=str_replace('$date',date('Y-m-d'),$where);
	if(isset($_REQUEST["dateFrm"])) $where=str_replace('$dateFrm',$_REQUEST["dateFrm"],$where);
	if(isset($_REQUEST["dateTo"])) $where=str_replace('$dateTo',$_REQUEST["dateTo"],$where);
	$where=str_replace('$userid',$userid,$where);
	
	$where=processSQLQuery($where);
	
	return $where;
}
?>
