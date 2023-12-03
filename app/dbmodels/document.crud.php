<?php
require_once("Constants.php");
class DocumentCRUD
{
 private $db;
 
 function __construct($DB_con)
 {
  $this->db = $DB_con;
 }
 
 public function getNumDocs($is_published, $document_type)
 {
  $sql = "SELECT count(*) FROM documents WHERE document_type=:document_type AND is_published=:is_published";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":document_type"=>$document_type, ":is_published"=>$is_published));
  $number_of_rows = $stmt->fetchColumn();
    if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }

 public function getNumDocsBetween($is_published,  $document_type, $startDate, $endDate)
 {
  $status = "Active";
   $sql = "SELECT count(*) FROM documents WHERE document_type=".$document_type." AND is_published=".$is_published."";
   if(!empty($startDate) && !empty($endDate)){
    $sql .= " AND timestamp >= '".$startDate."' AND timestamp < '".$endDate."'";
  }
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $numRow = $stmt->fetchColumn();
  return $numRow;
 }
 
 public function create($user_id, $title, $description, $qcode, $cover, $category_id, $document_type, $author_name, $author_link, $author_desc, $file_type, $link, $price, $num_pages, $listen_time, $read_time, $tag, $is_published, $is_downloadable, $note, $date_created)
 {
  $response = array();	
  $response["error"] = true;   
  $response["code"] = INSERT_FAILURE;
  $response["debug"] = "";
  try
  {
   $stmt = $this->db->prepare("INSERT INTO documents(user_id, title, description, qcode, cover, category_id, document_type, author_name, author_link, author_desc, file_type, link, price, num_pages, listen_time, read_time, tag, is_published, is_downloadable, note, date_created)VALUES(:user_id, :title, :description, :qcode, :cover, :category_id, :document_type, :author_name, :author_link, :author_desc, :file_type, :link, :price, :num_pages, :listen_time, :read_time, :tag, :is_published, :is_downloadable, :note, :date_created)");
   $stmt->bindparam(":user_id", $user_id);
    $stmt->bindparam(":title", $title);
    $stmt->bindparam(":description", $description);
     $stmt->bindparam(":qcode", $qcode);
    $stmt->bindparam(":cover", $cover);
    $stmt->bindparam(":category_id", $category_id);
	$stmt->bindparam(":document_type", $document_type);
	$stmt->bindparam(":author_name", $author_name);
	$stmt->bindparam(":author_link", $author_link);
	$stmt->bindparam(":author_desc", $author_desc);
	$stmt->bindparam(":file_type", $file_type);
	$stmt->bindparam(":link", $link);
	$stmt->bindparam(":price", $price);
	$stmt->bindparam(":num_pages", $num_pages);
	
	$stmt->bindparam(":listen_time", $listen_time);
	$stmt->bindparam(":read_time", $read_time);
	$stmt->bindparam(":tag", $tag);
	$stmt->bindparam(":is_published", $is_published);
	$stmt->bindparam(":is_downloadable", $is_downloadable);
	$stmt->bindparam(":note", $note);
	$stmt->bindparam(":date_created", $date_created);
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
	   /**************************************/
   }else{
	   $response["error"] = true;  
       $response["code"] = INSERT_FAILURE; 
   }
   return $response;
  }
  catch(PDOException $e)
  {
	$response["debug"] = $e->getMessage();  
    $response["code"] = INSERT_FAILURE; 
   //echo $e->getMessage();  
   return $response;
  } 
 }
 
 public function update($id, $title, $description, $category_id, $document_type, $author_name, $author_link, $author_desc, $price, $num_pages, $listen_time, $read_time, $tag, $is_published, $is_downloadable, $note, $date_updated)
 {
   $stmt=$this->db->prepare("UPDATE documents SET title=:title,
   description=:description,
   category_id=:category_id,
   document_type=:document_type,
   author_name=:author_name,
   author_link=:author_link,
   author_desc=:author_desc,
   price=:price,
   num_pages=:num_pages,
   listen_time=:listen_time,
   read_time=:read_time,
   tag=:tag,
   is_published=:is_published,
   is_downloadable=:is_downloadable,
   note=:note,
   date_updated=:date_updated WHERE id=:id");
   
    $stmt->bindparam(":title",$title);
    $stmt->bindparam(":description", $description);
    $stmt->bindparam(":category_id", $category_id);
	$stmt->bindparam(":document_type", $document_type);
	$stmt->bindparam(":author_name", $author_name);
	$stmt->bindparam(":author_link", $author_link);
	$stmt->bindparam(":author_desc", $author_desc);
	$stmt->bindparam(":price", $price);
	$stmt->bindparam(":num_pages", $num_pages);
	$stmt->bindparam(":listen_time", $listen_time);
	$stmt->bindparam(":read_time", $read_time);
	$stmt->bindparam(":tag", $tag);
	$stmt->bindparam(":is_published", $is_published);
	$stmt->bindparam(":is_downloadable", $is_downloadable);
	
   $stmt->bindparam(":note",$note);
   $stmt->bindparam(":date_updated", $date_updated);
   $stmt->bindparam(":id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }
 
 public function updateCover($id, $cover)
 {
   $stmt=$this->db->prepare("UPDATE documents SET cover=:cover 
             WHERE id=:id ");
   $stmt->bindparam(":cover",$cover);
   $stmt->bindparam(":id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }
 

  public function updateFileInfo($id, $file_type, $num_pages, $note)
 {
   $stmt=$this->db->prepare("UPDATE documents SET file_type=:file_type, num_pages=:num_pages, note=:note  
             WHERE id=:id ");
   $stmt->bindparam(":file_type", $file_type);
   $stmt->bindparam(":num_pages", $num_pages);
   $stmt->bindparam(":note", $note);
   $stmt->bindparam(":id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }
 
 public function updateFileLink($id, $link)
 {
   $stmt=$this->db->prepare("UPDATE documents SET link=:link 
             WHERE id=:id ");
   $stmt->bindparam(":link",$link);
   $stmt->bindparam(":id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }
 
  public function addAudioLink($document_id, $sno, $title, $description, $file)
 {
  //$keyword = mysql_real_escape_string($keyword);     
  $response = array();	
  $response["error"] = true; 
  $response["message"] = "";   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO document_audios(document_id, sno, title, description, file)VALUES(:document_id, :sno, :title, :description, :file)");
   $stmt->bindparam(":document_id", $document_id);
   $stmt->bindparam(":sno", $sno);
   $stmt->bindparam(":title", $title);
   $stmt->bindparam(":description", $description);
   $stmt->bindparam(":file", $file);
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
     $response["code"] = INSERT_SUCCESS; 
	   /**************************************/
   }else{
	   $response["error"] = true;  
       $response["code"] = INSERT_FAILURE; 
   }
   return $response;
  }
  catch(PDOException $e)
  {
   $response["message"] = $e->getMessage();  
   return $response;
  } 
 }
 
  public function updateAudioLink($id, $file)
 {
   $stmt=$this->db->prepare("UPDATE document_audios SET file=:file 
             WHERE document_id=:document_id ");
   $stmt->bindparam(":file",$file);
   $stmt->bindparam(":document_id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }

  public function getAudioTracksByID($document_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM document_audios WHERE document_id=:document_id ORDER BY sno ASC");
  $stmt->execute(array(":document_id"=>$document_id));
  $result = $stmt->fetchAll();
  return $result;
 }

 public function getAudioTracksByQcode($qcode)
 {
  $stmt = $this->db->prepare("SELECT * FROM document_audios WHERE document_id IN (SELECT id FROM documents WHERE qcode=:qcode) ORDER BY sno ASC");
  $stmt->execute(array(":qcode"=>$qcode));
  $result = $stmt->fetchAll();
  return $result;
 }
 
  public function doesHaveAudio($document_id)
 {
  $sql = "SELECT count(*) FROM document_audios WHERE document_id=:document_id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":document_id"=>$document_id)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows > 0;
 }

 public function getAudioFileUrlByID($id)
 {
  $sql = "SELECT file FROM document_audios WHERE id=:id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":id"=>$id)); 
  $number_of_rows = $stmt->fetchColumn();
  return $number_of_rows;
 }

 public function getDocumentIDByTrackID($id)
 {
  $sql = "SELECT document_id FROM document_audios WHERE id=:id";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":id"=>$id)); 
  return $stmt->fetchColumn();
 }

 public function doesAudioTrackExist($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM document_audios WHERE id=:id");
  $result = $stmt->execute(array(":id"=>$id));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
  public function deleteAudioLink($id)
 {
  $stmt = $this->db->prepare("DELETE FROM document_audios WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }

 public function deleteAudioLinkByQCode($id)
 {
  $stmt = $this->db->prepare("DELETE FROM document_audios WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
 /******************/
 
 public function updateDateModified($id, $date_updated)
 {
   $stmt=$this->db->prepare("UPDATE documents SET date_updated=:date_updated 
             WHERE id=:id ");
   $stmt->bindparam(":date_updated",$date_updated);
   $stmt->bindparam(":id", $id);
   if($stmt->execute()){
	    return true;
   }
 return false;
 }
 
 public function getID($id)
 {
  $stmt = $this->db->prepare("SELECT * FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
  return $editRow;
 }
 
  public function getDoc($user_id, $document_type)
 { 
  $sql = "SELECT * FROM documents WHERE user_id=:user_id AND document_type=:document_type";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":user_id"=>$user_id, ":document_type"=>$document_type)); 
  $number_of_rows = $stmt->fetch(PDO::FETCH_ASSOC);
  return $number_of_rows;
 }
 
   public function isIDExists($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM documents WHERE id=:id");
  $result = $stmt->execute(array(":id"=>$id));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
  public function isQCodeExists($qcode)
 {
  $stmt = $this->db->prepare("SELECT * FROM documents WHERE qcode=:qcode");
  $result = $stmt->execute(array(":qcode"=>$qcode));
  $rows = $stmt->fetchAll();
  $num_rows = count($rows);
  return $num_rows > 0;
 }
 
  public function getIDByQCode($qcode)
 {
  $stmt = $this->db->prepare("SELECT id FROM documents WHERE qcode=:qcode");
  $stmt->execute(array(":qcode"=>$qcode));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
   public function getQCodeByID($id)
 {
  $stmt = $this->db->prepare("SELECT qcode FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getNameByID($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function getOwnerID($id)
 {
  $stmt = $this->db->prepare("SELECT user_id FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function getDocTitle($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
  public function getDocType($id)
 {
  $stmt = $this->db->prepare("SELECT document_type FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
   public function getDocCover($id)
 {
  $stmt = $this->db->prepare("SELECT cover FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
   public function getDocFileLink($id)
 {
  $stmt = $this->db->prepare("SELECT link FROM documents WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }
 
 public function doesHaveDoc($user_id, $document_type)
 { 
  $sql = "SELECT count(*) FROM documents WHERE user_id=:user_id AND document_type=:document_type";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":user_id"=>$user_id, ":document_type"=>$document_type)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows;
 }

 
  public function getAllDocuments($is_published = 1)
 {
      $sql = "";
     if($is_published == 1){
         $sql = "SELECT * FROM documents WHERE is_published=1 ORDER BY id DESC";
     }else{
         $sql = "SELECT * FROM documents ORDER BY id DESC";
     }
  $stmt = $this->db->prepare($sql);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllDocumentsByDocType($document_type)
 {
  $sql = "SELECT * FROM documents WHERE is_published=1 AND document_type=:document_type ORDER BY id DESC";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":document_type"=>$document_type)); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getFewRelatedDocuments($category_id)
 {
  $sql = "SELECT * FROM documents WHERE is_published=1 AND category_id=:category_id ORDER BY id DESC LIMIT 10";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":category_id"=>$category_id)); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
   public function getAllLatestLiveDocuments()
 {
  $sql = "SELECT * FROM documents WHERE is_published=1 ORDER BY id DESC";
  $stmt = $this->db->prepare($sql);
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 

   public function getAllTestimonials()
 {
      $sql = "";
      $is_published = 1;
     if($is_published == 1){
         $sql = "SELECT * FROM testimonials WHERE is_published=1 ORDER BY id DESC";
     }else{
         $sql = "SELECT * FROM testimonials ORDER BY id DESC";
     }
  $stmt = $this->db->prepare($sql);
  $stmt->execute(); 
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function getFilteredDocuments($document_type, $categoryIDs, $flag_filter, $is_published)
 {
    
 }
 
 public function getDocsFor($user_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM documents WHERE user_id =:user_id ORDER BY id DESC");
  $stmt->execute(array(":user_id"=>$user_id));
   $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
  public function getAllFreeEBooks()
 {
  $stmt = $this->db->prepare("SELECT * FROM documents WHERE document_type = 1 AND is_downloadable = 1 ORDER BY id DESC");
  $stmt->execute();
   $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function delete($id)
 {
  $stmt = $this->db->prepare("DELETE FROM documents WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
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
        $stmt = $this->db->prepare("SELECT id from documents WHERE qcode = :qcode");
		$stmt->execute(array(":qcode"=>$qcode));
         $editRow=$stmt->fetch(PDO::FETCH_ASSOC);
        return $editRow > 0;
    }
    
    /********************* KEYWORDS **********************/
  public function createDocKeyword($doc_id, $keyword)
 {
  //$keyword = mysql_real_escape_string($keyword);     
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO doc_keywords(doc_id, keyword)VALUES(:doc_id, :keyword)");
   $stmt->bindparam(":doc_id", $doc_id);
   $stmt->bindparam(":keyword", $keyword);
   if($stmt->execute()){
	   $response["error"] = false;  
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
	   /**************************************/
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
 
  public function getAllTags($doc_id)
 {
  $stmt = $this->db->prepare("SELECT * FROM doc_keywords WHERE doc_id = ".$doc_id." ORDER BY id ASC");
  $stmt->execute();
  $editRow=$stmt->fetchAll();
  return $editRow;
 }
 
 public function isTagged($doc_id, $keyword)
 {
  $sql = "SELECT count(*) FROM doc_keywords WHERE doc_id=:doc_id AND keyword =:keyword";
  $stmt = $this->db->prepare($sql); 
  $stmt->execute(array(":doc_id"=>$doc_id, ":keyword"=>$keyword)); 
  $number_of_rows = $stmt->fetchColumn(); 
  return $number_of_rows > 0;
 }
 
   public function deleteKeywordByID($id)
 {
  $stmt = $this->db->prepare("DELETE FROM doc_keywords WHERE id=:id");
  $stmt->bindparam(":id",$id);
  $stmt->execute();
  return true;
 }
 
 public function deleteTagsForDocument($doc_id)
 {
  $stmt = $this->db->prepare("DELETE FROM doc_keywords WHERE doc_id=:doc_id");
  $stmt->bindparam(":doc_id",$doc_id);
  $stmt->execute();
  return true;
 }
 
  public function getDocTypeName($id)
 {
  $stmt = $this->db->prepare("SELECT title FROM document_types WHERE id=:id");
  $stmt->execute(array(":id"=>$id));
  $result = $stmt->fetchColumn();
  return $result;
 }

 public function addDocumentView($document_id, $user_id)
 {
  $response = array();	
  $response["error"] = true;   
  try
  {
   $stmt = $this->db->prepare("INSERT INTO document_views(document_id, user_id)VALUES(:document_id, :user_id)");
   $stmt->bindparam(":document_id", $document_id);
   $stmt->bindparam(":user_id", $user_id);
   if($stmt->execute()){
	   $response["error"] = false;
	   $response["id"] = $this->db->lastInsertId();  
       $response["code"] = INSERT_SUCCESS; 
	   /**************************************/
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

 public function getNumViews($document_id)
 {
  $sql = "SELECT count(1) FROM document_views WHERE document_id=:document_id";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":document_id"=>$document_id));
  $number_of_rows = $stmt->fetchColumn();
    if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }

 public function getNumDocumentsInCategory($category_id)
 {
  $sql = "SELECT count(1) FROM documents WHERE is_published=1 AND category_id=:category_id";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":category_id"=>$category_id)); 
  $number_of_rows = $stmt->fetchColumn();
    if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }

 public function getNumViewsInCategory($category_id)
 {
  $sql = "SELECT count(1) FROM document_views WHERE document_id IN (SELECT id FROM documents WHERE category_id=:category_id)";
  $stmt = $this->db->prepare($sql);
  $stmt->execute(array(":category_id"=>$category_id));
  $number_of_rows = $stmt->fetchColumn();
    if(empty($number_of_rows) || $number_of_rows === null){
      return 0;
  }
  return $number_of_rows;
 }
 
}