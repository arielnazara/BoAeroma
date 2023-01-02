let decls = [];
let stop = false;
let search = false;
let searchRequestClick = null;
let searchRequest = null;

checkboxClick()
addCart()

$('.hautPage').click(function (e) {
    e.preventDefault();
    $('html, body').animate({
        scrollTop: $(".haut").offset().top
    }, 2000)
})


$('.gamme').click(function (e) {
    e.preventDefault()

    let value = $(this).data('val')

    if($(this).hasClass("btn-info"))
    {
        value = null
        $(this).removeClass("btn-info")
        $(this).addClass("btn-outline-info")

    }

    $('.gamme').each((index, gamme)=>{

        if(gamme.dataset.val == value)
        {
            gamme.classList.add("btn-info")
            gamme.classList.remove("btn-outline-info")
            gamme.firstElementChild.style.display = 'inline'
        }
        else
        {
            gamme.classList.remove("btn-info")
            gamme.classList.add("btn-outline-info")
            gamme.firstElementChild.style.display = 'none'
        }

    })

   

    if (value) {

        search = true

        $('.spinner').hide();
        $(".container").css({ "opacity": 0.5 });

        $('.centerSpinner').css({ 'display': 'block' });

        $.ajax({
            url: $(this).attr('href'),
            type: 'POST',
            data: { id: value },
            success: function (data) {

                if (data.length > 0) {
                  
                    $('.resultat').html('')

                    
                    dataProcessing(data, false, true)
                  
                    checkboxClick()

                    addCart()

                } else {
                    $('.resultat').html('')
                    $('.resultat').append('<p>Aucun resultat</p>').css({'margin': 'auto'}).show('slow')
                }

                $('.searchResult').hide();
                $('.newProduct').hide();
                $('.produitCart').hide();
                $('.produitTrouver').hide();
                $(".container").css("opacity", 1);
                $('.centerSpinner').hide();

            },
            error: function (data) {
                console.log('');
            }
        });

    } else {
        search = false
        decls = [];
        $('.result').remove();
        $('.produitCart').show('slow');
        $('.newProduct').show('slow');
        $('.centerSpinner').hide();
        $('.produitTrouver').show();
        $('.fa-times-circle').hide()
    }

});


$('#chercher').click((e) => {
    e.preventDefault();
    let aChercher = $('#search').val();

    if (aChercher.length > 0) {

        $("#entitiesNav").hide();
        $('.spin').hide();
        search = true;

        $('.spinner').hide();
        $(".container").css({ "opacity": 0.5 });

        $('.centerSpinner').css({ 'display': 'block' });

        $.ajax({
            type: 'POST',
            url: $('.search-form').attr('action'),
            data: {
                val: aChercher
            },
            dataType: "json",
            success: function (data) {
                $("#entitiesNav").hide();
                $('.resultatRecherce').remove();
                $('.error').remove();

                $("#entitiesNav").hide();
                if (data.length > 0) {

                    dataProcessing(data, false, false, [false, true])

                    checkboxClick()

                    addCart()

                    $('.searchResult').show();
                    $('.resultat').hide();
                    $('.newProduct').hide();
                    $('.produitCart').hide();
                    $('.produitTrouver').hide();
                    $(".container").css("opacity", 1);
                    $('.centerSpinner').hide();

                    
                } else {
                    html = ''
                    $('.error').remove();
                    html += "<div class ='text-center error mt-5 text-secondary col-md-12'> <h4 class='text-muted'>Aucun resultat.</h4> </div>  ";
                    $('.searchResult').show();
                    $('.resultat').hide();
                    $('.newProduct').hide();
                    $('.produitCart').hide();
                    $('.produitTrouver').hide();
                    $(".container").css("opacity", 1);
                    $('.centerSpinner').hide();
                    $(html).hide().appendTo('.searchResult').show('slow');
                }
            },
            error: function (data) {
                console.log(data)
            }
        });
    }

})

