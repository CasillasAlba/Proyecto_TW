// Función que establece una cookie
var today = new Date();
var expiry = new Date(today.getTime() + 30 * 24 * 3600 * 1000); // periodo de 30 días
//console.log("hola set cookies");

function setCookie(name, value)
{
    //console.log("hola set cookies funcion");
    document.cookie=name + "=" + escape(value) + "; path=/; expires=" + expiry.toGMTString();
}