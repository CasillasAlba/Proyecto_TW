function getCookie(name){

    //console.log("hola getcookie");

    var re=new RegExp(name+"=([^;]+)");
    var value=re.exec(document.cookie);

    return(value!=null)?unescape(value[1]):null;
}