function openReportLink(link,title) {
	link+="&site="+SITENAME;
	//alert(title+"=>"+link);return;
	if(rptOptsMaster[gridId].actionLinkInNewPage) {
		if(typeof parent.dpLink == "function") {
			parent.dpLink(title,link);//,search,btns,icon
		} else if(typeof dpLink == "function") {
			dpLink(title,link);//,search,btns,icon
		} else if(typeof parent.openInNewTab == "function") {
			parent.openInNewTab(title,link);
		} else {
			window.open(link);
		}
	} else {
		document.location=link;
	}
}
function takeAction(obj,txt) {
	if(obj==null || txt==null || txt.length<=0) return false;
	if(obj.hasClass("email")) {
		$.mailform(txt,"No Subject","");
	} else if(obj.hasClass("url")) {
		if(typeof parent.openInNewTab=="function") {
			parent.openInNewTab("Link",txt);
		}
		return;
	} else if(obj.hasClass("attachment")) {
		if(isNaN(txt)) {
			txt=txt.split(",");
			if(txt.length>1) {
				msg="<div style='width:800px;height:300px;overflow:auto;'><ol>";
				for(i=0;i<txt.length;i++) {
					nm=txt[i].split('/');
					nm=nm[nm.length-1];
					msg+="<li style='margin-bottom:5px;'><a href='#' onclick=\"openFileLink('"+txt[i]+"','local');\" >"+nm+"</a><br/></li>";
				}
				msg+="</ol></div>";
				lgksAlert(msg);
			} else {
				openFileLink(txt[0],'local');
			}
		} else {
			openFileLink(txt,'dbfile');
		}
		return;
	}
	return true;
}
function openFileLink(txt,type) {
	if(type=="local") {
		//lnk="services/?scmd=viewfile&type=view&loc=local&popup=true&file="+txt;
		lnk=getServiceCMD("viewfile")+"&type=view&loc=local&popup=true&file="+txt;
		lgksOverlayFrame(lnk);
	} else {
		//services?scmd=viewfile&type=view&loc=dbfile&dbtbl=do_files&file=1
		//services/?scmd=viewfile&type=view&loc=dbdoc&dbtbl=do_docs&file=1
		lgksAlert("Can Not Handle These Type Of Attachments");
	}
}