$('#search').keyup(function () {
    $('#entitiesNav').hide();
    let minlength = 1;
    let that = $(this);
    let value = $(this).val();

    let entitySelector = $("#entitiesNav").html('');


    if (value.length >= minlength) {
        $('.spin').show();
        $('.produitTrouver').show('slow');
        $(".resultat").hide();

        if (searchRequest != null) {
            searchRequest.abort();
        }

        searchRequest = $.ajax({
            type: "POST",
            url: that.closest('form').attr('action'),
            data: {
                val: value
            },
            dataType: "json",
            success: function (msg) {

                if (value == $(that).val()) {
                    if (msg.length > 0) {
                        $.each(msg, function (key, val) {
                            //entitySelector.show('slow');
                            $('.spin').hide();
                            entitySelector.append(`<a href="" data-id="${val.id}" class="trouver p-1"> ${val.nom} <a>`).show();
                            entitySelector.css('border', '1px solid #cad1d7');

                            $('.trouver').click(function (e) {
                                e.preventDefault();

                                if (searchRequestClick != null) {
                                    searchRequestClick.abort();
                                }

                                search = true;
                                $('.spinner').hide();
                                $(".container").css({ "opacity": 0.5 });
                                $('.centerSpinner').css({ 'display': 'block' });

                                searchRequestClick = $.ajax({
                                    type: "POST",
                                    url: $('#entitiesNav').data('route'),
                                    data: {
                                        val: $(this).data('id')
                                    },
                                    dataType: "json",
                                    success: function (data) {
                                        $('.resultSearch').remove();
                                        dataProcessing(data, false, false, [true, false])

                                        checkboxClick()
                    
                                        addCart()

                                        $('.searchResult').hide();
                                        $('.newProduct').hide();
                                        $('.produitCart').hide();
                                        $('.trouver').remove();
                                        $(".container").css("opacity", 1);
                                        $('.centerSpinner').hide();
                                        entitySelector.hide();
                                        

                                    }
                                });

                            })
                        });
                    } else {
                        $('.spin').hide();
                        entitySelector.append('<small class="p-1">Aucun resultat</small>').show()
                        entitySelector.css('border', '1px solid #cad1d7');
                    }

                }
            }
        });

    } else {
        $('.spin').hide();
        entitySelector.hide();
        search = false;
        decls = [];
        $('.produitTrouver').hide();
        $('.produitCart').show('slow');
        $(".newProduct").show();
        $('.searchResult').hide();
    }
});


$(window).scroll(function () {

    let height = $(window).height() - 300;

    if ($(window).scrollTop() > height) {
        $('.hautPage').show();
    } else {
        $('.hautPage').hide();
    }

    if (decls.length === 0 && search == false) {

        if ($(window).scrollTop() == $(document).height() - $(window).height()) {

            if (!stop) {
                $('.spinner').show();

                $.ajax({
                    url: $('.newProduct').data('route'),
                    type: 'GET',
                    dataType: 'json',
                    success: function (data) {

                        if (!data.fin) {
                            
                            dataProcessing(data, true)
                            
                            checkboxClick()
                            
                            addCart()

                           
                        } else {
                            stop = true;
                            $('.spinner').hide();
                        }
                    },
                    error: function (data) {
                        console.log('');
                    }
                });
            } else {
                $('.spinner').hide();
            }

        }
    } else {
        $('.spinner').hide();
    }
});

function checkboxClick()
{
    
    $('input[type=checkbox]').unbind('click').bind('click' ,function(){ 

        let declinaison = $(this).val();
    
        if($(this).is(':checked')){

            $('.div'+declinaison +'').show('slow');
            $('.quantite'+ declinaison +'').attr('required', true);
                
        }else{
            $('.div'+ declinaison +'').hide('slow');
                
            $('.quantite'+ declinaison +'').val('').removeAttr('required');
        }
    })

    if($('.alert').is(':visible')){
        
        $('.alert').delay(3000).slideUp("slow");
    }
}

