$(function () {

    proxySourceGenerals();

});

function proxySourceGenerals() {

    this.methods = {
        voids: function() {
            return $('.proxy-value input[type=text].proxy-value-text').filter(function() { 
                return $(this).val().length == 0 
            });
        },
        addButtonSwitch: function() {
            if ( methods.voids().length == 0 ) $('.proxy-value-add').removeClass('disabled').attr({'title': 'Add new proxy-value'});
            else $('.proxy-value-add').addClass('disabled').attr({'title': 'Please fill the input first...'});
        },
        onType: function() {
            $(document).on('input', '.proxy-value input', function () {
                methods.addButtonSwitch();
            });
        },
        onRemove: function() {
            // $(document).on('click', '.proxy-value-remove', function () {
            //     $(this).parents('.proxy-value').remove();
            //     methods.addButtonSwitch();
            // });
        },
        onAdd: function() {
            $(document).on('click', '.proxy-value-add', function () {

                if ( !$(this).hasClass('disabled') ) {

                    var html =  '<div class=\'row proxy-value\'>' +
                                '    <div class=\'col-lg-3\'>' +
                                '        <span class=\'pull-left btn btn-default label label-primary proxy-value-status\' disabled><small>ON</small></span>' +
                                '        <input type=\'text\' class=\'form-control input-sm text-right proxy-value-queue\' disabled>' +
                                '    </div>' +
                                '    <div class=\'col-lg-7\'>' +
                                '        <input type=\'text\' value=\'\' class=\'form-control input-sm proxy-value-text\' readonly>' +
                                '        <input type=\'hidden\' value=\'\' name=\'proxy-new-values[]\'>' +
                                '    </div>' +
                                '    <div class=\'col-lg-2 text-center\'>' +
                                '        <i class=\'disabled btn btn-sm btn-danger glyphicon glyphicon-trash proxy-value-remove\'></i>' +
                                '    </div>' +
                                '</div><br/>';

                    $(this).after(html);
                    $(this).addClass('disabled');
                    $(this).attr({'title': 'Please fill the input first...'});

                }

            });
        }
    }

    $.each( this.methods, function (funcName) {
        methods[funcName]();
    });

    return methods;

}

function pasteProxyValue (proxyValueId, proxySourceId) {

    console.log(proxyValueId, proxySourceId);
    $.get( 'index.php?r=source/proxy-create&pid=' + proxyValueId + '&sid=' + proxySourceId )
        .done( function (data) {
            if (data) {
                console.log(data);

                if ( proxySourceGenerals().voids().length > 0 )
                    $(proxySourceGenerals().voids()[0] ).val(data).next().val(proxyValueId);
                else {
                    $('.proxy-value-add').trigger('click');
                    $( proxySourceGenerals().voids()[0] ).val(data).next().val(proxyValueId);
                }
                // proxySourceGenerals();

                $(this).prop('selectedIndex', 0);
            }
        }
    );
}


function deleteProxyValue (proxySourceId, i) {
    console.log(proxySourceId);
    confirm('Удаление прокси!');
    $.get( 'index.php?r=source/proxy-delete&id=' + proxySourceId )
        .done( function (data) {
            if (data == 1) {
                console.log(data);
                $('.proxy-value-' + i).remove();
                // if (data == 0) valueRow.removeClass('label-primary').addClass('label-danger').find('small').text('OFF');
                // else valueRow.removeClass('label-danger').addClass('label-primary').find('small').text('ON');
            }
        }
    );
}

