$(function () {

    $(document).on('click', '.tree-expand', function(){
        $(this).next().toggleClass('expanded');
        $(this).toggleClass('expanded');
    });

    $(document).ready( function(){
        selected = $('.category-tree-select.selected');
        if (selected.hasClass('selected')) {
            parentRow = selected.parent().parent();
            parentRow.prev().click();
            expandParents(parentRow);
        }
    });

});

function expandParents(row) {
    $(row).parent().prev().click();
    if ($(row).parent().hasClass('category-tree-row')) {
        expandParents($(row).parent());
    }
}

function keywordOnSelect(sourceId) {

	if ( $('#keywordList').val() )
        $.post('index.php?r=parser/build-url', 
            {
                sourceId: sourceId,
                categorySourceId: $('#categoryList').find(':selected').attr('data-csid'), 
                keyword: $('#keywordList').find(':selected').text(), 
                inputValue: $('#parseInput').val() 
            } 
        )
        .done(function( data ) {
            $('#parseInput').val(data);
        });
            
}

function categoryOnSelect(sourceId, regionId, categoryId) {

    $(document).on('click', '.category-tree-select', function(e){
        $('#categoryList').val(categoryId);
        $('.category-tree-select').removeClass('selected');
        $(this).addClass('selected');
    });

    location = 'index.php?r=parser/trial&cat=' + categoryId + '&id=' + sourceId + '&reg=' + regionId;

    // if ( $('#categoryList').val() )
    //     $.post('index.php?r=parser/build-url', 
    //             {
    //                 sourceId: sourceId,
    //                 categorySourceId: $('#categoryList').find(':selected').attr('data-csid'), 
    //                 keyword: $('#keywordList').find(':selected').text(), 
    //                 inputValue: $('#parseInput').val() 
    //             } 
    //         )
    //         .done(function( data ) {
    //             $('#parseInput').val(data);
    //         });
            
}