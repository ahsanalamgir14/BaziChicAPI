<?php
require_once("Constants.php");
class FAQCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function create($title, $description, $category_id, $subcategory_id, $sort_id, $qcode, $url, $is_published)
 {
  $response = array();
  $response["error"] = true;
  try
  {
   $stmt = $this->db->prepare("INSERT INTO faqs(title, description, category_id, subcategory_id, sort_id, qcode, url, is_published) VALUES(:title, :description, :category_id, :subcategory_id, :sort_id, :qcode, :url, :is_published)");
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":description",$description);
   $stmt->bindparam(":category_id",$category_id);
   $stmt->bindparam(":subcategory_id",$subcategory_id);
   $stmt->bindparam(":sort_id", $sort_id);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->bindparam(":url",$url);
   $stmt->bindparam(":is_published", $is_published);
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
 
 
 public function update($id, $title, $description, $category_id, $subcategory_id, $sort_id, $qcode, $url, $is_published)
 {
 $response = array();
  $response["error"] = true; 
  $response["message"] = "";
  try
  {
   $stmt=$this->db->prepare("UPDATE faqs SET title=:title, 
                description=:description,
				category_id=:category_id,
				subcategory_id=:subcategory_id,
                sort_id=:sort_id,
                qcode=:qcode,
				url=:url,
				is_published=:is_published
                WHERE id=:id");
   
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":description",$description);
   $stmt->bindparam(":category_id",$category_id);
   $stmt->bindparam(":subcategory_id",$subcategory_id);
   $stmt->bindparam(":sort_id",$sort_id);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->bindparam(":url",$url);
   $stmt->bindparam(":is_published",$is_published);
   $stmt->bindparam(":id",$id);
   $stmt->execute();
   $response["error"] = false;  
  }
  catch(PDOException $e)
  {
   $response["message"] = "Exception => ".$e->getMessage(); 
   $response["error"] = true;  
   $response["code"] = INSERT_FAILURE; 
   return false;
  }
 }
 
 
 public function generateCode(){
		require_once("CodeGenerator.php");
		 $generator = new CouponGenerator;
         $tokenLength = 16;
         $voucherNum = $generator->generate($tokenLength);
		if($this->isCodeValid($voucherNum) > 0){
			generateCode();
		}
		return $voucherNum;
	}
	
	public function isCodeValid($qcode) {
        $stmt = $this->db->prepare("SELECT id from faqs WHERE qcode = ?");
        $stmt->execute("s", $qcode);
         $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow > 0;
    }
   
    public function isUFNExists($url, $exceptionID=0) {
       if($exceptionID > 0){
            $stmt = $this->db->prepare("SELECT count(*) from faqs WHERE url = ? AND id != ?");
        $stmt->execute("s", $url);
        $stmt->execute("i", $exceptionID);
         $editRow=$stmt->fetchColumn;
        return $editRow > 0;
       }else{
            $stmt = $this->db->prepare("SELECT count(*) from faqs WHERE url = ?");
        $stmt->execute("s", $url);
         $editRow=$stmt->fetchColumn;
        return $editRow > 0;
       }
    }
	
    public function recheckQCode($qcode, $id)
 {
  $stmt = $this->db->prepare("SELECT id FROM faqs WHERE qcode=:qcode WHERE id != '.$id.'");
  $result = $stmt->execute(array(":qcode"=>$qcode));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM faqs WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT * FROM faqs WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getBySEOUrl($url)
 {
  $stmt = $this->db->prepare("SELECT * FROM faqs WHERE url=:url");
  $stmt->execute(array(":url"=>$url));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM faqs WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getIDByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT id FROM faqs WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
   public function getQCode($id)
 {
  $stmt = $this->db->prepare("SELECT qcode FROM faqs WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
    public function doesIDExists($id)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM faqs WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result > 0;
 }
 
  public function getNumFAQsInCategory($category_id)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM faqs WHERE category_id=:category_id");
  $stmt->execute(array(":category_id"=>$category_id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function getNumFAQsInSubCategory($subcategory_id)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM faqs WHERE subcategory_id=:subcategory_id");
  $stmt->execute(array(":subcategory_id"=>$subcategory_id));
  $result = $stmt->fetchColumn();
  return $result;
 }

 
  public function getAllFAQs($is_published = 1)
 {
    $sql = "";
    if($is_published == 1){
         $sql = "SELECT * FROM faqs WHERE is_published=1 ORDER BY id DESC";
    }else{
         $sql = "SELECT * FROM faqs ORDER BY id DESC";
    }
  $stmt = $this->db->prepare($sql);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM faqs WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
}