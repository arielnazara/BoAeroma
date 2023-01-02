$(function () {
    let $fomStatus = $('.filter-form'), $tabContainer = $('#tablecontent'), $state =  $('select[name="state_"]');

    $state.on('change', function () {
        const $loader = $('.loader');
        let value = $(this).val();

        if ($loader.attr('class')) {
            $loader.show();
        } else {
            $tabContainer.append($('<div style="margin: auto;" class="loader"></div>'));
        }

        $('.table-padded').hide();
        $.ajax({
            type: 'POST',
            url: $fomStatus.attr('action'),
            data: {'state' : value},
            dataType: 'json',
            success: function (data) {
                $('.loader').css("display", "none");
                $tabContainer.html(data.content);
                desactiverAnimation()
            }
        });
    });

    desactiverAnimation()

    function desactiverAnimation()
    {
        $('.payer').click(function(e){
            e.preventDefault()
    
            $('tr').addClass('stop')
    
        })
    
        $(document).on('click', function(e){
            
            setTimeout(() => {  
                let show = false
                $('.modal').each(function(i, element ){
                    
                    if($(this).is(':visible'))
                    {
                       return show = true
                    }
    
                })
    
                if( ! show )
                {
                    $('tr').removeClass('stop')            
                }
               
            }, 1000)
            
        });
    }
})