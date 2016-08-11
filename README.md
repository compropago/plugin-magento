Plugin para Magento 1.7.x, 1.8.x, 1.9.x - ComproPago
====================================================
## Descripción
Este modulo provee el servicio de ComproPago para poder generar intenciones de pago dentro de la plataforma Magento.

Con ComproPago puede recibir pagos en OXXO, 7Eleven y muchas tiendas más en todo México.

[Registrarse en ComproPago ] (https://compropago.com)


## Ayuda y Soporte de ComproPago

- [Centro de ayuda y soporte](https://compropago.com/ayuda-y-soporte)
- [Solicitar Integración](https://compropago.com/integracion)
- [Guía para Empezar a usar ComproPago](https://compropago.com/ayuda-y-soporte/como-comenzar-a-usar-compropago)
- [Información de Contacto](https://compropago.com/contacto)

## Requerimientos
* [Magento 1.7.x, 1.8.x, 1.9.x](https://magento.com/)
* [PHP >= 5.5](http://www.php.net/)
* [PHP JSON extension](http://php.net/manual/en/book.json.php)
* [PHP cURL extension](http://php.net/manual/en/book.curl.php)

## Instalación:

1. Copiar el enlace proprocionado por **Magento Connect** desde [aquí][Magento-Connect]
2. En el panel de administración de Magento ingresamos y nos dirigimos a **System -> Magento Connect -> Magento Connect Manager**
3. Pegamos el link obtenido en la sección **Install New Extension** y oprimimos el botón **Install**.
4. Verificamos que la instalación fue correcta y procedemos a oprimir el botón de **Refresh** para actualizar la pantalla.


## ¿Cómo trabaja el modulo?
Una vez que el cliente sabe que comprar y continua con el proceso de compra entrará a la opción de elegir metodo de pago
justo aqui aparece la opción de pagar con ComproPago, seleccionamos el establecimiento de nuestra conveniencia y le
damos continuar

Al completar el proceso de compra dentro de la tienda el sistema nos proporcionara un recibo de pago como el siguiente,
solo falta realizar el pago en el establecimiento que seleccionamos.

Una vez que el cliente genero su intención de pago, dentro del panel de control de ComproPago la orden se muestra como
"PENDIENTE" esto significa que el usuario esta por ir a hacer el deposito.

---

## Configurar el plugin

1. Para iniciar la configuración ir a **System -> Configuration -> Sales -> Payment Methods**. Seleccionar
   **ComproPago**. ***Nota:*** La opción de **Habilitar Logos** es para mostrar las imagenes de los establecimientos con
   los que procesamos pagos
2. Agregar la **llave privada** y **llave pública**, esta se puede encontrar en el apartado de configuración dentro del
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
