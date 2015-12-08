#Plugin para Magento 1.7.x, 1.8.x y 1.9.x

Este modulo provee el servicio de ComproPago para poder generar intensiones de pago dentro de la plataforma Magento.

* [Instalación](#install)
* [¿Cómo trabaja el modulo?](#howto)
* [Configuración](#setup)
* [Sincronización con los webhooks](#webhook)
* [Pasos para actualizar plugin](#upgrade)


<a name="install"></a>
## Instalación:

1.- Descargar el Archivo **.tgz** del siguiente link [https://s3.amazonaws.com/compropago/plugins/magento/ComproPago-1.0.0.tgz](https://s3.amazonaws.com/compropago/plugins/magento/ComproPago-1.0.0.tgz)

2.- En el panel de administración de Magento ingresamos y nos dirigimos a **System -> Magento Connect -> Magento Connect Manager** 

3.- Cargamos el archivo en la parte que dice **Direct package file upload** y oprimimos el botón **Upload**.

![Carga Archivo](https://cloud.githubusercontent.com/assets/1311937/11578093/02a4da92-99e8-11e5-8ce9-40a54eb3d0af.png)

4.- Verificamos que la instalación fue correcta y procedemos a oprimir el botón de **Refresh** para actualizar la pantalla.

![pluginsuccess](https://cloud.githubusercontent.com/assets/1311937/11578179/acc31ab6-99e8-11e5-8113-a0dbed22dda3.png)

5.- En el panel de administración ir a **System -> Cache Management** y limpiar la cache de todos los directorios. 

![cache](https://cloud.githubusercontent.com/assets/1311937/11644976/3f863196-9d16-11e5-9068-03120e99720c.png)<br />

6.- Ir a **System -> IndexManagement** seleccionar todos los directorios y dar click en Reindex Data y Submit.

![index](https://cloud.githubusercontent.com/assets/1311937/11644989/4fdfb65c-9d16-11e5-9a3b-51e812c1c01a.png)

---
<a name="howto"></a>
## ¿Cómo trabaja el modulo?
Una vez que el cliente sabe que comprar y continua con el proceso de compra entrará a la opción de elegir metodo de pago justo aqui aparece la opción de pagar con ComproPago, seleccionamos el establecimiento de nuestra conveniencia y le damos continuar

![pago](https://cloud.githubusercontent.com/assets/1311937/11578379/880b359e-99ea-11e5-8967-f6e43e604ea5.png) <br />

Al completar el proceso de compra dentro de la tienda el sistema nos proporcionara un recibo de pago como el siguiente, solo falta realizar el pago en el establecimiento que seleccionamos.

![success](https://cloud.githubusercontent.com/assets/1311937/11578435/269f7846-99eb-11e5-9111-a721863fee00.png) <br />

Una vez que el cliente genero su intención de pago, dentro del panel de control de ComproPago la orden se muestra como "PENDIENTE" esto significa que el usuario esta por ir a hacer el deposito.

![ComproPago](https://raw.github.com/compropago/plugin-magento/master/README.img/19.png) 

---
<a name="setup"></a>
## Configurar ComproPago

1.- Para iniciar la configuración ir a **System -> Configuration -> Sales -> Payment Methods**. Seleccionar **ComproPago**.

![paymentmethod](https://cloud.githubusercontent.com/assets/1311937/11578545/3f896320-99ec-11e5-9900-f9268b05fc58.png)

***Nota:*** La opción de **Habilitar Logos** es para mostrar las imagenes de los establecimientos con los que procesamos pagos

2.- Agregar la **llave privada** y **llave pública**, esta se puede encontrar en el apartado de configuración dentro del panel de control de ComproPago. [https://compropago.com/panel/configuracion](https://compropago.com/panel/configuracion)
<br />
![ComproPago](https://raw.github.com/compropago/plugin-magento/master/README.img/7.png) 


---

<a name="webhook"></a>
## Sincronización con la notificación Webhook

1.- Ir al area de **Webhooks** en ComproPago [https://compropago.com/panel/webhooks](https://compropago.com/panel/webhooks)

2.- Introducir la dirección: ***[direcciondetusitio.com]***/index.php/compropago/webhook/ 
   Para el caso en donde exista un idioma instalado la dirección deberia ser: ***[direcciondetusitio.com]***/index.php/compropago/webhook/

![ComproPago](https://raw.github.com/compropago/plugin-magento/master/README.img/9.png)

3.- Dar click en el botón "Probar" y verificamos que el servidor de la tienda esta respondiendo, debera aparecer el mensaje de "Order not valid"
![webhook](https://cloud.githubusercontent.com/assets/1311937/11578636/d1597bf0-99ec-11e5-8def-fc44e6ca603e.png)

Una vez completado estos pasos el proceso de instalación queda completado.

---

<a name="upgrade"></a>
## Pasos para actualizar plugin

1.- Si ya tenemos instalado Compropago en su versión anterior [v0.0.1](https://github.com/compropago/plugin-magento-deprecated){:target="_blank"}, para eliminarlo vamos a realizar un borrado de los siguientes archivos.

![files](https://cloud.githubusercontent.com/assets/1311937/11634984/5e9a7a66-9cd9-11e5-8583-c93bef106696.png)

2.- Una vez que eliminamos los archivos anteriores, vamos al panel de administración de Magento, **System -> Cache Management** y limpiar la cache de todos los directorios. 

![Cache Management](https://raw.github.com/compropago/plugin-magento/master/README.img/3.png)<br />

3.- Ahora eliminamos el webhook que dimos de alta en nuestro panel de Compropago cuando instalamos el plugin anterior.

![webhook2](https://cloud.githubusercontent.com/assets/1311937/11635215/d5a410f8-9cda-11e5-8c36-bac2f35d3b60.png)

4.- Ya que tenemos eliminado el Plugin anterior, procedemos a realizar la instalación del nuevo plugin. [Vease Aquí](#install)



