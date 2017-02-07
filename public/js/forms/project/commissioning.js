var Script = function () {
    $('#tbl_devices').dataTable({
        sDom: "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
        sPaginationType: "bootstrap",
        iDisplayLength:5,
        aLengthMenu: [[5, 10, 15, 20], [5, 10, 15, 20]],
        oLanguage: {
            sLengthMenu: "_MENU_ per page",
            oPaginate: {
                sPrevious: "",
                sNext: ""
            }
        },
        aoColumnDefs: [{
                'bSortable': true,
                'aTargets': [0]
            },
            {
                'bSortable': true,
                'aTargets': [1]
            },
            {
                'bSortable': true,
                'aTargets': [2]
            },
            {
                'bSortable': true,
                'aTargets': [3]
            },
            {
                'bSortable': false,
                'aTargets': [4]
            }
        ]
    });


    var ProjectID = $('#ProjectID');
    var deviceListOptions = $('#sel-device-types');

    $(document).on('click', '.btn-edit-device-type', function(e) {
        showButtons($(this).parent(), false, true, true);
        setDeviceTypeOptions($(this).parent().prev());
    });

    $(document).on('click', '.btn-cancel-device-type', function(e) {
        showButtons($(this).parent(), true, false, false);
        resetDeviceType($(this).parent().prev());
    });

    function setDeviceTypeOptions(setup) {
        var sel = deviceListOptions.clone().show();
        setup.empty().append(sel.val(setup.attr('data-device-type-id')));

        return sel;
    }

    function resetDeviceType(setup) {
        setup.empty().text(setup.attr('data-device-type-label'));
    }

    function setDeviceType(setup, deviceTypeId, deviceTypeLabel) {
        setup.attr('data-device-type-id', deviceTypeId);
        setup.attr('data-device-type-label', deviceTypeLabel);
        setup.empty().text(deviceTypeLabel);
    }

    function showButtons(btns, edit, save, cancel) {
        if (!!edit) {
            btns.find('.btn-edit-device-type').show();
        } else {
            btns.find('.btn-edit-device-type').hide();
        }

        if (!!save) {
            btns.find('.btn-save-device-type').show();
        } else {
            btns.find('.btn-save-device-type').hide();
        }

        if (!!cancel) {
            btns.find('.btn-cancel-device-type').show();
        } else {
            btns.find('.btn-cancel-device-type').hide();
        }
    }

    $(document).on('click', '.btn-save-device-type', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var btns = $(this).parent(),
            setup = btns.prev(),
            deviceId = setup.attr('data-device-id'),
            deviceType = setup.find('select');

        if (!deviceType) {
            return;
        }

        if (!deviceId) {
            return;
        }

        var deviceTypeId = deviceType.val(),
            deviceTypeLabel = deviceType.find('option[value=' + deviceTypeId + ']').text();

        try {
            var url = $('#tbl_devices').attr('action');
            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&deviceId=' + deviceId + '&deviceTypeId=' + deviceTypeId;

            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        var obj=jQuery.parseJSON(response);
                        var k = 0;
                        // an error has been detected
                        if (obj.error == true) {
                            var msg = "The device type setup could not be saved." +
                                (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                "<br><br>Please contact support if this problem persists."
                            growl('Error Saving!', msg, {});
                        } else{ // no errors
                            growl('Device Type Updated!', 'The device type has been successfully updated.', {});
                            setDeviceType(setup, deviceTypeId, deviceTypeLabel);
                            showButtons(btns, true, false, false);
                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#commissioningLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {
            $('#commissioningLoader').fadeOut(function(){});
        }/**/

        return false;

    });



    $('input[name=installed]').datepicker({
        format: 'dd/mm/yyyy'
    }).on('changeDate', function (e) {
        $('input[name=installed]').datepicker('hide').blur();
    });

    $('input[name=commissioned]').datepicker({
        format: 'dd/mm/yyyy'
    }).on('changeDate', function (e) {
        $('input[name=commissioned]').datepicker('hide').blur();
    });

    $('#btn-save-config').on('click', function (e) {
        e.preventDefault();
        $('#CommissioningSetupForm').submit();
        return false;
    });

    $('#CommissioningSetupForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            var url = $(this).attr('action');
            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&' + $(this).serialize();
            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        var obj=jQuery.parseJSON(response);
                        var k = 0;
                        // an error has been detected
                        if (obj.error == true) {
                            var msg = "The project setup data could not be saved." +
                                (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                "<br><br>Please contact support if this problem persists."
                            growl('Error Saving!', msg, {});
                            $('#commissioningLoader').fadeOut(function(){});
                        } else{ // no errors
                            window.location.reload();
                        }
                    },
                    complete: function(jqXHR, textStatus){

                    }
                });
            });

        } catch (ex) {
            $('#commissioningLoader').fadeOut(function(){});
        }/**/
        return false;
    });

    $('#frmSaveStatus').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            var url = $(this).attr('action');
            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&' + $(this).serialize();
            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.error == true) {
                                growl('Error Saving Status!', 'The project statuses have NOT been updated successfully. Please contact support if this problem persists.', {});
                            } else{ // no errors
                                growl('Status Updated', 'The project statuses have been updated successfully', {});
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#commissioningLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });

    $('#btn-save-status').on('click', function(e) {
        e.preventDefault();
        $('#frmSaveStatus').submit();
        return false;
    });


    $('#frmUpdateLinkedBranch').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            var url = $(this).attr('action');
            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&' + $(this).serialize();
            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.error == true) {
                                var msg = "The project linked branch has NOT been updated successfully." +
                                    (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                    "<br><br>Please contact support if this problem persists."
                                growl('Error Updating Linked Branch!', msg, {});
                                $('#commissioningLoader').fadeOut(function(){});
                            } else{ // no errors
                                window.location.reload();
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){

                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });

    $('#btn-update-linked-branch').on('click', function(e) {
        e.preventDefault();

        if (ProjectID.val().match(/^[\d]+$/)) {
            $('#frmUpdateLinkedBranch').submit();
        }

        return false;
    });

    $('#btn-synchronize').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        try {
            var url = $(this).attr('action');

            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&' + $(this).serialize();
            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.error == true) {
                                var msg = "The branch data could not be synchronized." +
                                    (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                    "<br><br>Please contact support if this problem persists.";
                                growl('Error Synchronizing!', msg, {});
                                $('#commissioningLoader').fadeOut(function(){});
                            } else{ // no errors
                                window.location.reload();
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){

                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;
    });


    $('.upload-drawing-image').on('click', function(e) {
        e.preventDefault();
        var DrawingID = $(this).parent().parent().attr('data-drawing-id');

        if (DrawingID==undefined) return;

        // set initial valuess
        $('form[name=FormUploadDrawing] input[name=DrawingID]').val(DrawingID);
        $('form[name=FormUploadDrawing] input[name=file]').val('');
        $('form[name=FormUploadDrawing] input[name=file]').trigger('change');

        $('#modalUploadDrawing').modal();

        return false;
    });

    $('#btn-select-file').on('click',function(e) {
        $('input[name=file]').trigger('click');
    });

    $('input[name=file]').on('change',function(){
        if ($(this).val().length) {
            $('#lbl-file-info').text($(this).val().replace(/([^\\/]*[\\/])*([^.\\/]+([.][^.]+)+)$/i,'$2')).removeClass('label-important').addClass('label-success');
        } else {
            $('#lbl-file-info').text('No file selected').addClass('label-important').removeClass('label-success');
        }
    });

    $('#btn-confirm-upload-drawing').on('click', function(e) {
        e.preventDefault();

        $('#FormUploadDrawing').submit();
        return false;
    });


    $('.deactivate-drawing-image').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        activateDrawing($(this).parent().parent(), false);
    });

    $('.activate-drawing-image').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        activateDrawing($(this).parent().parent(), true);
    });

    function activateDrawing(drawingRow, activate) {
        var DrawingID = drawingRow.attr('data-drawing-id');
        var projectId = drawingRow.attr('data-project-id');
        var clientId = drawingRow.attr('data-client-id');

        if (DrawingID==undefined) return;
        if (projectId==undefined) return;
        if (clientId==undefined) return;


        try {
            var url = '/client-' + clientId + '/project-' + projectId + '/commissioningactivatedrawing/';

            var params = 'ts=' + Math.round(new Date().getTime()/1000) + '&DrawingID=' + DrawingID + '&activate=' + (!!activate ? '1' : '0');
            $('#commissioningLoader').fadeIn(function(){
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: params, // Just send the Base64 content in POST body
                    processData: false, // No need to process
                    timeout: 60000, // 1 min timeout
                    dataType: 'text', // Pure Base64 char data
                    beforeSend: function onBeforeSend(xhr, settings) {},
                    error: function onError(XMLHttpRequest, textStatus, errorThrown) {},
                    success: function onUploadComplete(response) {
                        try{
                            var obj=jQuery.parseJSON(response);
                            var k = 0;
                            // an error has been detected
                            if (obj.error == true) {
                                var msg = "The drawing could not be " + (!!activate ? 'activated.' : 'deactivated.') +
                                    (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                    "<br><br>Please contact support if this problem persists.";
                                growl('Error Synchronizing!', msg, {});
                            } else{ // no errors
                                drawingRow.find('td:nth-child(4)').text(!!activate ? 'Yes' : 'No');
                                if (!!activate) {
                                    drawingRow.find('.activate-drawing-image').hide();
                                    drawingRow.find('.deactivate-drawing-image').show();
                                } else {
                                    drawingRow.find('.activate-drawing-image').show();
                                    drawingRow.find('.deactivate-drawing-image').hide();
                                }
                                growl('Drawing Updated Successfully!', "The drawing has been successfully " + (!!activate ? 'activated.' : 'deactivated.'), {});
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#commissioningLoader').fadeOut(function(){});
                    }
                });
            });

        } catch (ex) {

        }/**/
        return false;

    };


}();