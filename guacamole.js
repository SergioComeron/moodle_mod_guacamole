function cargarOnChange(){
  $('#id_daystodelete').load('../mod/guacamole/getter.php?idimagen=' + $('#id_imageid').val() );
}
