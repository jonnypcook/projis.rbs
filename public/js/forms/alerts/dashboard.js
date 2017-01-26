var Script = function () {
    //toggle button
    var fullAlertsLoaded = false;
    $('#header_notification_bar').on('click', function(e) {
        if (fullAlertsLoaded) return;
        if (!$('#alert-items-header').is(':visible')) {
            $('ul#alert-items-header li:not(.mailCount)').remove();
            $('ul#alert-items-header').append(
                $('<li>').append(
                    $('<a>').text('Alerts are loading - please wait ...')

                )
            );
            fullAlertsLoaded = true;
            loadAlerts();
        }
    });

    function loadAlerts(mode) {
        try {
            var url = '/branch/listalerts/';
            var params = 'ts='+Math.round(new Date().getTime()/1000) + '&preview=1';

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
                        if (obj.err == true) {

                        } else{ // no errors
                            if (obj.total<=0) {
                                $('.alert-count').text(0)
                            } else {
                                $('.alert-count').show().text(obj.total)
                            }

                            var ul = $('ul#alert-items-header');
                            ul.empty();
                            ul.append(
                                $('<li>').append(
                                    $('<a>').attr('href','#').html(obj.total + ' devices with alerts')

                                )
                            );

                            for (var i in obj.branches) {
                                ul.append(
                                    $('<li>').append(
                                        $('<a>').attr('href', '/branch-' + obj.branches[i].projectId).html(
                                            '<i class="icon-warning-sign"></i> ' +
                                           obj.branches[i].name +
                                           ' <span class="small italic">' + obj.branches[i].count + ' alerts</span>'
                                        )
                                    )
                                );
                            }


                        }
                    }
                    catch(error){
                        $('#errors').html($('#errors').html()+error+'<br />');
                    }
                },
                complete: function(jqXHR, textStatus){
                    $('#setupLoader').fadeOut(function(){});
                }
            });

        } catch (ex) {

        }/**/
    }


}();