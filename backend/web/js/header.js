$(function () {

    headerValueGenerals();

});

function headerValueGenerals() {

    this.methods = {
        voids: function() {
            return $('.header-value input').filter(function() { 
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
            $(document).on('click', '.header-value-remove', function () {
                $(this).parents('.header-value').remove();
                methods.addButtonSwitch();
            });
        },
        onAdd: function() {
            $(document).on('click', '.header-value-add', function () {

                if ( !$(this).hasClass('disabled') ) {

                    var html = '<div class=\'row header-value\'>' +
                        '                <div class=\'col-lg-11\'>' +
                        '                    <input type=\'text\' value=\'\' name=\'header-new-values[]\' class=\'form-control\' placeholder=\'Add new value\'>' +
                        '                </div>' +
                        '                <div class=\'col-lg-1\'>' +
                        '                    <i class=\'btn btn-danger glyphicon glyphicon-minus header-value-remove\'></i>' +
                        '                </div><br/><br/><br/>' +
                        '            </div>';

                    $(this).before(html);
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

function pasteToEmpty (valData) {
    if ( headerValueGenerals().voids().length > 0 )
        $(headerValueGenerals().voids()[0] ).val(valData);
    else {
        $('.header-value-add').trigger('click');
        $( headerValueGenerals().voids()[0] ).val(valData);
    }
    headerValueGenerals();
}

