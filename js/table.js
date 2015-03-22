function reloadDataTable() {
	loadData();
}

function printGrid() {
	html=$(".reportDataTable table").parent().html();
	html="<html><title>Print Preview</title></html><div align=center class=noprint><button onclick='window.print();' style='width:100px;height:30px;'>Print</button></div>"+html;
	OpenWindow=window.open('','Print Preview');
	OpenWindow.document.write(html);
}
