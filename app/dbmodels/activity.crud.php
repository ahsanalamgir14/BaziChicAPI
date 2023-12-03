<?php
require_once("Constants.php");
class ActivityCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function create($who_id, $title, $message, $data_id, $data_title, $status, $date_created)
 {
  $response = array();	
  $response["error"] = true;   
  $response["msg"] = "";
  $response["code"] = INSERT_FAILURE; 
  try
  {
   $stmt = $this->db->prepare("INSERT INTO activities(who_id, title, message, status, data_id, data_title, date_created) VALUES(:who_id, :title, :message, :status, :data_id, :data_title, :date_created)");
   $stmt->bindparam(":who_id",$who_id);
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":message",$message);
   $stmt->bindparam(":status",$status);
   $stmt->bindparam(":data_id",$data_id);
   $stmt->bindparam(":data_title",$data_title);
   $stmt->bindparam(":date_created",$date_created);

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
   $response["msg"] = $e->getMessage();  
   $response["error"] = true;  
   $response["code"] = INSERT_FAILURE; 
   return $response;
  }
  
 }
 
 public function getNumUnreadActivities($who_id)
 {
  $status = "New";	 
  $stmt = $this->db->prepare("SELECT * FROM activities WHERE who_id=:who_id AND status =:status ORDER BY id DESC");
  $stmt->execute(array(":who_id"=>$who_id, ":status"=>$status));
  $stmt->fetchAll();
  $numRow = $stmt->rowCount();
  return $numRow;
 }
 
 public function updateStatus($id, $status)
 {	 
  try
  {
   $stmt=$this->db->prepare("UPDATE activities SET status=:status
             WHERE id=:id ");
   $stmt->bindparam(":status", $status);
   $stmt->bindparam(":id",$id);
   $stmt->execute();
   return true; 
  }
  catch(PDOException $e)
  {
   return false;
  }
 }
 
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM activities WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
 //Paginated Example
 public function getActivitiesFor($who_id, $sno = 1)
 {
 if($sno <= 0){
     $sno = 0;
 }
  $startRange = 0;
  $endRange = 500;
  if($sno > 1){
    $startRange = ($sno -1)*10;
    $endRange = $startRange + 9;
  }
  $stmt = $this->db->prepare("SELECT * FROM activities WHERE who_id=:who_id ORDER BY id DESC LIMIT :startRange, :endRange");
  $stmt->execute(array(":who_id"=>$who_id, ":startRange"=>$startRange,":endRange"=>$endRange));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }

 public function getBaziChicActivitiesFor($who_id=0, $startRange=0, $endRange=5)
 {
  $sql = "SELECT * FROM activities";
  if($who_id > 0){
    $sql .= " WHERE who_id=".$who_id."";
  }
  $sql .= " ORDER BY id DESC";
  if($startRange >= 0){
    $sql .= " LIMIT ".$startRange.", ".$endRange."";
  }
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function getFewActivitiesFor($who_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM activities WHERE who_id=:who_id ORDER BY id DESC LIMIT 5");
  $stmt->execute(array(":who_id"=>$who_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function getNumActivitiesFor($who_id)
 { 
  $sql = "SELECT count(*) FROM activities WHERE who_id=:who_id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":who_id"=>$who_id)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM activities WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
 
 public function getActionLink($data_id, $data_title){
    if(!empty($data_title) && !empty($data_id)){
	$data_type = $data_title;
		  
	switch ($data_title) {
        case "Like":
		case "Review":
        $action_url = "book-detail/".$data_id;
        break;
		
		case "Membership":
        $action_url = "membership-details/".$data_id;
        break;
  
    default:
        $action_url = "#";
}
	  }else{
		  $action_url = "#";
	  }
 }
 
 public function getFewTopActivities()
 {
  $stmt = $this->db->prepare("SELECT * FROM activities ORDER BY id DESC LIMIT 10");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
}