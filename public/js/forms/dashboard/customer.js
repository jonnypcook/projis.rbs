var Script = function () {
    // begin first table
    var branchesTable = $('#branches_tbl').dataTable({
        "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "oLanguage": {
            "sLengthMenu": "_MENU_ records per page",
            "oPaginate": {
                "sPrevious": "Prev",
                "sNext": "Next"
            }
        },
        bProcessing: true,
        bServerSide: true,
        iDisplayLength:25,
        aLengthMenu: [[5, 10, 15, 20, 25, 50], [5, 10, 15, 20, 25, 50]],
        "aoColumns": [
            null,
            null,
            null,
            null,
            //{ 'bSortable': false }
        ],
        sAjaxSource: '/branch/list',
        fnServerParams: function (aoData) {
            //var fDrawingId = $("#fDrawingId").val();
            //aoData.push({name: "fDrawingId", value: fDrawingId});
        },
        fnDrawCallback: function () {
            // called at the end of table rendering
        }
    });

    //jQuery('#branches_tbl_wrapper .dataTables_filter input').addClass("input-medium"); // modify table search input
    //jQuery('#branches_tbl_wrapper .dataTables_length select').addClass("input-mini"); // modify table per page dropdown

}();