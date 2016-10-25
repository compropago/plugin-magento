Plugin Magento 1.7.x, 1.8.x, 1.9.x - ComproPago
====================================================

## Descripción
Este módulo provee el servicio de ComproPago para poder generar órdenes de pago dentro de la plataforma de e-commerce Magento.

Con ComproPago puede recibir pagos en OXXO, 7Eleven más tiendas en todo México.


[Registrarse en ComproPago ] (https://compropago.com)


## Ayuda y Soporte de ComproPago

- [Centro de ayuda y soporte](https://compropago.com/ayuda-y-soporte)
- [Solicitar integración](https://compropago.com/integracion)
- [Guía para comenzar a usar ComproPago](https://compropago.com/ayuda-y-soporte/como-comenzar-a-usar-compropago)
- [Información de contacto](https://compropago.com/contacto)

## Requerimientos
* [Magento 1.7.x, 1.8.x, 1.9.x](https://magento.com/)
* [PHP >= 5.5](http://www.php.net/)
* [PHP JSON extension](http://php.net/manual/en/book.json.php)
* [PHP cURL extension](http://php.net/manual/en/book.curl.php)

## Instalación:

1. Copiar el enlace proporcionado por **Magento Connect** desde [aquí][Magento-Connect]
2. En el panel de administración de Magento ingresa y dirígete a **System -> Magento Connect -> Magento Connect Manager**
3. Pega el link obtenido de la sección **Install New Extension** y selecciona el botón **Install**.
4. Verifica que la instalación fué correcta y procede a seleccionar el botón **Refresh** para actualizar la pantalla.


## ¿Cómo trabaja el módulo?
Una vez que el cliente sabe que comprar y continúa con el proceso, seleccionará la opción de elegir el método de pago.
Aquí aparecerá la opción de pago con ComproPago, selecciona el establecimiento de su conveniencia y el botón de **continuar**.

Al completar el proceso de compra dentro de la tienda, el sistema proporcionará un recibo de pago,
por lo que solo resta realizar el pago en el establecimiento que seleccionó anteriormente.

Una vez que el cliente generó su órden de pago, dentro del panel de control de ComproPago la orden se muestra como
"PENDIENTE". Sólo resta que el cliente realice el depósito a la brevedad posible.


---

## Configuración del plugin

1. Para iniciar la configuración dirígete a **System -> Configuration -> Sales -> Payment Methods**. Selecciona
   **ComproPago**. ***Nota:*** La opción de **Habilitar Logos** es para mostrar las imágenes de los establecimientos con
   los que procesamos pagos
2. Agregar la **llave privada** y **llave pública** que se encuentran en el apartado de configuración dentro del
   [panel de control de ComproPago][Compropago-Panel].

---

## Sincronización con la notificación Webhook
1. Ir al area de [Webhooks][Compropago-Webhooks] en ComproPago.
2. Introducir la dirección: **http://direcciondetusitio.com/index.php/compropago/webhook/**
3. Dar click en el botón "Probar" y verificamos que el servidor de la tienda esta respondiendo, debera aparecer el
   mensaje de "Probando el WebHook?, Ruta correcta."

Una vez completado estos pasos el proceso de instalación queda completado.

## Documentación
### Documentación ComproPago Plugin Magento

### Documentación de ComproPago
**[API de ComproPago](https://compropago.com/documentacion/api)**

ComproPago te ofrece un API tipo REST para integrar pagos en efectivo en tu comercio electrónico o tus aplicaciones.


**[General](https://compropago.com/documentacion)**

Información de Comisiones y Horarios, como Transferir tu dinero y la Seguridad que proporciona ComproPAgo


**[Herramientas](https://compropago.com/documentacion/boton-pago)**
* Botón de pago
* Modo de pruebas/activo
* WebHooks
* Librerías y Plugins
* Shopify

[Magento-Connect]: https://www.magentocommerce.com/magento-connect/compropago-oxxo-seven-eleven-extra-chedraui-elektra.html
[Compropago-Panel]: https://compropago.com/panel/configuracion
[Compropago-Webhooks]: https://compropago.com/panel/webhooks
