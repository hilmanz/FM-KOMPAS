$(function () {
	

	$('#shorTable').dataTable( {
		sDom: "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
		sPaginationType: "bootstrap",
       iDisplayLength: 5,
		oLanguage: {
			"sLengthMenu": "_MENU_ records per page"
		}
	});
	$('#shorTable2').dataTable( {
		sDom: "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
		sPaginationType: "bootstrap",
        iDisplayLength: 5,
		oLanguage: {
			"sLengthMenu": "_MENU_ records per page"
		}
	});
	
});