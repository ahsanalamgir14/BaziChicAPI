<?php
require_once("Constants.php");
class ReferralCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
  public function create($user_id, $code, $date_created)
 {
  $date_updated = "";	 
  $status = "Pending";
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO referrals(user_id, code, status, date_created, date_updated) VALUES(:user_id, :code, :status, :date_created, :date_updated)");
   $stmt->bindparam(":user_id", $user_id);
   $stmt->bindparam(":code", $code);
   $stmt->bindparam(":status", $status);
   $stmt->bindparam(":date_created", $date_created);
   $stmt->bindparam(":date_updated", $date_updated);
  
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
 
 //UPDATE PAY MODEL
public function updateReferral($code, $status, $date_updated)
 {
  try
  {
   $stmt=$this->db->prepare("UPDATE referrals SET status=:status,
				date_updated=:date_updated 
             WHERE code=:code");
   $stmt->bindparam(":status",$status);
    $stmt->bindparam(":date_updated",$date_updated);
   $stmt->bindparam(":code",$code);
   $stmt->execute();
   return true; 
  }
  catch(PDOException $e)
  {
   echo $e->getMessage(); 
   return false;
  }
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM referrals WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }

  public function getAllMyReferrals($user_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM referrals WHERE user_id =:user_id");
  $stmt->execute(array(":user_id"=>$user_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
   public function getMyConnections($ref_user_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM users WHERE ref_user_id =:ref_user_id");
  $stmt->execute(array(":ref_user_id"=>$ref_user_id));
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
 
 public function getUserID($code)
 {
  $stmt = $this->db->prepare("SELECT user_id FROM referrals WHERE code =:code");
  $stmt->execute(array(":code"=>$code));
  $stmt->execute();
  $rows = $stmt->fetchColumn(); 
  return $rows;
 }
 

 public function getNumMyRefeerals($user_id, $status)
 { 
     
     if(!empty($status)){
          $sql = "SELECT count(id) FROM referrals WHERE user_id=:user_id AND status=:status";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":user_id"=>$user_id, ":status"=>$status)); 
     }else{
          $sql = "SELECT count(id) FROM referrals WHERE user_id=:user_id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":user_id"=>$user_id)); 
     }
  $number_of_rows = $stmt->fetchColumn(); 
  if(!empty($number_of_rows)){
  return $number_of_rows;
  }else{
	 return 0;
  }
 }
 
 
  public function getNumAllSystemRefeerals()
 { 
  $sql = "SELECT count(id) FROM referrals";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(); 
  $number_of_rows = $stmt->fetchColumn(); 
  if(!empty($number_of_rows)){
  return $number_of_rows;
  }else{
	 return 0;
  }
 }
 
 
    public function getNumMyConnections($ref_user_id)
 {
  $stmt = $this->db->prepare("SELECT count(id) FROM users WHERE ref_user_id =:ref_user_id");
  $stmt->execute(array(":ref_user_id"=>$ref_user_id));
  $editRow=$stmt->fetchColumn();
  return $editRow;
 }
 
   public function getNumRedeems($referral_code)
 { 
  $sql = "SELECT count(*) FROM users WHERE referral_code=:referral_code";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":referral_code"=>$referral_code)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
  public function isReferralCodeExist($code)
 { 
  $sql = "SELECT count(*) FROM referrals WHERE code=:code";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":code"=>$code)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
   public function isReferralCodeValid($code)
 {
  $status = "Pending";	 
  $sql = "SELECT count(*) FROM referrals WHERE code=:code AND status =:status";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":code"=>$code, ":status"=>$status)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
}