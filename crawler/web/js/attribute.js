$(function () {
    $('.attribute-add').click(function () {
        var html = '<div class=\'row attribute\'>' +
            '                <div class=\'col-lg-4\'>' +
            '                    <input type=\'text\' value=\'\' name=\'attributes[]\' class=\'form-control\'>' +
            '                </div>' +
            '                <div class=\'col-lg-1\'>' +
            '                    <i class=\'btn btn-danger glyphicon glyphicon-minus attribute-remove\'></i>' +
            '                </div>' +
            '            </div>';

        $('.attribute-add').before(html);
    });

    $(document).on('click', '.attribute-remove', function () {
        $(this).parents('.attribute').remove();
    });
});