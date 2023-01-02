$(function(){

    let count = 1

    $('#nombreShop').val(0)
    $('#ajouterShop').click(function(e){
        e.preventDefault()

        $('.btnShop').show()
        $('#nombreShop').val(count)

        const html = `
            <div class="col-md-12 mt-3 infoBoutique p-3 border rounded shadow p-2">
                <div class="row">
                    <div class="col-md-12">
                        <a class="delete btn btn-danger btn-sm float-right" href=""><i class="os-icon os-icon-ui-15"></i></a>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="form-group col-md-12">
                        <label for="shop${count}">Nom de la boutique</label>
                        <input type="text" class="form-control" id="shop${count}" required name="shop_${count}">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="nom${count}">Nom responsable</label>
                        <input type="text" class="form-control" id="nom${count}"  name="nom_${count}">
                    </div>
                    <div class="col-md-6">
                        <label for="prenom${count}">Prénom responsable</label>
                        <input type="text" class="form-control" id="prenom${count}"  name="prenom_${count}">
                    </div>
                </div>
                
                <div class="form-group row">
                    <div class="col-md-6">
                        <label for="email${count}">Email</label>
                        <input type="email" class="form-control" id="email${count}"   name="email_${count}">
                    </div>
                    <div class="col-md-6">
                        <label for="telephone${count}">Téléphone</label>
                        <input type="text" class="form-control" id="telephone${count}"   name="telephone_${count}">
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-3">
                        <label for="adresse${count}">Adresse</label>
                        <input type="text" class="form-control" id="adresse${count}" required  name="adresse_${count}">
                    </div>
                    <div class="col-md-3">
                        <label for="codePostal${count}">Code postale</label>
                        <input type="text" class="form-control" id="codePostal${count}" required  name="codePostal_${count}">
                    </div>
                    <div class="col-md-3">
                        <label for="ville${count}">Ville</label>
                        <input type="text" class="form-control" id="ville${count}" required name="ville_${count}">
                    </div>
                    <div class="col-md-3">
                        <label for="commune${count}">Commune</label>
                        <input type="text" class="form-control" id="commune${count}"  name="commune_${count}">
                    </div>
                </div>
            </div>
        `
        $('#shop').append(html)
        count++

        $(document).on('click', '.delete', function(e){
            e.preventDefault();
            count -= 1;
            $(this).closest('.infoBoutique').remove();
        });
    })

    $("#shopInfo").submit(function(e){
        e.preventDefault()
        
        $('#spin').show();

        const form = $(this)
        const url = form.attr('action')
        console.log(url)
        const datas = form.serialize()

        $.ajax({
            url: url,
            type: 'post',
            data: datas,
            success: function(data){
               if(data.effectuer)
               {
                    $('.ajoutShopOk').show()
                    $("html, body").animate({ scrollTop: 0 }, "slow");

                    $('#spin').hide();

                    setTimeout(function(){
                        window.location.reload();
                    }, 2000);
               }
            }
        })
    })

    $('.deleteShop').click(function(e){
        e.preventDefault()

        const boutiqueId = $(this).data('id')
        const supprimer = confirm("Voulez-vous vraiment supprimer cette boutique?");
        const url = $(this).attr('href')

        if(supprimer){
            $.ajax({
                url: url,
                type: 'post',
                data: {id: boutiqueId},
                success: function(data){
                   $(`#${data.supprimer}`).remove()
                }
            })
        }

    })
})