$(function () {

    keywordGenerals();

});

function keywordGenerals() {

    this.methods = {
        voids: function() {
            return $('.keyword input').filter(function() { 
                return $(this).val().length == 0 
            });
        },
        addButtonSwitch: function() {
            if ( methods.voids().length == 0 ) $('.keyword-add').removeClass('disabled').attr({'title': 'Add new keyword'});
            else $('.keyword-add').addClass('disabled').attr({'title': 'Please fill the input first...'});
        },
        onType: function() {
            $(document).on('input', '.keyword input', function () {
                methods.addButtonSwitch();
            });
        },
        onRemove: function() {
            $(document).on('click', '.keyword-remove', function () {
                $(this).parents('.keyword').remove();
                methods.addButtonSwitch();
            });
        },
        onAdd: function() {
            $(document).on('click', '.keyword-add', function () {

                if ( !$(this).hasClass('disabled') ) {

                    var html = '<div class=\'row keyword\'>' +
                        '                <div class=\'col-lg-10\'>' +
                        '                    <input type=\'text\' value=\'\' name=\'keywords[]\' class=\'form-control\' placeholder=\'... or add a new one\'>' +
                        '                </div>' +
                        '                <div class=\'col-lg-1\'>' +
                        '                    <i class=\'btn btn-danger glyphicon glyphicon-trash keyword-remove\'></i>' +
                        '                </div>' +
                        '            <br/><br/></div>';

                    $(this).before(html);
                    $(this).addClass('disabled');
                    $(this).attr({'title': 'Please fill the input first...'});

                }

            });
        }
    }

    $.each(this.methods, function(funcName){
        methods[funcName]();
    });

    return methods;

}

function pasteToEmpty(valData) {
    if (keywordGenerals().voids().length > 0)
        $(keywordGenerals().voids()[0]).val(valData);
    else {
        $('.keyword-add').trigger('click');
        $(keywordGenerals().voids()[0]).val(valData);
    }
    keywordGenerals();
}

