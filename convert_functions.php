<?php
function loadClass($classname)
{
  require 'classes/'.$classname.'.php';
}

function upload($FILES, $folderDest) {
    $message = ''; 
    // Traitement du formulaire. 
    // Récupérer les informations sur le fichier. 
    $informations = $FILES["fichier"]; 
    // En extraire : 
    //    - son nom 
    $nom = $informations['name']; 
    //    - son type MIME 
    $type_mime = $informations['type']; 
    //    - sa taille 
    $taille = $informations['size']; 
    //    - l'emplacement du fichier temporaire 
    $fichier_temporaire = $informations['tmp_name']; 
    //    - le code d'erreur 
    $code_erreur = $informations['error']; 
    // Contrôles et traitement 
    switch ($code_erreur) { 
        case UPLOAD_ERR_OK : 
            // Fichier bien reçu. 
            // Déterminer sa destination finale 
            $destination = $folderDest.$nom; 
            // Copier le fichier temporaire (tester le résultat). 
            if (copy($fichier_temporaire,$destination)) { 
                // Copie OK => mettre un message de confirmation. 
                $message  = "Transfert terminé - Fichier = $nom - "; 
                $message .= "Taille = $taille octets - "; 
                $message .= "Type MIME = $type_mime."; 
            } else { 
                // Problème de copie => mettre un message d'erreur. 
                $message = 'Problème de copie sur le serveur.'; 
            } 
            break; 
        case UPLOAD_ERR_NO_FILE : 
            // Pas de fichier saisi. 
            $message = 'Pas de fichier saisi.'; 
            break; 
        case UPLOAD_ERR_INI_SIZE : 
            // Taille fichier > upload_max_filesize. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (taille > upload_max_filesize).'; 
            break; 
        case UPLOAD_ERR_FORM_SIZE : 
            // Taille fichier > MAX_FILE_SIZE. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (taille > MAX_FILE_SIZE).'; 
            break; 
        case UPLOAD_ERR_PARTIAL : 
            // Fichier partiellement transféré. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (problème lors du tranfert).'; 
            break; 
        case UPLOAD_ERR_NO_TMP_DIR : 
            // Pas de répertoire temporaire. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (pas de répertoire temporaire).'; 
            break; 
        case UPLOAD_ERR_CANT_WRITE : 
            // Erreur lors de l'écriture du fichier sur disque. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (erreur lors de l\'écriture du fichier sur disque).'; 
            break; 
        case UPLOAD_ERR_EXTENSION : 
            // Transfert stoppé par l'extension. 
            $message  = "Fichier '$nom' non transféré "; 
            $message .= ' (transfert stoppé par l\'extension).'; 
            break; 
        default : 
            // Erreur non prévue ! 
            $message  = "Fichier non transféré "; 
            $message .= " (erreur inconnue : $code_erreur )."; 
    } 
    return array('msg'=>$message, 'coderr'=>$code_erreur);
}

/*******************Extract CSS Code from header tags and save it int DB *******************/
function saveCSSintoDB($myConvert, $dbManager) {
//raz tables
    $dbManager->getDb()->exec("TRUNCATE table classecss");
    $dbManager->getDb()->exec("TRUNCATE table proprietecss");
    $dbManager->getDb()->exec("TRUNCATE TABLE liaison");    
    
    foreach ($myConvert->getStyles() as $style) {  
        foreach($style[0] as $classe) {
            if ($classe!='') {
                $idClasse = $dbManager->setClassCss($classe); //save CSS class into DB and return last insert id
            }
            else {
                continue;
            }

            foreach($style[1] as $propriete) {
                    if ($propriete!="") {
                        $paire = explode(':', $propriete);
                        $idPropriete = $dbManager->setProprieteCss($paire, $idClasse); //save CSS properties corresponding to class into DB and return last insert id
                        $dbManager->setRelation($idClasse, $idPropriete); //make a many to many relation table between classe and properties
                    }
            }
        }
    }
}

