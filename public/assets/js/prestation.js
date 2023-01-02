$(function(){
    let count = 0;

    $(".search").select2( {
        allowClear: true
    } );

    

    $('#prestation_estPayer').change(function(e){
        if($(this).is(":checked")) 
        {
            $('.js-payer').show()
            $('.js-payement-form').attr('required', true)

        }
        else
        {
            
            $('.js-payer').hide()
            $('.js-payement-form').attr('required', false)

        }
    })
    
    $('.js-customizeInput').attr('type', 'number')
    
    deleteBlock()

    $('#ajoutType').click(function (e) {

        e.preventDefault()

        const template = $('#prestation_typePrestations').data('prototype').replace(/__name__/g, `type${count}`)

        $('#prestation_typePrestations').append(template)
       
        count++

        deleteBlock()

        $('.js-customizeInput').attr('type', 'number')

    })

    function deleteBlock()
    {
        $('[data-action="delete"]').click(function(e){
            e.preventDefault()
           
            const div = $(this).data('target')

            $(div).remove()
        })
    }

    getPrices()

    function getPrices()
    {
        
        $('.calculer').click(function(e){
            
            e.preventDefault()
            let price = 0;


            $('.js-prix').each(function(i, input){

                const id = input.dataset.id
                const prix = input.value
                const quantite = $(`#block_${id}`).find('.js-quantite').val()
                const total = $(`#block_${id}`).find('.js-total')

                const result = parseFloat(prix) * parseInt(quantite)

                total.val(result)

            })
            
            $('.js-type-val').each(function(i, input){
                if(input.value)
                {
                    price = parseFloat(price) + parseFloat(input.value)
                    
                }
            })
            
            const tva = parseFloat(price) * 0.2

            const ttc = parseFloat(price) + tva

            $('#prestation_montant').val(price.toFixed(2))
            $('#prestation_tva').val(tva.toFixed(2))
            $('#prestation_totalTtc').val(ttc.toFixed(2))
            
        })
    }

})
