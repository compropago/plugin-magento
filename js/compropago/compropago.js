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
