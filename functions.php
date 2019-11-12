<?php
$PARAM_hote='localhost'; // le chemin vers le serveur
$PARAM_port='3306';
$PARAM_nom_bd='convertdoc'; // le nom de votre base de données
$PARAM_utilisateur='root'; // nom d'utilisateur pour se connecter
$PARAM_mot_passe='formation2019'; // mot de passe de l'utilisateur pour se connecter

try
{
    $connexion = new PDO('mysql:host='.$PARAM_hote.';dbname='.$PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe,array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
}
 
catch(Exception $e)
{
    echo 'Erreur : '.$e->getMessage().'<br />';
    echo 'N° : '.$e->getCode();
}
/*
The following should be escaped if you are trying to match that character
\ ^ . $ | ( ) [ ] * + ? { } ,
*/
/*******************Extraction du CSS *******************/

function decrypterCSS($filename, $connexion) {    

    //lire fichier et supprimer les retours chariots
    $document = preg_replace("(\r\n|\n|\r)",' ',file_get_contents($filename));
    
    //ne garder que les styles
    $tab1 = preg_split ('/\<style\>|\<\/style\>/', $document);
    
    //supprimer commentaire --> et <!--
    $blocStyle = preg_replace('/\-\-\>|\<\!\-\-/','', $tab1[1]);
    
    //supprimer commentaires /* nnn */ 
    $blocStyle = preg_replace('/\/\*([\S\s]+?)\*\//','', $blocStyle); 

    //Créer un tableau contenant les blocs de style (classe+propriétés)
    $tab1 = preg_split('/\}/', $blocStyle);

   
    $i=0;
    foreach ($tab1 as $style) {
        //Extraire les paires classe/propriétés dans un tableau
        $styles[$i] = preg_split('/\{/', $style);
 
        //extraire les classes multiples
        $styles[$i][0] = preg_split('/\,/', $styles[$i][0]);

        //supprimer les espaces à gauche
        for ($j=0;$j<sizeof($styles[$i][0]);$j++) {
            $styles[$i][0][$j]=trim($styles[$i][0][$j]); //supprimer les espaces à gauche
        }

        //Extraire les propriétés dans un sous tableau
        if (isset($styles[$i][1])) {
            $styles[$i][1] = preg_split('/\;/', $styles[$i][1]);

            for ($j=0;$j<sizeof($styles[$i][1]);$j++) {
                $styles[$i][1][$j]=trim($styles[$i][1][$j]); //supprimer les espaces à gauche
            }
        } 
        $i++;
    }    
    
    //raz tables
    $connexion->exec("TRUNCATE table classecss");
    $connexion->exec("TRUNCATE table proprietecss");
    $connexion->exec("TRUNCATE TABLE liaison");
    
    foreach ($styles as $style) {
        //Sauvegarde des classes        
        foreach($style[0] as $classe) {
            if ($classe!='') {
                $requete = $connexion->prepare("INSERT INTO classecss (nom) VALUES (:classe)");
                $requete->execute(array(':classe' => $classe));
                $idClasse = $connexion->lastInsertId(); 
            }
            else {
                continue;
            }
        
            //sauvegarde des proprietes
            foreach($style[1] as $propriete) {
                    if ($propriete!="") {
                        $paire = explode(':', $propriete);
                        $requete = $connexion->prepare("INSERT INTO proprietecss (nom,valeur,classecss) VALUES (:nom,:valeur,:classe)");
                        $requete->execute(array(':nom' => $paire[0], 'valeur'=>$paire[1], ':classe'=>$idClasse));

                        $idPropriete = $connexion->lastInsertId();                     

                        //table de relation many to many avec les last insert id
                        $requete = $connexion->prepare("INSERT INTO liaison (classe, propriete) VALUES(:classe, :propriete)"); 
                        $requete->execute(array(':classe'=>$idClasse,':propriete'=>$idPropriete));   
                    }
            }
        }
    }
    return $tab1;
}
/**********************Extraire classes et générer CSS à partir de la BDD**************/
function extraireClasses($filename, $connexion) {

    $connexion->exec("TRUNCATE TABLE span");
    $requete = $connexion->prepare("UPDATE classecss SET used='0' WHERE 1");
    $requete->execute();

    $regexp = '/\<span style([\S\s]+?)\>/';

    $document = preg_replace("(span\r\n)",'span ',file_get_contents($filename));
    //ne garder que les styles
    $tab = preg_split ('/\<\/head\>|\<\/html\>/', $document);

    $count = preg_match_all('/ class=([\S\s]+?)\>/', $tab[1], $styles);    
//print_r($styles);
    $cssfile = explode('.',$filename);
    $handle=fopen($cssfile[0].'.css','w+');

    for ($i=0;$i<count($styles[1]);$i++) {
        $styles[1][$i] = explode(' ', $styles[1][$i]); //séparer class et styles dans p
        
        $val = '%'.$styles[1][$i][0];        
        //lire les classes css
        $requete = $connexion->prepare("SELECT id, nom FROM classecss WHERE nom LIKE :nom");
        $requete->execute(array('nom'=>$val));
        $resultCl = $requete->fetchAll(PDO::FETCH_ASSOC);     

        //Boucle des classes
        foreach ($resultCl as $classe) {
            //Vérifier ds BDD si classes Utilisées pour ne pas les réécrire 50 fois
            $requete = $connexion->prepare("SELECT used FROM classecss WHERE nom=:nom");
            $requete->execute(array('nom'=>$classe['nom']));
            $resultUsed = $requete->fetch(PDO::FETCH_ASSOC);

            if ($resultUsed['used']=='1') break;
            //MAJ Classes ds BDD Utilisées pour ne pas les réécrire 50 fois
            $requete = $connexion->prepare("UPDATE classecss SET used='1' WHERE nom LIKE :nom");
            $requete->execute(array('nom'=>$classe['nom']));

            //Lire les propriétés
            $requete = $connexion->prepare("SELECT proprietecss.nom, proprietecss.valeur 
                                            FROM proprietecss
                                            JOIN liaison ON liaison.propriete=proprietecss.id
                                            WHERE liaison.classe= :classe");
            $requete->execute(array('classe'=>$classe['id']));
            $result = $requete->fetchAll(PDO::FETCH_ASSOC);

            $ligneCL='';$first=true;
            foreach ($resultCl as $laclasse) {  
                if ($laclasse['nom']=='div.WordSection1') {
                    fwrite($handle,'div.WordSection1 {page:WordSection1;}'."\n");
                    continue;
                }                 
                else {
                    $virgule=($first)?'':', ';
                    $ligneCL.= $virgule.$laclasse['nom'];
                    $first=false;
                }          
            }
            //ecrire dans le fichier CSS            
            fwrite($handle, $ligneCL.' {'."\n");
                
            $ligneCL='';
            foreach ($result as $propriet) {
                $ligneCL.= $propriet['nom'].':'.$propriet['valeur'].';';
            }
            fwrite($handle, $ligneCL."\n".'}'."\n");
            break;
        }
    }
    fclose ($handle);

    return $tab;
}

/********************* remplacer style=propriete par style=classe ******************/
function remplaceStyle($tab, $filename, $connexion) {   

    $c=1;
    $cssfile = explode('.',$filename);
    $handle=fopen($cssfile[0].'.css','a+');

    $count = preg_match_all('/ style=([\S\s]+?)\>/', $tab[1], $styles); //générer tableau styles
    //print_r($styles);
    
    for ($i=0;$i<count($styles[1]);$i++) {
        if (!isset($styles[1][$i])) continue;

        $requete = $connexion->prepare("SELECT id FROM span WHERE valeur=:valeur");
        $requete->execute(array('valeur'=>$styles[1][$i]));
        $result = $requete->fetchAll(PDO::FETCH_ASSOC);

        if (count($result)==0) {
            $className='CL00'.$c++;
            
            $requete = $connexion->prepare("INSERT INTO span (valeur, classe) VALUES (:valeur,:classe)");
            $requete->execute(array('valeur'=>$styles[1][$i], ':classe'=>$className));
            
            //Remplacer les styles par des classes css
            if (!preg_match('/\<\/span/', $styles[1][$i])) {
                //echo $className." ".$styles[1][$i]."\n";
                $styles[1][$i] = preg_replace('/\./','\.', $styles[1][$i]);
                $styles[1][$i] = preg_replace('/\=/',':', $styles[1][$i]);
                //echo $i." ".$className." ".$styles[1][$i]."\n";
                $tab[1]=preg_replace('/'.$styles[1][$i].'/', 'class="'.$className.'"', $tab[1]);
                //echo "\n";  
            }
           
            //extraire styles css
            $styles[1][$i] = preg_replace('/\\\./','.', $styles[1][$i]);
            $styles[1][$i] = preg_replace('/\r\n|\'|style=/','', $styles[1][$i]);
            
            //générer fichier css
            fwrite($handle,'.'.$className.' {'."\n");
            fwrite($handle, '    '.$styles[1][$i].";\n");
            fwrite($handle,'}'."\n");
        }
    }
    fclose($handle);

    return $tab;
}
/*********Enregistrer fichier HTML final ************/

function saveHTML($tab, $filename) {

    $cssfile = explode('.',$filename);
    $header = '<!DOCTYPE html>
<html lang=fr>
<head>
  <meta http-equiv=Content-Type content="text/html; charset=utf-8">
  <META NAME="description" CONTENT="'.$cssfile[0].'" />
  <META NAME="robots" CONTENT="ALL" />
  <META NAME="author" CONTENT="pierre-louis Peyssard" />
  <META NAME="viewport" CONTENT="width=device-width" />
  <link rel="stylesheet" href="'.$cssfile[0].'.css'.'" type="text/css" media="all" >
</head>'."\n";

    $handle=fopen($cssfile[0].'_new.html','w+');
    fwrite($handle,$header);
    fwrite($handle,$tab[1]);
    fclose($handle);
}
?>