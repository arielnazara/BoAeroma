$(function()
{
    const url = $('#addToSession').data('target')
    const type = $('#addToSession').data('type')

    $('input[type=checkbox]').change(function()
    {
        if( !this.checked )
        {
            const productId = $(this).data('productid')
            const declinaison = $(this).data('dec')

            addToSession(productId, 0, declinaison)

        }
    })

    $('input[type=number]').on('keyup keypress blur change', function(e) 
    {
        const quantite = $(this).val()
        const productId =  $(this).data('productid')
        const declinaison = $(this).data('dec')
        

        addToSession(productId, 1, declinaison, quantite)
        
    });

    function addToSession(productId, add, declinaison = null, quantite = null)
    {
        if(quantite != '' || quantite == null)
        {
             $.ajax({
                url: url,
                type: 'post',
                data: {productId, declinaison, quantite, add, type},
                success: function(data){
               
                }
            })
        }
    }

})