function dataProcessing(data, scroll = false, filter = false, recherche = [])
{
    let html = '';
    let info = parseInt($('.newProduct').data('info'));
    let image = $('.image').data('route');

    let apercu = $('.apercu').data('route');
    apercu = apercu.slice(0, apercu.lastIndexOf("0"));

    $.each(data,function (index, val) {
        
        let declinaison = null

        let tarif = null

        if(val.tarif != null)
        {

            if(val.tarif.memeTarif)
            {
                if(val.typeClient.toLowerCase() == "détaillant")
                {

                    tarif = val.tarif.prixDeReferenceDetaillant
                }
                else
                {
                    tarif = val.tarif.prixDeReferenceGrossiste
                }
            }
            else
            {
                tarif = val.prixDeclinaison
            }

        }
        else
        {
            tarif = null
        }

        if(val.principeActif)
        {

            
            declinaison = val.declinaison.map(function(declinaison){
                
                return `
                    <div class="row mt-3">
                        <div class="form-check decli col-sm-2 ml-3 ">

                            <input type="checkbox" id="${val.id}_${declinaison}" class="form-check-input " name="${declinaison.replace(',', '')}" value="${declinaison.replace(',', '')}">
                            <label class="form-check-label " for="${val.id}_${declinaison}" class="${declinaison.replace(',', '')}">${declinaison}</label> <br>

                        </div>
                        <div class="col-sm-2 prix mb-2">
                            <span class="badge badge-success display-4" > ${tarif && tarif[`${declinaison}`] ? tarif[`${declinaison}`] : tarif ? tarif : '0' } €</span> <br>
                        </div>
                
                        <div class="col-sm-6" >
                            <div class="form-group div${declinaison.replace(',', '')}"  style="display:none;" >
                                <div class="row">
                                    <div class="col-sm-4 float-right">
                                        <label style="text-decoration: underline;">Quantité</label>
                                    </div>
                                    <div class="col-sm-8">
                                        <input type="number" min="0" class="form-control quantite${declinaison.replace(',', '')} " name="quantite-${declinaison.replace(',', '')}" placeholder="Quantité"  >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    `
            })

            declinaison = declinaison.join('')
        }
        else
        {
            declinaison = `
                
                <span class="badge badge-success display-4 float-left" > ${ tarif ? tarif : '0' } €</span> <br>
                <div class="col-sm-6 mt-3" >
                    <div class="form-group " >
                        <div class="row">
                            <input type = "hidden" value = "sans">
                            <div class="col-sm-4 float-right">
                                <label style="text-decoration: underline;">Quantité</label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" min="0" required class="form-control quantiteSansDecl " name="quantiteSansDecl" placeholder="Quantité" required  >
                            </div>
                        </div>
                    </div>
                </div>
            `
        }

        let imageHtml = ''
        html += '<div class="col-md-4 cart d-flex">';
        html += '<div class="card shadow" style="width: 20rem;">';

        if (val.imageName) {
            imageHtml += '<div class="form-group col-md-4 mt-1 text-center">';
            imageHtml += `<img class='card-img-top rounded' style='width: 250px' src=' ${image}${val.imageName} ')}}' alt='${val.reference}'>`;
            imageHtml += '</div>';

            html += imageHtml
        }

        html += '<div class="card-body text-center mb-4">';
        html += `<p> ${val.nom} </p>`;
        html+=`
        
            <a href="#" data-toggle="modal" data-target=".modal${val.id}"><i class='fas fa-search'></i> Detail</a>

            <div class="modal fade modal${val.id}" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header mb-3">
                            <h2 class="text-center">${val.nom}</h2>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="row pb-3 px-3">
                            <div class="col-md-4">
                                ${imageHtml}
                            </div>

                            <div class="col-md-8">
                                
                                <form method="post" id = "form${val.id}">
                                    <input type="hidden" name="produit" value = "${val.id}">

                                    ${ val.principeActif ?
                                        (
                                            `
                                                <h6>Produit décliné avec de ${val.principeActif.principeActif}</h6>
                                                <p> Liste des déclinaisons disponible pour cette produit </p>
                                                <small> (Veuillez cochez le ou les déclinaison que vous voulez choisir) </small>
                                                <div class=" col-md-12 message" ></div>
                                                <div class=" col-md-12 erreur"></div>
                                            ` 
                                        )
                                        : 
                                            `
                                                <p>Produit sans déclinaison</p>
                                                <div class=" col-md-12 message" ></div>
                                            `
                                    }


                                    ${declinaison.length > 0 ?
                                        (
                                            declinaison
                                        )
                                        : 
                                        ''
                                    }

                                    
                                    <div class="text-center mt-3 mb-5">
                                        <button class="btn btn-primary ajouter">
                                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                            <div class="spinner-border spinner-border-sm" role="status" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div> 
                                            Ajouter
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        `
        html += '</div>';
        html += '</div>';
        html += '</div>';

       
    });

    //$('.newProduct').append(html);

    if(filter)
    {
        $('.resultat').show()
        $(html).hide().appendTo('.resultat').show('slow');
    }
    else if(scroll)
    {
        $(html).hide().appendTo('.newProduct').show('slow');
    }
    else if(recherche[1])
    {
        $(html).hide().appendTo('.searchResult').show('slow');
    }
    else if (recherche[0])
    {
        $(html).hide().appendTo('.produitTrouver').show().slideDown('slow');
    }
}


function addCart()
{
    $('.ajouter').unbind('click').bind('click',function(e){
        e.preventDefault()

        $('.message').removeClass("alert alert-success").html('')

        const form = $(this).parents('form:first');
        const btn = $(this)
        const url = $('#cartRoute').val()

        let error = null

        let checked = false

    
        form.find(':checkbox').each(function(index, value){
            if(value.checked)
            {
                checked = true
            }
        })

        form.find(':input[type="number"]').each(function(index, value){
           
            if(value.required && value.value == "")
            {
                error = "Veuillez renseigner une quantitée"
                checked = false
            }
            
        })
        
        if(!checked)
        {
            form.find(':hidden').each(function(index, hid){
                if(hid.attributes.value)
                {
                    if(hid.attributes.value.nodeValue == "sans")
                    {
                        checked = true
                    }
                }
            })
        }


    
        if(checked && !error)
        {

            btn.prop("disabled",true)

            btn.find('.fa').hide()

            btn.find('.spinner-border').show()

            $('.erreur').hide()
            $.ajax({
                url: url,
                type: 'post',
                data: form.serialize(),
                dataType: 'json',
                success: function (data) {
    
                    btn.prop("disabled",false)

                    btn.find('.fa').show()

                    btn.find('.spinner-border').hide()

                    form.find(":checkbox").prop("checked", false)
                    
                    form.find(':input[type="number"]').val('')

                    $('#shoppingCart').html(data.panier)
                    
                    checkboxClick()
                    
                    form.find('.message').show().addClass("alert alert-success").html(data.success)
    
                    setTimeout(function(){
                        form.find('.message').hide().removeClass("alert alert-success")
                    },5000);
                    
                },
                error: function (data) {
                    console.log('');
                }
            });
        }
        else if(error)
        {
            form.find('.erreur').addClass("alert alert-danger").html(error)
        }
        else
        {
            form.find('.erreur').addClass("alert alert-danger").html("Veuillez choisir au moin un déclinaison")
        }
    })
    
}






