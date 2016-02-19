function seleccionar(t,internal_name){
	var class_name = t.className,
		seleccionados = document.getElementsByClassName("seleccion_store"),
		store_code = document.getElementById('store_code_selected');
	
	for (i = 0; i < seleccionados.length; i++) {
		seleccionados[i].className = seleccionados[i].className.replace(/\bseleccion_store\b/,'');
	}

	if(class_name.search("seleccion_store") == -1){
		t.className += "seleccion_store";
		store_code.value = internal_name;
	}		
};

function loadImage (t) {
	var input = document.getElementById('p_method_compropago');

	if(input.checked){
		t.hide();
	} else {
		t.show();
	}
};

window.onload = function(){	
	$$("#co-payment-form input[type=radio]").each(function(input){
        input.observe("click", function(){
            var input = document.getElementById('p_method_compropago'),
            	image = document.getElementById('image_providers');
            
			if(input.checked){
				image.hide();
			} else {
				image.show();
			}
		})
	})
}
