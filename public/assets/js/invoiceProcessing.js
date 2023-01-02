$(function(){
    
    const facture = $('.js-facture')
    const url = facture.data('target')

    $('.customize').attr('type', 'number');

    $(".search").select2( {
        allowClear: true
    } );

    if(facture.data('info') == "creation")
    {
        facture.val('')
        $('#select2-facture-container').prop('title','').html('')
        $('#facture_avoir_tva').val('')
        $('#facture_avoir_totalTtc').val('')
        $('#facture_avoir_tva').val('')
        $('#facture_avoir_totalHT').val('')
    }
    
    calcul()

    facture.change(function(){
        getInvoice($(this).val())
        $('#facture_avoir_tva').val('')
        $('#facture_avoir_totalTtc').val('')
        $('#facture_avoir_tva').val('')
        $('#facture_avoir_totalHT').val('')
    })

    function getInvoice(info)
    {
        $.ajax({
            url: url,
            type: 'POST',
            data: { id: info },
            success: function (data) {

                if(data)
                {
                    const numero = data.numero
                    const Ht = data.totalHt
                    const tva = data.tva
                    const ttc = data.totalTtc

                    $('.js-ht').html(Ht + "€")
                    $('.js-tva').html(tva + "€")
                    $('.js-ttc').html(ttc + "€")

                    $('.js-info').show()
                    $('.js-numero').html(numero)

                    calcul()
                }
                else
                {
                    $('.js-info').hide()

                }

            },
            error: function (data) {
                alert("une erreur c'est produite")
            }
        });

    }

    function calcul()
    {
        $('#facture_avoir_totalHT').on('keyup keypress blur change',function(){
            const result = $(this).val()

            if(result > 0 && result != "")
            {
                const res = (result * 0.2).toFixed(2)
                $('#facture_avoir_tva').val(res)

                const total = parseFloat(result) + parseFloat(res)

                $('#facture_avoir_totalTtc').val(total.toFixed(2))
            }
            else
            {
                $('#facture_avoir_tva').val(0)
                $('#facture_avoir_totalTtc').val(0)

            }
        })
    }

})