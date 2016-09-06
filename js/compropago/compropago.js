function seleccionar(elem){
    console.log("entra seleccion");
    allproviders = document.querySelectorAll(".cp-provider img");

    for(var x = 0; x < allproviders.length; x++){
        allproviders[x].classList.remove("cp-selected");
    }

    elem.classList.add("cp-selected");

    provider = elem.getAttribute('data-provider');
    document.querySelector('store_code_selected').value = provider;
}


window.onload = function(){
    $$("#co-payment-form input[type=radio]").each(function(input){
        input.observe("click", function(t){
            if(t.getAttribute("id") == "cp-provider"){
                seleccionar(t);
            }
        });
    });
};


document.addEventListener("click", someListener);
var flagC = false;

function setBack(types) {
    var dropShops = document.getElementById("store_code_selected");
    switch (types) {
        case 'oxxo':
            back = 'https://compropago.com/assets/print/receipt-oxxo-btn-mini.png';
            break;
        case 'seven_eleven':
            back = 'https://compropago.com/assets/print/receipt-seven-btn-mini.png';
            break;
        case 'coppel':
            back = 'https://compropago.com/assets/print/receipt-coppel-btn-mini.png';
            break;
        case 'chedraui':
            back = 'https://compropago.com/assets/print/receipt-chedraui-btn-mini.png';
            break;
        case 'extra':
            back = 'https://compropago.com/assets/print/receipt-extra-btn-mini.png';
            break;
        case 'farmacia_esquivar':
            back = 'https://compropago.com/assets/print/receipt-esquivar-btn-mini.png';
            break;
        case 'farmacia_benavides':
            back = 'https://compropago.com/assets/print/receipt-benavides-btn-mini.png';
            break;
        case 'elektra':
            back = 'https://compropago.com/assets/print/receipt-elektra-btn-mini.png';
            break;
        case 'casa_ley':
            back = 'https://compropago.com/assets/print/receipt-ley-btn-mini.png';
            break;
        case 'pitico':
            back = 'https://compropago.com/assets/print/receipt-pitico-btn-mini.png';
            break;
        case 'telecomm':
            back = 'https://compropago.com/assets/print/receipt-telecomm-btn-mini.png';
            break;
        case 'farmacia_abc':
            back = 'https://compropago.com/assets/print/receipt-abc-btn-mini.png';
            break;
    }
    dropShops.style.backgroundImage = 'url(\'' + back + '\')';
}

function someListener(event) {
    var element = event.target,
        dropShops = document.getElementById("store_code_selected"),
        back = '';
    if (dropShops) {
        setBack(dropShops.value.toLowerCase());
    }
    if (element.classList.contains("provider-select")) {
        if (flagC === false) {
            flagC = true;
            dropShops.addEventListener("change", function() {
                setBack(dropShops.value.toLowerCase());
            });
        }
    }
}
