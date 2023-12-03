<?php
require_once("Constants.php");
class PlanCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function createStripeWebhook($title, $message)
 {
  $response = array();
  $response["error"] = true;
  $temp = "";
  try
  {
   $stmt = $this->db->prepare("INSERT INTO stripe_webhooks(title, message) VALUES(:title, :message)");
  
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":message", $message);

   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
	   $prodID = $response["id"];
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

 public function create($title, $membersip_desc, $price, $currency_id, $tagline, $status,$is_available, $note, $is_highlighted, $sort_order, $date_created)
 {
  $response = array();
  $response["error"] = true;
  $temp = "";
  try
  {
   $stmt = $this->db->prepare("INSERT INTO membersip_plans(title, membersip_desc, currency_id, tagline, is_available, note, is_highlighted, sort_order, date_created) VALUES(:title, :membersip_desc, :currency_id, :tagline, :is_available, :note, :is_highlighted, :sort_order, :date_created)");
  
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":membersip_desc", $membersip_desc);
   $stmt->bindparam(":price", $price);
   $stmt->bindparam(":currency_id", $currency_id);
   $stmt->bindparam(":tagline", $tagline);
   $stmt->bindparam(":is_available", $is_available);
   $stmt->bindparam(":note", $note);
   $stmt->bindparam(":is_highlighted",$is_highlighted);
   $stmt->bindparam(":sort_order",$sort_order);
   $stmt->bindparam(":date_created", $date_created);
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
	   $prodID = $response["id"];
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
 
 
 public function update($id, $title, $membersip_desc, $price, $duration, $currency_id, $tagline, $is_available, $note, $is_highlighted, $sort_order)
 {
$response = array();
  $response["error"] = true;     
  try
  {
   $stmt=$this->db->prepare("UPDATE membersip_plans SET title=:title, 
                membersip_desc=:membersip_desc,
                price =:price,
                duration =:duration,
				currency_id =:currency_id,
				tagline=:tagline,
				is_available=:is_available,
				note=:note,
				is_highlighted=:is_highlighted,
				sort_order=:sort_order
                WHERE id=:id");
   
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":membersip_desc",$membersip_desc);
   $stmt->bindparam(":price",$price);
   $stmt->bindparam(":duration",$duration);
   $stmt->bindparam(":currency_id",$currency_id);
   $stmt->bindparam(":tagline",$tagline);
   $stmt->bindparam(":is_available",$is_available);
   $stmt->bindparam(":note",$note);
   $stmt->bindparam(":is_highlighted",$is_highlighted);
   $stmt->bindparam(":sort_order",$sort_order);
   $stmt->bindparam(":id",$id);
   if($stmt->execute()){
        $response["error"] = false;  
       $response["code"] = INSERT_SUCCESS; 
   }else{
       $response["error"] = true;  
       $response["code"] = INSERT_FAILURE; 
   }
   return $response;
  }
  catch(PDOException $e)
  {
   //echo $e->getMessage(); 
    $response["msg"] = $e->getMessage();  
    $response["error"] = true;  
    $response["code"] = INSERT_FAILURE; 
    return $response;
  }
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM membersip_plans WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT * FROM membersip_plans WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getPriceByID($id)
 {
  $stmt = $this->db->prepare("SELECT price FROM membersip_plans WHERE id='$id'");
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 
   public function getTenureByID($id)
 {
  $stmt = $this->db->prepare("SELECT duration FROM membersip_plans WHERE id='$id'");
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 
 public function getAllPlans()
 {
  $stmt = $this->db->prepare("SELECT * FROM membersip_plans ORDER BY sort_order ASC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllActivePlans()
 {
  $is_available = 1;	 
  $stmt = $this->db->prepare("SELECT * FROM membersip_plans ORDER BY sort_order ASC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
   public function getNumAllPlans($status)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM membersip_plans WHERE status =:status ORDER BY id DESC");
  $stmt->execute(array(":status"=>$status));
  $editRow=$stmt->fetchColumn();
  return $editRow;
 }
 
 public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM membersip_plans WHERE id='$id'");
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 
  public function getQCodeByID($id)
 {
  $stmt = $this->db->prepare("SELECT qcode FROM membersip_plans WHERE id='$id'");
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 
 public function isMembershipQcodeExists($qcode)
 { 
  $sql = "SELECT count(*) FROM membersip_plans WHERE qcode=:qcode";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":qcode"=>$qcode)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows > 0;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM membersip_plans WHERE id='$id'");
 if($stmt->execute()){
	 return true;
 }else{
	 return false;
 }
 }
 
}