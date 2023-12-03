<?php
require_once("Constants.php");
class CategoryCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
   public function getNumAllCategories($is_published)
 {
  $sql = "SELECT count(*) FROM categories WHERE is_published=:is_published";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":is_published"=>$is_published));
  $number_of_rows = $stmt->fetchColumn();
  return $number_of_rows;
 }
 
 public function create($title, $description, $qcode, $taxonomy, $magazine_only, $is_published)
 {
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO categories(title, description, qcode, taxonomy, magazine_only, is_published) VALUES(:title, :description, :qcode, :taxonomy, :magazine_only, :is_published)");
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":description", $description);
   $stmt->bindparam(":qcode",$qcode);
   $stmt->bindparam(":taxonomy",$taxonomy);
   $stmt->bindparam(":magazine_only",$magazine_only);
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
 
 
 public function update($id, $title, $description, $taxonomy, $magazine_only, $is_published)
 {
  try
  {
   $stmt=$this->db->prepare("UPDATE categories SET title=:title, 
                description=:description,
                taxonomy=:taxonomy,
                magazine_only=:magazine_only,
				is_published=:is_published
                WHERE id=:id");
   
   $stmt->bindparam(":title",$title);
   $stmt->bindparam(":description",$description);
   $stmt->bindparam(":taxonomy",$taxonomy);
   $stmt->bindparam(":magazine_only",$magazine_only);
   $stmt->bindparam(":is_published",$is_published);
   $stmt->bindparam(":id",$id);
   $stmt->execute();
	   
   return true; 
  }
  catch(PDOException $e)
  {
   echo $e->getMessage(); 
   return false;
  }
 }
 
 
 public function generateCode(){
		require_once("CodeGenerator.php");
		 $generator = new CouponGenerator;
         $tokenLength = 16;
         $voucherNum = $generator->generate($tokenLength);
		if($this->isCodeValid($voucherNum) > 0){
			generateDealCode();
		}
		return $voucherNum;
	}
	
	public function isCodeValid($qcode) {
        $stmt = $this->db->prepare("SELECT id from categories WHERE qcode = ?");
        $stmt->execute("s", $qcode);
         $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow > 0;
    }
    
     public function recheckQCode($qcode, $id)
 {
  $stmt = $this->db->prepare("SELECT id FROM categories WHERE qcode=:qcode WHERE id != '.$id.'");
  $result = $stmt->execute(array(":qcode"=>$qcode));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM categories WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getIDByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT id FROM categories WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function doNameExists($title)
 { 
  $sql = "SELECT count(*) FROM categories WHERE title=:title";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":title"=>$title)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }
 
  public function getAllCategories($is_published = 1)
 {
      $sql = "";
     if($is_published == 1){
         $sql = "SELECT * FROM categories WHERE is_published=1 ORDER BY id DESC";
     }else{
         $sql = "SELECT * FROM categories ORDER BY id DESC";
     }
  $stmt = $this->db->prepare($sql);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function getActiveEbookCategories()
 {
  $is_published = 1;	 
  $stmt = $this->db->prepare("SELECT * FROM categories WHERE is_published=:is_published AND magazine_only =0 ORDER BY id DESC");
  $stmt->bindparam(":is_published",$is_published);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllMagazineCategories()
 {
  $is_published = 1;	 
  $stmt = $this->db->prepare("SELECT * FROM categories WHERE is_published=:is_published AND magazine_only =1");
  $stmt->bindparam(":is_published",$is_published);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllRibbonTags()
 {
  $stmt = $this->db->prepare("SELECT * FROM store_tags");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllCurrencies()
 {
  $stmt = $this->db->prepare("SELECT * FROM currencies");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM categories WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
 public function updateImage($id, $image)
 {	 
  $response = array();	
  $response["error"] = true; 
  $response["message"] = ""; 
  
   /************* UPLOAD IMAGE **************/
	   try{
		   if(!empty($image)){
			$path = "images/categories/".$id.".jpg";
		    $actualpath = $path;
			
			file_put_contents("uploads/".$path, base64_decode($image));
			$stmt2=$this->db->prepare("UPDATE categories SET image=:image
             WHERE id=:id");
   
            $stmt2->bindparam(":image",$actualpath);
            $stmt2->bindparam(":id",$id);
            $res = $stmt2->execute();
			if($res){
				 $response["message"] = "category image updated successfully."; 
			}
			}
	   }catch (Exception $e) {
		   $response["message"] = "Could not upload category Image.";
	   }
	   /**************************************/
	   
   $response["error"] = false; 
   return $response; 
 }
 
}