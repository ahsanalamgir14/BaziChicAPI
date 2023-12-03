<?php
require_once("Constants.php");
class FreeTrialsCRUD
{
 private $db;

 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }

 
  public function getByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT * FROM free_trials WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
    
 
  public function updateExpiryDate($qcode, $date_expiring, $date_updated)
 {	 
  $response = array();
  try
  {
   $stmt=$this->db->prepare("UPDATE free_trials SET date_expiring=:date_expiring, date_updated=:date_updated
             WHERE qcode=:qcode");
   $stmt->bindparam(":date_expiring", $date_expiring);
    $stmt->bindparam(":date_updated", $date_updated);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->execute();
	   
   $response["error"] = false; 
   $response["message"] = "Membership request updated successfully."; 
   return $response; 
  }
  catch(PDOException $e)
  {
   $response["error"] = true; 
   $response["message"] = "An error occurred while processing your request. Try again.";
   return $response;
  }
 }
 
 public function create($user_id, $plan_id, $date_created, $date_expiring)
 {
  $response = array();
  $response["error"] = true;
  $temp = "";
  try
  {
   $stmt = $this->db->prepare("INSERT INTO free_trials(user_id, plan_id, date_created, date_expiring) VALUES(:user_id, :plan_id, :date_created, :date_expiring)");
   $stmt->bindparam(":user_id", $user_id);
   $stmt->bindparam(":plan_id", $plan_id);
   $stmt->bindparam(":date_created", $date_created);
   $stmt->bindparam(":date_expiring", $date_expiring);
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
       $response["error"] = true;
       $response["code"] = INSERT_FAILURE;
    $response["msg"] = $e->getMessage();
   return $response;
  }
 }



 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM free_trials WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function isIDExists($id)
 {
  $stmt = $this->db->prepare("SELECT id FROM free_trials WHERE id=:id");
  $result = $stmt->execute(array(":id"=>$id));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
  
 /************/
  public function getMyActivePlan($user_id)
 {
  $status = "Active";     
  $sql = "SELECT * FROM free_trials WHERE user_id=:user_id AND NOW() BETWEEN date_created AND date_expiring ORDER BY id DESC LIMIT 1";	 
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
   public function getMySubscriptionHistory($user_id)
 {
  $sql = "SELECT * FROM free_trials WHERE user_id=:user_id ORDER BY id DESC";	 
  //$sql = "SELECT * FROM free_trials WHERE user_id=:user_id AND NOW() NOT BETWEEN date_created AND date_expiring ORDER BY id DESC LIMIT 1";	 
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 /**************/
 

 public function getAllPlans()
 {
  $stmt = $this->db->prepare("SELECT * FROM free_trials ORDER BY id DESC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }

 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM free_trials WHERE id=:id");
  $stmt->bindparam(":id",$id);
 if($stmt->execute()){
	 return true;
 }else{
	 return false;
 }
 }

 public function updateStatus($qcode, $status, $date_updated)
 {	 
  $response = array();
  try
  {
   $stmt=$this->db->prepare("UPDATE free_trials SET status=:status, date_updated=:date_updated
             WHERE qcode=:qcode");
   $stmt->bindparam(":status", $status);
    $stmt->bindparam(":date_updated", $date_updated);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->execute();
	   
   $response["error"] = false; 
   $response["message"] = "Membership request updated successfully."; 
   return $response; 
  }
  catch(PDOException $e)
  {
   $response["error"] = true; 
   $response["message"] = "An error occurred while processing your request. Try again.";
   return $response;
  }
 }
 
 
  public function getNumMyPlans($user_id)
 {
  $sql = "SELECT count(*) FROM free_trials WHERE user_id=:user_id";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id));
  $number_of_rows = $stmt->fetchColumn();
  return $number_of_rows;
 }

  public function getNumMyActivePlan($user_id)
 {
  $sql = "SELECT count(*) FROM free_trials WHERE user_id=:user_id AND NOW() BETWEEN date_created AND date_expiring ORDER BY id DESC LIMIT 1";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id));
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 

 
 public function getAllMyPlans($user_id)
 {
  $sql = "SELECT * FROM free_trials WHERE user_id=:user_id ORDER BY id DESC";	 
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 /*******************************/
 public function getNumTotalActivePlans($plan_id)
 {
  $sql = "SELECT count(*) FROM free_trials WHERE plan_id=:plan_id";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":plan_id"=>$plan_id));
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 
 /*****************/
  public function getNumActivePlansFor($user_id, $plan_id)
 {
  $sql = "SELECT count(*) FROM free_trials WHERE plan_id=:plan_id AND user_id=:user_id AND NOW() BETWEEN date_created AND date_expiring";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":plan_id"=>$plan_id, ":user_id"=>$user_id));
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 
 public function getNumAllPlansFor($user_id, $plan_id)
 {
  $sql = "SELECT count(*) FROM free_trials WHERE plan_id=:plan_id AND user_id=:user_id";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":plan_id"=>$plan_id, ":user_id"=>$user_id));
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 /*******************/
 
  public function getAllMyPlansExcept($user_id, $id)
 {
  $status = "Active";    
  $sql = "SELECT * FROM free_trials WHERE user_id=:user_id AND status=:status AND id !=:id ORDER BY id DESC";	 
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":user_id"=>$user_id, ":status"=>$status, ":id"=>$id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 
 
   public function getNumAllActiveTrials()
 {
  $sql = "SELECT count(*) FROM free_trials WHERE NOW() BETWEEN date_created AND date_expiring";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }

 public function getNumAllFreeTrialsBetween($startDate, $endDate)
 {
   $sql = "SELECT count(*) FROM free_trials";
   if(!empty($startDate) && !empty($endDate)){
    $sql .= " WHERE timestamp >= '".$startDate."' AND timestamp <= '".$endDate."'";
  }
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $numRow = $stmt->fetchColumn();
  return $numRow;
 }
 
  public function getNumAllFreeTrials()
 {
  $sql = "SELECT count(*) FROM free_trials";	
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $number_of_rows = $stmt->fetchColumn();
  if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 
 

}