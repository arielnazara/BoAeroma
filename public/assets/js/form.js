$(function () {
    const $container = $('#container-prototype');
    let index = 0;

    $('.add-delivery').on('click', function (e) {
        addDelivery($container);
        e.preventDefault();
        return false;
    });


    function addDelivery($container) {
        let template = $container.attr('data-prototype').replace(/__index/g, '-' + (index+1))
        let $prototype = $(template);

        addDeleteLink($prototype);

        $container.append($prototype);

        $('html, body').animate({
            scrollTop: $prototype.offset().top
        }, 1000);

        index++;

        $('.indice').val(index);
    }

    function addDeleteLink($prototype) {
        let $deleteLink = $('<a href="#" class="btn btn-danger mt-3">Supprimer</a><hr>');
        $prototype.append($deleteLink);
        $deleteLink.on('click', function (e) {
            //$prototype.remove();
            $prototype.hide('slow', function(){ $prototype.remove(); });
            e.preventDefault();
            return false;
        });
    }
});