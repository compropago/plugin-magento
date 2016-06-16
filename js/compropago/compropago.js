/**
 * @author Eduardo Aguilar <eduardo.aguilar@compropago.com>
 * @author Oswaldo Lopez
 */

function seleccionar(t){
    provider = t.getAttribute("data-provider");
    seleccionados = document.querySelectorAll("#cp-provider");
    store_code = document.querySelector('#store_code_selected');

    for(var x = 0; x < seleccionados.length; x++){
        seleccionados[x].setAttribute("style",
            "box-shadow: 0px 0px 0px 0px transparent;"+
            "-webkit-box-shadow: 0px 0px 0px 0px transparent;"+
            "-moz-box-shadow: 0px 0px 0px 0px transparent;"+
            "margin: 6px !important;"
        );
    }

    for (var i = 0; i < seleccionados.length; i++) {
        seleccionados[i].className = seleccionados[i].className.replace(/\bseleccion_store\b/,'');
    }

    t.setAttribute("style",
        "box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"+
        "-webkit-box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"+
        "-moz-box-shadow: 0px 0px 2px 4px rgba(0,170,239,1);"+
        "margin: 6px !important;"
    );

    if(t.className.search("seleccion_store") == -1){
        t.className += "seleccion_store";
        store_code.value = provider;
    }
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
