$(function () {
    let $man = $('.man'), $mab = $('.mab'), $men= $('.men');
    let $amount = $('.amount_ht'), $fee = $('.fee'), $totalht= $('.total_ht'), $taxe = $('.taxe'), $totalttc= $('.total_ttc'), $deposit = $('.deposit');
    let quantity = 0, price = 0.00, totalht, totalttc;
    let $btnSave = $('.btn-save'), $totalunit_element = $('.totalunit'), totalunit = 0.00;
    let weight_m = 0.025,  weight = 0.00, fee = 0.00;
    let $checkIsPro = $('.ispro');

    if ($checkIsPro) {
        $checkIsPro.click(function () {
            if ($(this).prop('checked') === true) {
                $('.healthPro').val(1);
                if (!$('.is_signed').val()) {
                    calculQuote();
                }
            } else {
                $('.healthPro').val(0);
                if (!$('.is_signed').val()) {
                    calculQuote();
                }
            }
        });
    }

    $('.man, .mab, .men').on('change keyup', function () {
        if ($(this).val() < 0) {
            $(this).val('0');
        }

        $amount.text(0);
        $totalht.text(0);
        $fee.text(0);
        $taxe.text(0);
        $totalttc.text(0);
        $deposit.text(0);
    });

    $('.man').on('keyup change', function () {
        $('input[name="man"]').val($(this).val());
    });
    $('.mab').on('keyup change', function () {
        $('input[name="mab"]').val($(this).val());
    });
    $('.men').on('keyup change', function () {
        $('input[name="men"]').val($(this).val());
    });

    $('.start-calcul').click(function () {
        calculQuote();
    });

    function groupingNumbers(number) {
        let n = number.toString().split('.');
        n[0] = n[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return n.join(",");
    }

    function calculQuote() {
        let $healthPro = $('.healthPro').val();
        let man = (isNaN(parseInt($man.val(), 10))) ? 0 : parseInt($man.val(), 10);
        let mab = (isNaN(parseInt($mab.val(), 10))) ? 0 : parseInt($mab.val(), 10);
        let men = (isNaN(parseInt($men.val(), 10))) ? 0 : parseInt($men.val(), 10);
        let amount;

        quantity = man + mab + men;
        $('input[name="quantity"]').val(quantity);

        let tarifProposer = $('.tarif').val(); //tarif proposé

        let prixProposer = tarifProposer;
        price = calculPrice($healthPro, quantity, prixProposer);
        $('input[name="price"]').val(price);
        amount = quantity*price;
        $amount.text(groupingNumbers(amount.toFixed(2)));
        $('input[name="amount"]').val(amount.toFixed(2));

        weight = quantity * weight_m;
        fee = (quantity > 5000) ? 0 : calculFee(Math.round(weight));
        $fee.text(groupingNumbers(fee));
        $('input[name="fee"]').val(fee);

        totalht = amount + fee;
        $totalht.text(groupingNumbers(totalht.toFixed(2)));
        $('input[name="totalht"]').val(totalht.toFixed(2));

        $taxe.text(groupingNumbers((totalht*0.055).toFixed(2)));
        $('input[name="taxe"]').val((totalht*0.055).toFixed(2));

        totalttc = totalht + (totalht*0.055);
        $totalttc.text(groupingNumbers(totalttc.toFixed(2)));
        $('input[name="totalttc"]').val(totalttc.toFixed(2));

        let $depositAmount = totalht * 0.35;
        $deposit.text(groupingNumbers($depositAmount.toFixed(2)));

        if ($totalunit_element) {
            let man_last = (isNaN(parseInt($('.man_last').val(), 10))) ? 0 : parseInt($('.man_last').val(), 10);
            let men_last = (isNaN(parseInt($('.men_last').val(), 10))) ? 0 : parseInt($('.men_last').val(), 10);

            if (man_last === man && men_last === men) {
                let $last_totalttc = $('.last_totalttc'), last_totalttc = $last_totalttc.val();
                let totalttc_str = totalttc.toString();
                totalttc_str = totalttc_str.split('.')[0];
                last_totalttc = last_totalttc.split(',')[0];

                if (last_totalttc === totalttc_str) {
                    $btnSave.text('Enregistrer les changements');
                    $btnSave.removeClass('btn-info');
                    $btnSave.addClass('btn-primary');
                    $('.new_quote').val(0);
                } else {
                    $('.new_quote').val(1);
                    $btnSave.text('Générer un nouveau devis');
                    $btnSave.removeClass('btn-primary');
                    $btnSave.addClass('btn-info');
                }
            } else {
                $('.new_quote').val(1);
                $btnSave.text('Générer un nouveau devis');
                $btnSave.removeClass('btn-primary');
                $btnSave.addClass('btn-info');
            }
        }

        if (amount > 0) {
            $('input[type="submit"]').removeAttr('disabled');
            $('input[type="submit"]').removeClass('btn-secondary');
            $('input[type="submit"]').addClass('btn-success');
        } else {
            $('input[type="submit"]').attr('disabled');
            $('input[type="submit"]').removeClass('btn-success');
            $('input[type="submit"]').addClass('btn-secondary');
        }
    }

    function calculFee($weight) {
        switch (true) {
            case $weight < 4:
                fee = 20.31;
                break;
            case $weight>=5 && $weight <= 9:
                fee = 28.02;
                break;
            case $weight>=10 && $weight <= 14:
                fee = 35.78;
                break;
            case $weight >=15 && $weight <= 19:
                fee = 43.51;
                break;
            case $weight >=20 && $weight <= 29:
                fee = 51.23;
                break;
            case $weight>=30 && $weight <= 39:
                fee = 62.69;
                break;
            case $weight>=40 && $weight <= 49:
                fee = 74.17;
                break;
            case $weight>=50 && $weight <= 59:
                fee = 85.62;
                break;
            case $weight>=60 && $weight <= 69:
                fee = 97.08;
                break;
            case $weight >=70 && $weight <= 79:
                fee = 108.54;
                break;
            case $weight >=80 && $weight <= 89:
                fee = 120.00;
                break;
            case $weight>=90 && $weight <= 99:
                fee = 131.46;
                break;
            case $weight>=100 && $weight <= 1000:
                fee = 131.46;
                break;
        }

        return  fee;
    }

    function calculPrice(healthPro, $quantity, prixProposer) {
        if( prixProposer > 0 ){
            //console.log(prixProposer);
            return prixProposer;
        }
        if (healthPro == 1) {
            return 4.90;
        } else {
            switch (true) {
                case $quantity < 499:
                    price = 5.90;
                    break;
                case $quantity>=500 && $quantity <= 4999:
                    price = 5.80;
                    break;
                case $quantity>=5000 && $quantity <= 9999:
                    price = 5.70;
                    break;
                case $quantity>=10000 && $quantity <= 19999:
                    price = 5.50;
                    break;
                case $quantity>=20000 && $quantity <= 49999:
                    price = 5.40;
                    break;
                case $quantity>=50000 && $quantity <= 99999:
                    price = 5.30;
                    break;
                case $quantity>=100000 && $quantity <= 199999:
                    price = 4.90;
                    break;
                case $quantity>200000:
                    price = 4.50;
                    break;
            }

            return  2.79;
        }
    }
});