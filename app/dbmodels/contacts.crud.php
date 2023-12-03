<?php
require_once("Constants.php");
class ContactsCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function create($name, $email, $subject, $message, $date_created)
 {
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO contacts(name, email, subject, message, date_created) VALUES(:name, :email, :subject, :message, :date_created)");
   $stmt->bindparam(":name", $name);
   $stmt->bindparam(":email", $email);
   $stmt->bindparam(":subject", $subject);
   $stmt->bindparam(":message", $message);
   $stmt->bindparam(":date_created", $date_created);
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
   }else{
	   $response["error"] = true;  
       $response["code"] = INSERT_FAILURE; 
   }
   return $response;
  }
  catch(PDOException $e)
  {
   echo $e->getMessage();  
   return $response;
  }
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM contacts WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getAllMessages()
 {
  $stmt = $this->db->prepare("SELECT * FROM contacts ORDER BY id DESC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getNumMessages($startDate = "", $endDate = "")
 {
    $sql = "SELECT count(*) FROM contacts";
    if (!empty($startDate) && !empty($endDate)) {
        $sql .= " WHERE date_created >= '" . $startDate . "' AND date_created < '" . $endDate . "'";
    }
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $number_of_rows = $stmt->fetchColumn();
    return $number_of_rows;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM contacts WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
}