var Script = function () {
    $('#btn-synchronize').on('click', function() {
        try {
            var url = '/tools/iotSynchronize/';
            var params = 'ts='+Math.round(new Date().getTime()/1000);
            $('#pageLoader').fadeIn(function(){
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

                            // an error has been detected
                            if (obj.error == true) {
                                var msg = "The branch data could not be synchronized." +
                                    (!!obj.info ? "<br><br>" + obj.info : "" ) +
                                    "<br><br>Please contact support if this problem persists.";
                                growl('Error Synchronizing!', msg, {});
                                $('#commissioningLoader').fadeOut(function(){});
                            } else{ // no errors
                                growl('Branch data synchronized', 'The branch data has been successfully synchronized.', {});
                            }
                        }
                        catch(error){

                        }
                    },
                    complete: function(jqXHR, textStatus){
                        $('#pageLoader').fadeOut();
                    }
                });
            });

        } catch (ex) {

        }/**/
    });

}();