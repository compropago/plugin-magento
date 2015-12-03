#Plugin para Magento 1.7.x, 1.8.x y 1.9.x

Este modulo provee el servicio de ComproPago para poder generar intensiones de pago dentro de la plataforma Magento.

* [Instalación](#install)
* [¿Cómo trabaja el modulo?](#howto)
* [Configuración](#setup)
* [Sincronización con los webhooks](#webhook)


<a name="install"></a>
## Instalación:

1. Descargar el archivo .tgz del siguiente link https://github.com/compropago/plugin-magento-tmp/archive/master.zip

2.- En el panel de administración de Magento ingresamos y nos dirigimos a System -> Magento Connect -> Magento Connect Manager 

3.- Cargamos el archivo descargado en la parte que dice "Direct package file upload" y oprimimos el botón "Upload".

4.- Verificamos que la instalación fue correcta y procedemos a oprimir el botón de "Refresh".

5.- En el panel de administración ir a System -> Cache Management y limpiar la cache de todos los directorios. 

6.- Ir a System -> IndexManagement seleccionar todos los directorios y dar click en Reindex Data y Submit.

---
<a name="howto"></a>
## ¿Cómo trabaja el modulo?
Una vez que el cliente sabe que comprar y continua con el proceso de compra entrará a la opción de elegir metodo de pago justo aqui aparece la opción de pagar con ComproPago<br /><br />

Una vez que el cliente completa su orden de compra iniciara el proceso para generar su intensión de pago, el cliente selecciona el establecimiento y recibe las instrucciones para realizar el pago.

Una vez que el cliente genero su intención de pago, dentro del panel de control de ComproPago la orden se muestra como "PENDIENTE" esto significa que el usuario esta por ir a hacer el deposito.

---
<a name="setup"></a>
## Configurar ComproPago

1. Para iniciar la configuración ir a **Extensions > Payments**. Seleccionar **Compropago Payment Method**.

2. Agregar la **llave privada** y **llave pública**, esta se puede encontrar en el apartado de configuración dentro del panel de control de ComproPago. [https://compropago.com/panel/configuracion](https://compropago.com/panel/configuracion)

---

<a name="webhook"></a>
## Sincronización con la notificación Webhook

1. Ir al area de **Webhooks** en ComproPago [https://compropago.com/panel/webhooks](https://compropago.com/panel/webhooks)

2. Introducir la dirección: ***[direcciondetusitio.com]***/index.php?route=payment/webhook
   Para el caso en donde exista un idioma instalado la dirección deberia ser: ***[direcciondetusitio.com]***/index.php?route=payment/webhook


3. Dar click en el botón "Probar" y verificamos que el servidor de la tienda esta respondiendo, debera aparecer el mensaje de "Order not valid"

---

Una vez completado estos pasos el proceso de instalación queda completado.
