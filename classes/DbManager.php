  <?php
class DbManager
{
  private $_db; // Instance de PDO
  private $msg;
  
  public function __construct($PARAM_hote, $PARAM_port, $PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe)
  {
    $this->msg='';
    $this->setDB($PARAM_hote, $PARAM_port, $PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe);
  }

  public function razTblSpan() {
    $this->_db->exec("TRUNCATE TABLE span");
    $requete = $this->_db->prepare("UPDATE classecss SET used='0' WHERE 1");
    $requete->execute();
  }

  public function getUsed($nom) {
    $requete = $this->_db->prepare("SELECT used FROM classecss WHERE nom=:nom");
    $requete->execute(array('nom'=>$nom));
    return $requete->fetch(PDO::FETCH_ASSOC);
  }

  public function setUsed($nom) {
    $requete = $this->_db->prepare("UPDATE classecss SET used='1' WHERE nom LIKE :nom");
    $requete->execute(array('nom'=>$nom));
  }

  public function setClassCss($classe) {
    $requete = $this->_db->prepare("INSERT INTO classecss (nom) VALUES (:classe)");
    $requete->execute(array(':classe' => $classe));
    return $this->_db->lastInsertId();
  }

  public function getClassCss($val) {
    $requete = $this->_db->prepare("SELECT id, nom FROM classecss WHERE nom LIKE :nom");
    $requete->execute(array('nom'=>$val));
    return $requete->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getClassCss2($val) {
    $requete = $this->_db->prepare("SELECT id, nom FROM classecss WHERE nom = :nom");
    $requete->execute(array('nom'=>$val));
    return $requete->fetchAll(PDO::FETCH_ASSOC);
  }

  public function setProprieteCss($paire, $idClasse) {
    $requete = $this->_db->prepare("INSERT INTO proprietecss (nom,valeur,classecss) VALUES (:nom,:valeur,:classe)");
    $requete->execute(array(':nom' => $paire[0], 'valeur'=>$paire[1], ':classe'=>$idClasse));
    return $this->_db->lastInsertId(); 
  }

  public function getProprieteCss($classe) {
    $requete = $this->_db->prepare("SELECT proprietecss.nom, proprietecss.valeur 
                                    FROM proprietecss
                                    JOIN liaison ON liaison.propriete=proprietecss.id
                                    WHERE liaison.classe= :classe");
    $requete->execute(array('classe'=>$classe));
    return $requete->fetchAll(PDO::FETCH_ASSOC);
  }

  public function setRelation($idClasse, $idPropriete) {
    //table de relation many to many avec les last insert id
    $requete = $this->_db->prepare("INSERT INTO liaison (classe, propriete) VALUES(:classe, :propriete)"); 
    $requete->execute(array(':classe'=>$idClasse,':propriete'=>$idPropriete));
  }

  public function getSpan($valeur) {
    $requete = $this->_db->prepare("SELECT id FROM span WHERE valeur=:valeur");
    $requete->execute(array('valeur'=>$valeur));
    return $requete->fetchAll(PDO::FETCH_ASSOC);
  }

  public function setSpan($valeur, $classeName) {
    $requete = $this->_db->prepare("INSERT INTO span (valeur, classe) VALUES (:valeur,:classe)");
    $requete->execute(array('valeur'=>$valeur, ':classe'=>$classeName));
  }

  public function getDB() {
        return $this->_db;
  }

  private function setDB($PARAM_hote, $PARAM_port, $PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe) {
    try
        {
            $this->_db = new PDO('mysql:host='.$PARAM_hote.';dbname='.$PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe,
                        array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); // On émet une alerte à chaque fois qu'une requête a échoué.
        }
        
        catch(Exception $e)
        {
            $this->$msg = 'Erreur : '.$e->getMessage().'<br />';
            $this->$msg.= 'N° : '.$e->getCode();
        }        
    }    
}