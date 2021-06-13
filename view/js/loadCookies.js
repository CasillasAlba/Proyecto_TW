var myForm = document.getElementById("busqueda-usuarios");

//console.log("hola caracola");

$( myForm ).ready(function(){
    if(search = getCookie("search")) myForm.search.value = search;
    if(from = getCookie("from")) myForm.from.value = from;
    if(to = getCookie("to")) myForm.to.value = to;
});