<?php
class MyConvert
{
    private $filenameHtml;
    private $filenameCss;
    private $filenameHtmlNew;
    private $fullPathCss;
    private $tab1;          //String: contains original CSS code
    private $styles;        //Array: List of CSS styles from header style tags
    private $tab;           //Array: List of CSS styles from body styles tags

    public function __construct($folderDest, $filename)
    {
        $this->setFilename($folderDest, $filename);

        $this->iniTab1();      //extract Css Code from original file
        $this->iniStyles();    //Extract CSS code in an Array;
    }

    /**** Extract CSS Code in an Array**************/
    public function iniStyles() {
        $i=0;
        foreach ($this->tab1 as $this->style) {
            //Extraire les paires classe/propriétés dans un tableau
            $this->styles[$i] = preg_split('/\{/', $this->style);
    
            //extraire les classes multiples
            $this->styles[$i][0] = preg_split('/\,/', $this->styles[$i][0]);

            //supprimer les espaces à gauche
            for ($j=0;$j<sizeof($this->styles[$i][0]);$j++) {
                $this->styles[$i][0][$j]=trim($this->styles[$i][0][$j]); //supprimer les espaces à gauche
            }

            //Extraire les propriétés dans un sous tableau
            if (isset($this->styles[$i][1])) {
                $this->styles[$i][1] = preg_split('/\;/', $this->styles[$i][1]);

                for ($j=0;$j<sizeof($this->styles[$i][1]);$j++) {
                    $this->styles[$i][1][$j]=trim($this->styles[$i][1][$j]); //supprimer les espaces à gauche
                }
            } 
            $i++;
        }        
    }

    /******* Extract CSS part from code *********/
    public function iniTab1() {
        //lire fichier et supprimer les retours chariots
        $document = preg_replace("(\r\n|\n|\r)",' ',file_get_contents("C:/Users/1900780/Documents/Afpa/Ressources/PHP/ConvertDoc/".$this->filenameHtml));

        //ne garder que les styles
        //$this->tab1 = preg_split ('/\<style\>|\<\/style\>/', $document); //if word document saved with html format filtered web page
        preg_match_all('/\<style\>([\S\s]+?)\<\/style/', $document, $this->tab1);

        $this->tab1[1] = $this->tab1[1][1];                                 //if word document saved with html format web page
        
        if (!isset($this->tab1[1])) die("no css style in your html file !");

        //supprimer commentaire --> et <!--
        $this->tab1[1] = preg_replace('/\-\-\>|\<\!\-\-/','', $this->tab1[1]);
        
        //supprimer commentaires /* nnn */ 
        $this->tab1[1] = preg_replace('/\/\*([\S\s]+?)\*\//','', $this->tab1[1]); 

        //Créer un tableau contenant les blocs de style (classe+propriétés)
        $this->tab1 = preg_split('/\}/', $this->tab1[1]);
    }

    public function iniTab() {
        $document = preg_replace("(span\r\n)",'span ',file_get_contents($this->filenameHtml));
        
        $this->tab = preg_split ('/\<\/head\>|\<\/html\>/', $document);

        $count = preg_match_all('/ class=([\S\s]+?)\>/', $this->tab[1], $this->styles);
    }

    public function setTab($tab) {
        $this->tab = $tab;
    }

    public function getTab() {
        return $this->tab;
    }

    public function setNewStyle() {
        $count = preg_match_all('/ style=([\S\s]+?)\>/', $this->tab[1], $this->styles); 
    }

    private function setFilename($folderDest, $filename) {
        $this->filenameHtml = $folderDest.$filename;

        $t = explode('.',$filename);

        $this->filenameCss      = $t[0].'.css';
        $this->fullPathCss      = $folderDest.$t[0].'.css';
        $this->filenameHtmlNew  = $folderDest.$t[0].'_new.html';
    }

    public function getStyles() {
        return $this->styles;
    }

    public function getFilenameHtml() {
        return $this->filenameHtml;
    }

    public function getFilenameHtmlNew() {
        return $this->filenameHtmlNew;
    }

    public function getFilenameCss() {
        return $this->filenameCss;
    }

    public function getFullPathCss() {
        return $this->fullPathCss;
    }

    
}