/**********************Extraire classes et générer CSS à partir de la BDD**************/
function extractClasses($dbManager, $myConvert, $minified) {

    if ($minified) $RC=""; else $RC="\n";

    $dbManager->razTblSpan();

    $myConvert->iniTab();

    $styles = $myConvert->getStyles();

    $handle=fopen($myConvert->getFilenameCss(),'w+');

    for ($i=0;$i<count($styles[1]);$i++) {
        $styles[1][$i] = explode(' ', $styles[1][$i]); //séparer class et styles dans p
        
        $resultCl = $dbManager->getClassCss('%'.$styles[1][$i][0]); //read CSS class

        //Boucle des classes
        foreach ($resultCl as $classe) {
            //Vérifier ds BDD si classes Utilisées pour ne pas les réécrire 50 fois
            $resultUsed = $dbManager->getUsed($classe['nom']);

            if ($resultUsed['used']=='1') break;
            //MAJ Classes ds BDD Utilisées pour ne pas les réécrire 50 fois
            $dbManager->setUsed($classe['nom']);

            $result = $dbManager->getProprieteCss($classe['id']);

            $ligneCL='';$first=true;
            foreach ($resultCl as $laclasse) {  
                if ($laclasse['nom']=='div.WordSection1') {
                    fwrite($handle,'div.WordSection1 {page:WordSection1;}'.$RC);
                    continue;
                }                 
                else {
                    $virgule=($first)?'':', ';
                    $ligneCL.= $virgule.$laclasse['nom'];
                    $first=false;
                }          
            }
            //ecrire dans le fichier CSS            
            fwrite($handle, $ligneCL.' {'.$RC);
                
            $ligneCL='';
            foreach ($result as $propriet) {
                $ligneCL.= $propriet['nom'].':'.$propriet['valeur'].';';
            }
            fwrite($handle, $ligneCL."\n".'}'.$RC);
            break;
        }
    }
    fclose ($handle);
}

/********************* replace style=property with style=class in Body Code ******************/
function replaceStyle($dbManager, $myConvert, $minified) {

    if ($minified) $RC=""; else $RC="\n";

    $myConvert->setNewStyle();
    $styles = $myConvert->getStyles();

    $handle=fopen($myConvert->getFilenameCss(),'a+');

    $c=1;
    for ($i=0;$i<count($styles[1]);$i++) {
        if (!isset($styles[1][$i])) continue;

        $result = $dbManager->getSpan($styles[1][$i]);

        if (count($result)==0) {
            $className='CL00'.$c++;

            $dbManager->setSpan($styles[1][$i], $className);

            //replace style=property with style=class and clean bad caracters
            if (!preg_match('/\<\/span/', $styles[1][$i])) {
                $styles[1][$i] = preg_replace('/\./','\.', $styles[1][$i]);
                $styles[1][$i] = preg_replace('/\=/',':', $styles[1][$i]);
                
                $tab = $myConvert->getTab();
                $tab[1]=preg_replace('/'.$styles[1][$i].'/', 'class="'.$className.'"', $tab[1]);
                $myConvert->setTab($tab);                
            }           
            $styles[1][$i] = preg_replace('/\\\./','.', $styles[1][$i]);
            $styles[1][$i] = preg_replace('/\r\n|style=/','', $styles[1][$i]);

            preg_match('/\'([\S\s]+?)\'/', $styles[1][$i], $arr);
            $styles[1][$i] = $arr[1];

            //genererate fichier css
            fwrite($handle,'.'.$className.' {'.$RC);
            if ($minified) fwrite($handle, $styles[1][$i].";".$RC); else fwrite($handle, '    '.$styles[1][$i].";".$RC);
            fwrite($handle,'}'.$RC);
        }
    }
    fclose($handle);
}

/*********Enregistrer fichier HTML final ************/

function saveHTML($myConvert, $withHeaders) {

    //write headers
    $header = '<!DOCTYPE html>
<html lang=fr>
<head>
  <meta http-equiv=Content-Type content="text/html; charset=utf-8">
  <META NAME="description" CONTENT="'.$myConvert->getFilenameHtml().'" />
  <META NAME="robots" CONTENT="ALL" />
  <META NAME="author" CONTENT="pierre-louis Peyssard" />
  <META NAME="viewport" CONTENT="width=device-width" />
  <link rel="stylesheet" href="'.$myConvert->getFilenameCss().'" type="text/css" media="all" >
</head>'."\n";

    //write body
    $tab = $myConvert->getTab([1]);
    
    $handle=fopen($myConvert->getFilenameHtmlNew(),'w+');
    
    if ($withHeaders) fwrite($handle,$header);
    fwrite($handle, $tab[1]);
    if ($withHeaders) fwrite($handle,"</body>\n</html>");

    fclose($handle);

    echo $tab[1];
}