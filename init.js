M.chargeOnLoad = {};

M.chargeOnLoad.init = function() {
  $('#id_daystodelete').load('../mod/guacamole/getter.php?idimagen=' + $('#id_imageid').val() );

}
