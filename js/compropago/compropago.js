function seleccionar(elem){
    allproviders = document.querySelectorAll(".cp-provider");
    for(var x = 0; x < allproviders.length; x++){
        allproviders[x].classList.remove("cp-selected");
    }
    elem.classList.add("cp-selected");
    provider = elem.getAttribute('data-provider');
    document.getElementById('store_code_selected').value = provider;
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

