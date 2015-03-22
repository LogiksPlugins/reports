var exportCSS="";
function createForm(url,html) {
	var ids="exportForm"+Math.ceil(Math.random()*100000);
	var form = "<form id="+ids+" name='"+ids+"' action='"+url+"' method='post' target='_blank' style='display:none;'>";
    form = form + "<textarea name='data'>"+html+"</textarea>";
	form = form + "</form>";
	$("body").append(form);
	return ids;
}
function getHTMLForGrid(id,styled) {
	if(styled==null) styled=true;
	var mya=new Array();
	mya=$(id).getDataIDs();  // Get All IDs
	var data=$(id).getRowData(mya[0]);     // Get First row to get the labels
	var colNames=new Array(); 
	var colTitles=new Array(); 
	var ii=0;
	xc=$(id).getGridParam("colNames");
	xp=$(id).getGridParam("colModel");
	for(var t in xp) {
		if(xc[t]!=null && typeof xc[t]=="string" && xc[t].length>0 && xc[t].indexOf("<")<0) {
			if(xp[t].name!=null && !(xp[t].name=="rn" || xp[t].name=="cb")) {
				if(!xp[t].hidden) {
					colNames[ii]=xp[t].name;
					colTitles[ii]=xc[t];
					ii++;
				}
			}
		}		
	}
	
	header=$(id).parents("div.LGKSRPTTABLE").find("#hd1 h3").text();
	footer=$(id).parents("div.LGKSRPTTABLE").find(".reportfooter").text();
	
	if(header==null) header="";
	if(footer==null) footer="";
	
	var html="";
	if(styled) html+="<style>"+exportCSS+"</style>";
	if(styled) html+="<table class='exportdatatable' width=100% border=0 cellpadding=0 cellspacing=0>";	
	else html+="<table class='exportdatatable'>";
	html+="<caption align=center>"+header+"</caption>";
	html+="<thead><tr>";
	for(k=0;k<colNames.length;k++) {
		html+="<td>"+colTitles[k]+"</td>";     // output each Column as tab delimited
	}
	html+="</tr></thead><tbody>";
	for(i=0;i<mya.length;i++) {
		data=$(id).getRowData(mya[i]);
		html+="<tr>";
		for(j=0;j<colNames.length;j++) {
			html+="<td>"+data[colNames[j]]+"</td>";
		}
		html+="</tr>";
	}
	html+="</tbody>";
	html+="<tfoot><tr><td colspan=10>"+footer+"</td></tr></tfoot>";
	html+="</table>";
	return html;
}
function getCSVForGrid(id) {
	var mya=new Array();
	mya=$(id).getDataIDs();  // Get All IDs
	var data=$(id).getRowData(mya[0]);     // Get First row to get the labels
	var colNames=new Array(); 
	var colTitles=new Array(); 
	var ii=0;
	//for (var i in data){colNames[ii++]=i;}    // capture col names
	xc=$(id).getGridParam("colNames");
	xp=$(id).getGridParam("colModel");
	for(var t in xp) {
		if(xc[t]!=null && typeof xc[t]=="string" && xc[t].length>0 && xc[t].indexOf("<")<0) {
			if(xp[t].name!=null && !(xp[t].name=="rn" || xp[t].name=="cb")) {
				if(!xp[t].hidden) {
					colNames[ii]=xp[t].name;
					colTitles[ii]=xc[t];
					ii++;
				}
			}
		}		
	}
	var html="";
	var delim=",";
	for(k=0;k<colNames.length;k++) {
		html=html+colTitles[k]+delim;     // output each Column as tab delimited
	}
	html=html+"\n";                    // Output header with end of line
	for(i=0;i<mya.length;i++) {
		data=$(id).getRowData(mya[i]); // get each row
		str="";
		for(j=0;j<colNames.length;j++) {
			//html=html+data[colNames[j]]+delim; // output each column as tab delimited
			xxx=data[colNames[j]];
			xxx=xxx.replace(/\"/g,"`");
			//xxx=xxx.replace(/\n/g,"XXX");
			str=str+'"'+xxx+'"'+delim; // output each column as tab delimited
		}
		if(str.length>0) {
			html+=str+'\n';  // output each row with end of line
		}
	}
	html=html+"\n";  // end of line at the end
	return html;
}
