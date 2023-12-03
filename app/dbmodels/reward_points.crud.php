<?php
require_once("Constants.php");
class RewardPointCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
  public function create($user_id, $points, $transaction_type, $note, $status, $date_created)
 {
  $date_updated = "";	 
  $status = "Pending";
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO reward_points(user_id, points, transaction_type, note, status , date_created) VALUES(:user_id, :points, :transaction_type, :note, :status, :date_created)");
   $stmt->bindparam(":user_id", $user_id);
   $stmt->bindparam(":points", $points);
   $stmt->bindparam(":transaction_type", $transaction_type);
   $stmt->bindparam(":note", $note);
   $stmt->bindparam(":status", $status);
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
   //echo $e->getMessage();  
   $response["error"] = true;  
   $response["msg"] = "Error => ".$e->getMessage();  
   $response["code"] = INSERT_FAILURE; 
   return $response;
  }  
 }
 
 //PAYMODEL CREATE TXN
 public function createTransaction($user_id, $item_name, $total_price, $currency_code, $date_created, $txn_id, $sender, $gateway_status, $status)
 {
  $response = array();	
  $response["error"] = true;   
  $temp = "";
  try
  {
   $stmt = $this->db->prepare("INSERT INTO referrals_txns(user_id, item_name, :total_price, :currency_code, :date_created, txn_id, sender, gateway_status, status) VALUES(:user_id, item_name, :total_price, :currency_code, :date_created, :txn_id, :sender, :gateway_status, :status)");
   $stmt->bindparam(":user_id", $user_id);
   $stmt->bindparam(":item_name", $item_name);
   $stmt->bindparam(":total_price", $total_price);
   $stmt->bindparam(":currency_code", $currency_code);
   $stmt->bindparam(":date_created", $date_created);
  
   $stmt->bindparam(":txn_id", $txn_id);
   $stmt->bindparam(":sender", $sender);
   $stmt->bindparam(":gateway_status", $gateway_status);
   $stmt->bindparam(":status", $status);
   
   
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId(); 
       $shopID = $response["id"]; 	   
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
  $stmt = $this->db->prepare("SELECT * FROM reward_points WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }

  public function getAllMyRewardPoints($user_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM reward_points WHERE user_id =:user_id ORDER BY id DESC");
  $stmt->execute(array(":user_id"=>$user_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
   public function getAllRewardPoints()
 {
  $stmt = $this->db->prepare("SELECT * FROM reward_points ORDER BY id DESC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
    public function getRewardPointsSummary()
 {
  $stmt = $this->db->prepare("SELECT user_id, sum(points) AS points FROM reward_points GROUP BY user_id");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 
  public function getStatus($id)
 {
  $stmt = $this->db->prepare("SELECT status FROM referrals WHERE id='$id'");
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 
 public function getCurrentRewardPointFor($user_id)
 {
  $status = "Complete";
  $sql = "SELECT SUM(points) FROM reward_points WHERE user_id=:user_id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":user_id"=>$user_id)); 
  $number_of_rows = $stmt->fetchColumn(); 
  if(!empty($number_of_rows)){
  return $number_of_rows;
  }else{
	 return "0";
  }
 }
 
   public function getNumRedeems($referral_code)
 { 
  $sql = "SELECT count(*) FROM users WHERE referral_code=:referral_code";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":referral_code"=>$referral_code)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
    public function getNumPeopleRewarded()
 { 
  $sql = "SELECT count(DISTINCT user_id) FROM reward_points";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
  public function getTotalRewardPointsAwarded()
 {
  $status = "Complete";
  $sql = "SELECT SUM(points) FROM reward_points";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(); 
  $number_of_rows = $stmt->fetchColumn(); 
  if(!empty($number_of_rows)){
  return $number_of_rows;
  }else{
	 return "0";
  }
 }
 
}