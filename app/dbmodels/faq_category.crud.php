<?php
require_once("Constants.php");
class FAQCategoryCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
   public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM faq_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
     public function doesIDExists($id)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM faq_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result > 0;
 }
 
 public function create($title, $sort_id)
 {
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO faq_categories(title, sort_id) VALUES(:title, :sort_id)");
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":sort_id", $sort_id);
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
	$response["error"] = true;  
    $response["code"] = INSERT_FAILURE;   
   //echo $e->getMessage();  
   return $response;
  }
  
 }
 
 
 public function update($id, $title, $sort_id)
 {
  try
  {
   $stmt=$this->db->prepare("UPDATE faq_categories SET title=:title, 
                sort_id=:sort_id 
                WHERE id=:id");
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":sort_id",$sort_id);
   $stmt->bindparam(":id",$id);
   $stmt->execute();
   return true; 
  }
  catch(PDOException $e)
  {
   //echo $e->getMessage(); 
   return false;
  }
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM faq_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
 
 
  public function getFAQSubCategories($category_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM faq_sub_categories WHERE category_id=:category_id");
  $stmt->execute(array(":category_id"=>$category_id));
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
   public function getAllFAQSubCategories()
 {
  $stmt = $this->db->prepare("SELECT * FROM faq_sub_categories ORDER BY sort_id");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 
   public function getSubCategoryByID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM faq_sub_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
    public function getSubCategoryNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM faq_sub_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getAllFAQCategories()
 {
  $stmt = $this->db->prepare("SELECT * FROM faq_categories ORDER BY sort_id");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM faq_categories WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
 
 
 /******************* SUB CATEGORIES *********************/
 public function createSubCategory($title, $qcode, $category_id, $sort_id)
 {
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO faq_sub_categories(title, qcode, category_id, sort_id) VALUES(:title, :qcode, :category_id, :sort_id)");
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":qcode", $qcode);
   $stmt->bindparam(":category_id", $category_id);
   $stmt->bindparam(":sort_id", $sort_id);
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
	$response["error"] = true;  
    $response["code"] = INSERT_FAILURE;   
   //echo $e->getMessage();  
   return $response;
  }
  
 }
 
 
 public function updateSubCategory($id, $title, $qcode, $category_id, $sort_id)
 {
  try
  {
   $stmt=$this->db->prepare("UPDATE faq_sub_categories SET title=:title, 
   qcode=:qcode,
   category_id=:category_id,
                sort_id=:sort_id 
                WHERE id=:id");
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->bindparam(":category_id",$category_id);
   $stmt->bindparam(":sort_id",$sort_id);
   $stmt->bindparam(":id",$id);
   $stmt->execute();
   return true; 
  }
  catch(PDOException $e)
  {
   //echo $e->getMessage(); 
   return false;
  }
 }
 
  public function deleteSubCategory($id)
 {
  $stmt = $this->db->prepare("DELETE FROM faq_sub_categories WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
  public function doesSubCategoryIDExists($id)
 {
  $stmt = $this->db->prepare("SELECT count(*) FROM faq_sub_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result > 0;
 }
 
   public function getSubCategoryQCode($id)
 {
  $stmt = $this->db->prepare("SELECT qcode FROM faq_sub_categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getSubCategoryIDByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT id FROM faq_sub_categories WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function generateSubCategoryCode(){
		require_once("CodeGenerator.php");
		 $generator = new CouponGenerator;
         $tokenLength = 16;
         $voucherNum = $generator->generate($tokenLength);
		if($this->isCodeValid($voucherNum) > 0){
			generateSubCategoryCode();
		}
		return $voucherNum;
	}
	
	public function isCodeValid($qcode) {
        $stmt = $this->db->prepare("SELECT id from faq_sub_categories WHERE qcode = ?");
        $stmt->execute("s", $qcode);
         $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow > 0;
    }
    
 /**************************************/
 
}