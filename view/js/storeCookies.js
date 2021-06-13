function storeCookies(form){

    //console.log("hola store");
  
    // En el guión de prácticas se indica que el uso de las cookies
    // se realizará en el formulario de búsqueda de pacientes
    // En este formulario, podemos guardar las cookies del searchbox 
    // o del intervalo de las fechas que puede introducir el usuario.
    // Solo guardamos estos valores ya que los otros son checkboz

    setCookie("search", form.search.value);
    setCookie("from", form.from.value);
    setCookie("to", form.to.value);

    return true;
  
}