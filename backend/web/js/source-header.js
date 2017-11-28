$(function () {

    headerSourceGenerals();

});

function headerSourceGenerals() {

    this.methods = {
        voids: function() {
            return $('.header-value input[type=text].header-value-text').filter(function() { 
                return $(this).val().length == 0 
            });
        },
        addButtonSwitch: function() {
            if ( methods.voids().length == 0 ) $('.header-value-add').removeClass('disabled').attr({'title': 'Add new header-value'});
            else $('.header-value-add').addClass('disabled').attr({'title': 'Please fill the input first...'});
        },
        onType: function() {
            $(document).on('input', '.header-value input', function () {
                methods.addButtonSwitch();
            });
        },
        onRemove: function() {
            // $(document).on('click', '.header-value-remove', function () {
            //     $(this).parents('.header-value').remove();
            //     methods.addButtonSwitch();
            // });
        },
        onAdd: function() {
            $(document).on('click', '.header-value-add', function () {

                if ( !$(this).hasClass('disabled') ) {

                    var html =  '<div class=\'row header-value\'>' +
                                '    <div class=\'col-lg-2\'>' +
                                '        <span class=\'pull-left btn btn-default label label-primary header-value-status\' disabled><small>ON</small></span>' +
                                '        <input type=\'text\' class=\'form-control input-sm text-right header-value-queue\' disabled>' +
                                '    </div>' +
                                '    <div class=\'col-lg-9\'>' +
                                '        <input type=\'text\' value=\'\' class=\'form-control input-sm header-value-text\' readonly>' +
                                '        <input type=\'hidden\' value=\'\' name=\'header-new-values[]\'>' +
                                '    </div>' +
                                '    <div class=\'col-lg-1\'>' +
                                '        <i class=\'disabled btn btn-sm btn-danger glyphicon glyphicon-trash header-value-remove\'></i>' +
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

function pasteHeaderValue (headerValueId, headerSourceId) {

    console.log(headerValueId, headerSourceId);
    $.get( 'index.php?r=source/header-create&hid=' + headerValueId + '&sid=' + headerSourceId )
        .done( function (data) {
            if (data) {
                console.log(data);

                if ( headerSourceGenerals().voids().length > 0 )
                    $(headerSourceGenerals().voids()[0] ).val(data).next().val(headerValueId);
                else {
                    $('.header-value-add').trigger('click');
                    $( headerSourceGenerals().voids()[0] ).val(data).next().val(headerValueId);
                }
                // headerSourceGenerals();

                $(this).prop('selectedIndex', 0);
            }
        }
    );
}


function deleteHeaderValue (headerSourceId, i) {
    console.log(headerSourceId);
    confirm('Удаление заголовка!');
    $.get( 'index.php?r=source/header-delete&id=' + headerSourceId )
        .done( function (data) {
            if (data == 1) {
                console.log(data);
                $('.header-value-' + i).remove();
                // if (data == 0) valueRow.removeClass('label-primary').addClass('label-danger').find('small').text('OFF');
                // else valueRow.removeClass('label-danger').addClass('label-primary').find('small').text('ON');
            }
        }
    );
}

function unhideSelector (valData) {
    $('.header-value-select').addClass('hidden');
    $('.header-value-select[data-id=' + valData + ']').removeClass('hidden');
}

