$(function () {

    let quantite = 0;
    let prix = 0;
    let PrixDecl = 0;
    let totalPrix = 0;
    let poids = 0;
    let frais = 0;
    $('select').change(function () {
        totalPrix = 0;
        $('.amount_ht').text(totalPrix);
    });

    calculer()

    function calculer()
    {

        $('.calculer').click(function (e) {
    
            e.preventDefault();

            $('.erreur').html('')

            let erreur = []
            let quantiteTotal = 0;
            totalPrix = 0;
            poids = 0;
            fraisDecl = 0;
            fraisSans = 0;
            let existDecl = false;

            $('.declinaison').each(function () {
    
                let declinaison = $(this).val();
                let ref = $(this).data('productid');

                if ($(this).is(':checked')) {
                    existDecl = true;
                    quantite = parseInt($('.quantite' + declinaison + ref + '').val());
                    prix = parseFloat($('.' + declinaison + ref + '').data('val'));
                    poids += parseFloat($(this).data('poids')) * quantite;

                    nom = $(`.nom_${ref}`).text()
                    
                    if(quantite == 0 || quantite == '' || isNaN(quantite))
                    {
                        erreur.push(`- Veuillez remplir le champ quantité du produit ${nom}  <br>`)
                    }

                    if(prix == 0 || prix == '' || !prix )
                    {
                        erreur.push(`- Le produit ${nom} n'a pas de prix <br>`)
                    }

                    PrixDecl = quantite * prix;
                    
    
                } else {
                    quantite = 0;
                    PrixDecl = 0;
                }
                quantiteTotal += quantite;
                totalPrix += PrixDecl;
            });
    
    
            let prixSansDecl = 0;
            let QSansDecl;
            let QTotalSansDecl = 0;
            let poidSD = 0;
            let sansDecl = 0;
            let existSans = false;
    
            $('.sansDecl').each(function () {
                let ref = $(this).data('productid')
                nom = $(`.nom_${ref}`).text()
                
                QSansDecl = parseInt($(this).val());
                
                if (QSansDecl > 0) {
                    existSans = true;
                    let id = parseInt($(this).data('id'));
    
                    let prix = parseFloat($('.sans-' + id).data('val'));
    
                    poidSD += parseFloat($(this).data('poids')) * QSansDecl;

                    if(prix == 0 || prix == '' || !prix)
                    {
                        erreur.push(`- Le produit ${nom} n'a pas de prix <br>`)
                    }
    
                    sansDecl = prix * QSansDecl;
                    prixSansDecl += sansDecl;
                    QTotalSansDecl += QSansDecl;
                }

                if(QSansDecl == 0 || QSansDecl == '' || isNaN(QSansDecl))
                {
                    erreur.push(`- Veuillez remplir le champ quantité du produit ${nom}  <br>`)
                }

               

    
            });
    
            poids += poidSD
    
    
            if (poids > 0) {
                fraisDecl = calculFrais(poids);
            }
    
            if (prixSansDecl > 0) {
    
                totalPrix += prixSansDecl;
                quantiteTotal += QTotalSansDecl;
            }
    
            
            if (!isNaN(totalPrix) && totalPrix > 0) {
                $('.amount_ht').text(totalPrix.toFixed(2));
    
                let totalHt = totalPrix + parseFloat(fraisDecl);
                let tva = totalPrix * 0.2;

                let noTva = $("#noTva").val()

                if(typeof noTva !== "undefined")
                {
                    tva = 0
                }
                
                let totalTtc = totalHt + tva;
                $('.total_ht').text(totalHt.toFixed(2));
                $('.taxe').text(tva.toFixed(2));
                $('.total_ttc').text(totalTtc.toFixed(2));
                $('.fee').text(fraisDecl.toFixed(2) + '€ HT').css('color', '#3e4b5b');
    
    
                $('input[name=quantite]').val(quantiteTotal.toFixed(2));
                $('input[name=montant]').val(totalPrix.toFixed(2));
                $('input[name=fraisDeTransport]').val(fraisDecl.toFixed(2));
                $('input[name=taxe]').val(tva.toFixed(2));
                $('input[name=totalHt]').val(totalHt.toFixed(2));
                $('input[name=totalTtc]').val(totalTtc.toFixed(2));
    
                $('.btn-lb').removeAttr('disabled').addClass('btn btn-success');
            }
           
            if(erreur.length > 0)
            {
                let uniqueItems = [...new Set(erreur)]
                $('.erreur').append(` <p> ${uniqueItems.map(function(val){return val}).join("")} </p>`)
            }
    
        });

    }

    function calculFrais($poids) {
        switch (true) {
            case $poids >= 0 && $poids <= 0.99:
                frais = 8.28;
                break;
            case $poids >= 1 && $poids <= 1.99:
                frais = 9.09;
                break;
            case $poids >= 2 && $poids <= 2.99:
                frais = 9.9;
                break;
            case $poids >= 3 && $poids <= 3.99:
                frais = 10.71;
                break;
            case $poids >= 4 && $poids <= 4.99:
                frais = 11.52;
                break;
            case $poids >= 5 && $poids <= 5.99:
                frais = 12.33;
                break;
            case $poids >= 6 && $poids <= 6.99:
                frais = 13.44;
                break;
            case $poids >= 7 && $poids <= 7.99:
                frais = 13.95;
                break;
            case $poids >= 8 && $poids <= 8.99:
                frais = 14.76;
                break;
            case $poids >= 9 && $poids <= 9.99:
                frais = 15.57;
                break;
            case $poids >= 10 && $poids <= 14.99:
                frais = 19.62;
                break;
            case $poids >= 15 && $poids <= 19.99:
                frais = 23.67;
                break;
            case $poids >= 20 && $poids <= 24.99:
                frais = 27.72;
                break;

            case $poids >= 25:
                frais = 31.77;
                break;
        }

        return frais;
    }

    checkClick()

    function checkClick()
    {
        
        $('input[type=checkbox]').click(function(){ 
            let declinaison = $(this).val();
            
            let ref = $(this).data('productid');
            
            if($(this).is(':checked')){
                $('.quantite'+ declinaison+ref +'').show('slow');
                $('.'+ declinaison+ref +'').show('slow');
            }else{
                $('.quantite'+ declinaison+ref +'').hide('slow');
                $('.'+ declinaison+ref +'').hide('slow');
                $('.quantite'+ declinaison+ref +'').val(0);
                $('.amount_ht').text(0);
                $('.total_ht').text(0);
                $('.taxe').text(0);
                $('.total_ttc').text(0);
                $('.fee').text('0€ HT').css('color', '#3e4b5b');
            }
            
        })

    }

    $('.js-ajouter').click(function(e){
        e.preventDefault();

        $('.js-spin').show()
        $(this).hide()
        
        const url = $(this).data('target')
        const client = $(this).data('client')
        const type_client = $(this).data('clienttype').toLowerCase()
        
        let produits = []

        $('.js-add').each(function(index, element){
            produits.push(element.value)
        })
        
        $('.js-add-products').text('')

        $.ajax({
            type: 'POST',
            url: url,
            data: {'produits' : produits, 'client': client},
            dataType: 'json',
            success: function (data) {
                
                let template = ""
                let countSupp = 0
                
                /*data.map(function(val)
                {
                
                    let declinaison = ""

                    let produit 

                    if("produit" in val)
                    {
                        produit = val.produit

                    }
                    else
                    {
                        produit = val
                        
                    }

                    
                    let tarifOk = val.tarif
                    let tarif
                    let prixDecl = {}
                    let messagePasDeTarifClient = ''

                    if( tarifOk != null && typeof tarifOk != "undefined" && tarifOk.client && tarifOk.client.id == client  || tarifOk != null &&  typeof tarifOk != "undefined" && !tarifOk.client )
                    {

                        if(tarifOk.memeTarif)
                        {
                            if(type_client == "grossiste")
                            {
                                tarif = tarifOk.prixDeReferenceGrossiste
                            }
                            else
                            {
                                tarif = tarifOk.prixDeReferenceDetaillant
                            }
                        }
                        else
                        {
                            prixDecl = tarifOk.activePrice[type_client]
                        }

                        if( ! tarifOk.client )
                        {
                            messagePasDeTarifClient = `
                                <div class="alert alert-warning col-sm-12">
                                    Le client n'a pas de tarif pour cette produit <br>
                                    Le tarif afficher est le tarif de référence
                                </div>
                            `
                        }
                    }
                    else
                    {
                        
                        tarif = null
                    }

                    
                    let badge
                    let prix

                    if(tarif == null && Object.keys(prixDecl).length == 0)
                    {
                        badge = 'badge-danger'
                        prix = 'AUCUN PRIX'
                    }
                    else
                    {
                        badge = 'badge-success'

                        if(Object.keys(prixDecl).length == 0)
                        {
                            prix = tarif+'€'
                        }

                    }
                    
                    let reference = produit.reference

                    reference = reference.replace(',', '').replace("'", '').replace(" ", '').replace("%", '_').replace("+", '_').replace(" ", '')

                    if(produit.declinaison.length > 0)
                    {
                        
                        declinaison += produit.declinaison.map(function(decl)
                        {
    
                            if( Object.keys(prixDecl).length > 0 )
                            {
                                
                                if(prixDecl[decl])
                                {
                                    tarif = prixDecl[decl]
                                    prix = prix = tarif+'€'
                                }
                                else
                                {
                                    return ''
                                }
                            }

                          
                            return `
                                <div class="row mb-2">
                                    <div class="form-check col-sm-3 ">
                                        <input class="form-check-input declinaison" type="checkbox" data-productid="${produit.id}" name="produit-supp-${produit.id}-${decl.replace(',', '')}" data-ref="${reference}" data-poids="${produit.poids}" value="${decl.replace(',', '')}">
                                        <label class="form-check-label" >${decl}</label> <br>
    
                                    </div>
                                    <div class="col-sm-4">
                                        <span style="display: none;" class="badge ${badge}  ${decl.replace(',', '')}${produit.id}" data-val="${tarif}">${prix}</span>
                                    </div>
                                    <div class="col-sm-5">
                                        <div class="form-group">
                                            <input type="number" style="display: none;" min="0" value="" class="form-control quantite${decl.replace(',', '')}${produit.id}" name="quantite-supp-${produit.id}-${decl.replace({',': ''})}" placeholder="Quantité" data-productid="${produit.id}">
                                        </div>
                                    </div>
                                </div>
                            `
                        }).join("")

                    }
                    else
                    {
                        declinaison += `

                            <span class="badge ${badge} sans-${produit.id}" data-val="${tarif}" >${prix}</span> <br>
                            
                            <div class="row">
                                <label for="">Quantitées</label>

                                <input type="number" name="quantite-supp-${produit.id}-${produit.id}" min="0" value="${produit.quantite}" class="form-control sansDecl" data-poids="${produit.poids}" data-id="${produit.id}" data-productid="${produit.id}">
                            </div>

                        `
                    }

                    
                    template += `
                        <div class="col-md-4 mt-3 new ">
                            <div class="card border p-4 m-2 bg-light border-light shadow" id="${produit.id}">
                                <div class="row">
                                    <div class="col-sm-10">
                                        <h5 class="card-title text-center mb-3 font-weight-bold nom_${produit.id}" >${val.nom}</h5>
                                        </div>
                                    <div class="col-sm-2">
                                        <a href="#" class="deleteNew btn btn-danger btn-sm"><i class="os-icon os-icon-ui-15"></i></a>
                                    </div>
                                    ${messagePasDeTarifClient}
                                </div>
                                <hr style="width: 90%;">

                                <div class="card-body">
                                    <input type="hidden" name="produit-supp-${countSupp}" value="${produit.id}">

                                    ${declinaison}
                                </div>

                            </div>
                        </div>

                    `

                    countSupp++
                    
                })*/

                

                $('.js-add-products').append(data.content);
                checkClick()

                $('.js-spin').hide()
                $('.js-ajouter').show()

                deleteNew()
                
                
            },
            error: function(){
                alert("une erreur c'est produite")
            }
        })

    })

    function deleteNew()
    {
        $('.deleteNew').click(function(e)
        {
            e.preventDefault()

            const ok = confirm('Voulez-vous vraiment supprimer ce produit ?')

            if(ok)
            {
                $(this).closest('.new').remove()
            }

        })
    }

    
});