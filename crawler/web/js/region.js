$(function () {

    regionGenerals();

});

function regionGenerals() {

    this.methods = {
        voids: function() {
            return $('.region input').filter(function() { 
                return $(this).val().length == 0 
            });
        },
        addButtonSwitch: function() {
            if ( methods.voids().length == 0 ) $('.region-add').removeClass('disabled').attr({'title': 'Add new region'});
            else $('.region-add').addClass('disabled').attr({'title': 'Please fill the input first...'});
        },
        onType: function() {
            $(document).on('input', '.region input', function () {
                methods.addButtonSwitch();
            });
        },
        onRemove: function() {
            $(document).on('click', '.region-remove', function () {
                $(this).parents('.region').remove();
                methods.addButtonSwitch();
            });
        },
        onAdd: function() {
            $(document).on('click', '.region-add', function () {

                if ( !$(this).hasClass('disabled') ) {

                    var html =  '<div class=\'row region\'>' +
                    '                <div class=\'col-lg-10\'>' +
                    '                    <input type=\'text\' value=\'\' name=\'regions[]\' class=\'form-control\' placeholder=\'... or add a new one\'>' +
                    '                </div>' +
                    '                <div class=\'col-lg-1\'>' +
                    '                    <i class=\'btn btn-sm btn-danger glyphicon glyphicon-trash region-remove\'></i>' +
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

function pasteRegionToEmpty(valData) {
    if (regionGenerals().voids().length > 0)
        $(regionGenerals().voids()[0]).val(valData);
    else {
        $('.region-add').trigger('click');
        $(regionGenerals().voids()[0]).val(valData);
    }
    regionGenerals();
}

