
$('#myframe').on('load', function () {
    $(this).contents().on('click','.select',function () {
        var path = $(this).attr('data-path')
        $('#path').val(path);
        $('#image').attr('src', path)
        $('#myModal').modal('hide')
    });
});

$('.btn-modal').on('click', function (){
    $('.modal-backdrop.fade.show').css('display', 'block');
})

$('.btn-close').on('click', function (){
    console.log('click')
    $('.modal-backdrop.fade.show').css('display', 'none');
    $('#myModal').modal('hide');
})
