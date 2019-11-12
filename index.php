<?php
$PARAM_hote='localhost'; // le chemin vers le serveur
$PARAM_port='3306';
$PARAM_nom_bd='convertdoc'; // le nom de votre base de données
$PARAM_utilisateur='root'; // nom d'utilisateur pour se connecter
$PARAM_mot_passe='formation2019'; // mot de passe de l'utilisateur pour se connecter

$folderDest="files_origin/"; //destination folder for upload 

if (isset($_POST['submit'])) {
  require_once('convert_functions.php');
  print_r($_POST);

  if (isset($_POST['minified']))    $minified=true;    else $minified=false;
  if (isset($_POST['withHeaders'])) $withHeaders=true; else $withHeaders=false;

  $arr    = upload($_FILES, $folderDest);
  $msg    = $arr['msg'];
  $coderr = $arr['coderr'];

  if ($coderr=='UPLOAD_ERR_OK') {

    ini_set('max_execution_time', 240);

    spl_autoload_register('loadClass');

    $myConvert = new MyConvert($folderDest.$_FILES["fichier"]["name"]);

    $dbManager = new dbManager($PARAM_hote, $PARAM_port, $PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe);

    saveCSSintoDB($myConvert, $dbManager);

    extractClasses($dbManager, $myConvert, $minified);

    replaceStyle($dbManager, $myConvert, $minified);

    saveHTML($myConvert, $withHeaders);
  }
}

?>

<doctype HTML>
<html>
  <head>
    <meta http-equiv=Content-Type content="text/html; charset=utf-8">
    <META NAME="X-UA-Compatible", "IE=edge", 'http-equiv'/>
    <META NAME="viewport", "width=device-width, initial-scale=1"/>
    <title>Convertisseur Word HTML</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
  </head>

  <body>
    <div class="container">

      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h3> Upload your file </h3>
        <p class="message"><?php if (isset($msg)) echo $msg;?></p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span> <!--croix-->
        </button>
      </div>

      <form class="form" action="#" method="POST" enctype="multipart/form-data">

          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" name="minified">
            <label class="form-check-label" for="defaultCheck1">
              Css code Minified
            </label>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" name="withHeaders" checked="checked">
            <label class="form-check-label" for="defaultCheck1">
              Generate HTML code with headers
            </label>
          </div>
     
          <div class="file-field">
              <div class="btn btn-primary btn-sm float-left">
                <span>Choose file</span>
                <input type="file" name="fichier" value="" >
              </div>
          </div>

        <div class="row">
          <div class="form-group">
            <div class="col-md-offset-2 col-md-10 float-left">
              <input type="submit" class="btn btn-primary" name="submit" value="Send">
            </div>
          </div>
        </div>

      </form>
    </div>
</body>
</html>