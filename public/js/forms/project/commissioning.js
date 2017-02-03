var Script = function () {
    var ProjectID = $('#ProjectID');


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
                        try{
                            console.log(response); return;
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
                                    "<br><br>Please contact support if this problem persists."
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


}();