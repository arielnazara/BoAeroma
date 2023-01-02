$(function(){
    

    $('input[type=checkbox]').click(function(){ 
       
        const val = $(this).val()

        if($(this).is(':checked')){
            produitAction(val)
        }else{
            produitAction(val, true)
        }
        
    })

    function produitAction(val, supprimer = null)
    {

        $(`#check_${val}`).hide()
        $(`.js-spin-${val}`).show()
        
        const url = $('#valider').data('target')

        $.ajax({
            type: 'POST',
            url: url,
            data: {val, supprimer},
            dataType: 'json',
            success: function (data) 
            {

                $(`.js-spin-${val}`).hide()
                $(`#check_${val}`).show()

            },
            error: function()
            {
                alert("une errereur c'est produite")
            }
        })
    }

})