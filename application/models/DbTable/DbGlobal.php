<?php

class Application_Model_DbTable_DbGlobal extends Zend_Db_Table_Abstract
{
    // set name value
	public function setName($name){
		$this->_name=$name;
	}
	
	/**
	 * get selected record of $sql
	 * @param string $sql
	 * @return array $row;
	 */
	public function getGlobalDb($sql)
  	{
  		$db=$this->getAdapter();
  		$row=$db->fetchAll($sql);
  		if(!$row) return NULL;
  		return $row;
  	}
  	public function getGlobalDbRow($sql)
  	{
  		$db=$this->getAdapter();
  		$row=$db->fetchRow($sql);
  		if(!$row) return NULL;
  		return $row;
  	}
  	
  	public static function getActionAccess($action)
    {
    	$arr=explode('-', $action);
    	return $arr[0];    	
    }     
    
    /**
     * get CSO options for select box
     * @return array $options
     */
    public function getOptionCSO(){
    	$options = array('Please select');
    	$sql = "SELECT id, name_en FROM fi_cso ORDER BY name_en";
    	$rows = $this->getGlobalDb($sql);
    	foreach($rows as $ele){
    		$options[$ele['id']] = $ele['name_en'];
    	}
    	return $options;
    }
    
    /**
     * boolean true mean record exist already
     * @param string $conditions
     * @param string $tbl_name
     * @return boolean
     */
    public function isRecordExist($conditions,$tbl_name){
		$db=$this->getAdapter();
		$sql="SELECT * FROM ".$tbl_name." WHERE ".$conditions; 
		$stm = $db->query($sql);
		$row = $stm->fetchAll();
    	if(!$row) return false;
    	return true;    	
    }
    public function porductLocationExist($pro_id, $location_id){//used
    	$db=$this->getAdapter();
    	
    	$sql="SELECT ProLocationID,qty,qty_onorder,qty_avaliable,LocationId,pro_id,qty_onsold FROM tb_prolocation WHERE pro_id = ".$pro_id." AND LocationId = ".$location_id." LIMIT 1";
    	try{
    	$row = $db->fetchRow($sql);
    	}catch (Exception $e){
    		var_dump($sql);
    		die($e->getMessage());
    	}
    	//echo $sql;exit();
    	
    	return $row;
    }
    //get value in product inventory with product location (Joint)
    
    public function productLocationInventory($pro_id, $location_id){
    	$db=$this->getAdapter();
    	
    	$sql="SELECT pl.ProLocationID,pl.qty,pl.qty_onorder,pl.qty_onsold,pl.qty_avaliable
    	,(SELECT p.qty_onorder FROM tb_product AS p WHERE p.pro_id= pl.pro_id LIMIT 1)AS pqty_onorder
    	,(SELECT p.qty_onhand 	FROM tb_product AS p WHERE p.pro_id = pl.`pro_id` LIMIT 1) AS qty_onhand
    	,(SELECT p.qty_available 	FROM tb_product AS p WHERE p.pro_id = pl.`pro_id` LIMIT 1) AS qty_available
    	,(SELECT p.qty_onsold FROM tb_product AS p WHERE p.`pro_id` = pl.pro_id LIMIT 1) AS pqty_onsold 
    	FROM tb_prolocation AS pl WHERE pl.pro_id =".$pro_id." AND pl.LocationId=".$location_id." LIMIT 1";
    	//echo $sql;exit();
    	
    	$row = $db->fetchRow($sql);
    	//if(!$row) return false;
    	return $row;   	
    }
    //for get product product inventory but if have in prodcut location
    public function productInventoryExist($itemId){
    	$db=$this->getAdapter();
    	$sql="SELECT pl.ProLocationID, pl.qty, iv.QuantityOnHand, iv.QuantityAvailable
				FROM tb_prolocation AS pl
				INNER JOIN tb_inventorytotal AS iv ON iv.ProdId = pl.pro_id WHERE pl.pro_id=".$itemId." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	if(!$row) return false;
    	return $row;    	
    }
    
    //to get and check if product total inventory exist 8/26/13
    public function InventoryExist($pro_id){
    	$db=$this->getAdapter();
    	$sql="SELECT * FROM tb_product WHERE pro_id =".$pro_id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	if(!$row) return false;
    	return $row;
    }
    public function productLocation($pro_id,$location_id){
    	$db=$this->getAdapter();
    	$sql="SELECT * FROM tb_prolocation WHERE pro_id =".$pro_id." AND LocationId = ".$location_id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	if(!$row) return false;
    	return $row;
    }
    public function QtyProLocation($pro_id,$location_id){//get qty location
    	$db=$this->getAdapter();
    	$sql="SELECT ProLocationID,pro_id,qty FROM tb_prolocation WHERE pro_id =".$pro_id." AND LocationId = ".$location_id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	return $row;
    }
	//if myProductExist
    public function myProductExist($pro_id){
    	$db=$this->getAdapter();
    	$sql="SELECT pro_id FROM tb_product WHERE pro_id =".$pro_id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	return $row;
    }
    
    public function userSaleOrderExist($order_id , $location_id){
    	$db = $this->getAdapter();
    	$sql = "SELECT order_id FROM tb_sales_order WHERE order_id =".$order_id." AND LocationId = $location_id LIMIT 1";
    	$row= $db->fetchRow($sql);
    	return $row;
    }
    
    public function userPurchaseOrderExist($order_id , $location_id){
    	$db = $this->getAdapter();
    	$sql = "SELECT order_id FROM tb_purchase_order WHERE order_id =".$order_id." AND LocationId = $location_id LIMIT 1";
    	$row= $db->fetchRow($sql);
    	return $row;
    }
    //if user purchase exist
    public function userPurchaseExist($order_id , $location_id){
    	$db = $this->getAdapter();
    	$sql = "SELECT order_id FROM tb_purchase_order WHERE order_id =".$order_id." AND LocationId = $location_id"." LIMIT 1";
    	$row= $db->fetchRow($sql);
    	return $row;
    }  
    //check product location history exit(for update prodcut) 28/8
    public function prodcutHistoryExist($location_id,$id){
    	$db=$this->getAdapter();
    	$sql="SELECT pl.ProLocationID, pl.qty FROM tb_prolocation_history AS pl
    	INNER JOIN tb_product AS p ON p.pro_id = pl.pro_id
    	WHERE pl.LocationId = ".$location_id." AND p.pro_id=".$id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	return $row;
    }
    //check if not have in product location history
    public function proLocationHistoryNoExist($id){
    	$db=$this->getAdapter();
    	$sql="SELECT qty,locationID FROM tb_prolocation_history
    	WHERE pro_id= $id AND LocationId NOT IN
    	( SELECT LocationId FROM tb_prolocation where pro_id = $id) ";
    	$row = $db->fetchAll($sql);
    	if(!$row) return false;
    	return $row;
    	
    }
    //for check order history exist 
    final public function orderHistoryExitRow($order_id){
    	$db=$this->getAdapter();
    	$sql="SELECT * FROM `tb_order_history` WHERE `order`= ".$order_id." LIMIT 1";
    	$row=$db->fetchRow($sql);
    	return $row;
    	
    }
    final public function purchaseOrderHistoryExitAll($order_id){
    	$db=$this->getAdapter();
    	$sql="SELECT * FROM `tb_purchase_order_history` WHERE type=1 AND `order`=". $order_id;
    	$rows=$db->fetchAll($sql);
    	//if(!$rows) return false;
    	return $rows;
    	 
    }
    final public function purchaseOrderHistory($order_id){
    	$db=$this->getAdapter();
   	$sql="SELECT * FROM `tb_purchase_order_history` WHERE type=1 AND `order`=". $order_id;
    	$rows=$db->fetchAll($sql);
    	//if(!$rows) return false;
    	return $rows;
    
    }
    final public function salesOrderHistoryExitAll($order_id){
    	$db=$this->getAdapter();
    	$sql="SELECT * FROM `tb_order_history` WHERE type=2 AND `order`= ".$order_id;
    	$rows=$db->fetchAll($sql);
    	if(!$rows) return false;
    	return $rows;
    
    }
    final public function inventoryLocation($locationid, $itemId){
    	$db=$this->getAdapter();
    	$sql="SELECT pl.ProLocationID, pl.`qty_onorder` ,pl.qty, p.qty_onhand,p.qty_available
    	FROM tb_prolocation AS pl
    	INNER JOIN tb_product AS p ON p.pro_id = pl.pro_id
    	WHERE pl.LocationId = ".$locationid. " AND pl.pro_id= ".$itemId." LIMIT 1";
    	$row=$db->fetchRow($sql);
    	return $row;
    }
    final public function productInvetoryLocation($locationid, $itemId){
    	$db=$this->getAdapter();
    	$sql="SELECT pl.ProLocationID, pl.`qty_onorder` ,pl.qty, p.qty_onhand,p.qty_available
    	FROM tb_prolocation AS pl
    	INNER JOIN tb_product AS p ON p.pro_id = pl.pro_id
    	WHERE pl.LocationId = ".$locationid. " AND pl.pro_id= ".$itemId." LIMIT 1";
    	$row=$db->fetchRow($sql);
    	return $row;
    }
    
    
    
    /**
     * insert record to table $tbl_name
     * @param array $data
     * @param string $tbl_name
     */
    public function addRecord($data,$tbl_name){
    	$this->setName($tbl_name);
    	return $this->insert($data);
    }
    
    
    /**
     * update record to table $tbl_name
     * @param array $data
     * @param int $id
     * @param string $tbl_name
     */
//     public function updateRecord($data,$id,$tbl_name){
//     	$this->setName($tbl_name);
//     	$where=$this->getAdapter()->quoteInto('pro_id=?',$id);
//     	$this->update($data,$where);
//     }
	public function updateRecord($data,$id,$updateby,$tbl_name){
		$tb = $this->setName($tbl_name);
		$where=$this->getAdapter()->quoteInto($updateby.'=?',$id);
		$rs = $this->update($data,$where);
		//echo $rs;//exit();
		
	}
    
    public function DeleteRecord($tbl_name,$id){
    	$db = $this->getAdapter();
		$sql = "UPDATE ".$tbl_name." SET status=0 WHERE id=".$id;
		return $db->query($sql);
    }

    public function deleteRecords($sql){
    	$db = $this->getAdapter();
		return $db->query($sql);
    } 

     public function DeleteData($tbl_name,$where){
    	$db = $this->getAdapter();
		$sql = "DELETE FROM ".$tbl_name.$where;
		return $db->query($sql);
    } 
    
    public function convertStringToDate($date, $format = "Y-m-d H:i:s")
    {
    	if(empty($date)) return NULL;
    	$time = strtotime($date);
    	return date($format, $time);
    }
    /* @Desc: add or sub qty of item depend on item and stock
     * @param $stockID stock id
     * @param $itemQtys array of item id and item qty
     * @param $sign: + | -
     * */
    public function query($sql){
    	$db = $this->getAdapter();
    	return $db->query($sql);	
    }
    public function fetchArray($result){
    	$db = $this->getAdapter();
    	return mysql_fetch_assoc($result);
    }
    public function getAccessPermission($level, $str_condition,  $location_id){
    	if($level==1 OR $level==2){
    		return false;
    	}
    	else{
    		$result = "$str_condition =".$location_id;
    		return $result;
    	} 
    }
    public function getSetting(){
    	$DB = $this->getAdapter();
    	$sql="SELECT * FROM `tb_setting` ";
    	RETURN $DB->fetchAll($sql);
    }
    public static function GlobalgetUserId(){
    	$session_user=new Zend_Session_Namespace('auth');
    	return $session_user->user_id;
    }
    public static function writeMessageErr($err=null)
    {
    	$request=Zend_Controller_Front::getInstance()->getRequest();
    	$action=$request->getActionName();
    	$controller=$request->getControllerName();
    	$module=$request->getModuleName();
    
    	$session = new Zend_Session_Namespace('auth');
    	$user_name = $session->user_name;
    
    	$file = "../logs/error.log";
    	if (!file_exists($file)) touch($file);
    	$Handle = fopen($file, 'a');
    	$stringData = "[".date("Y-m-d H:i:s")."]"." [user]:".$user_name." [module]:".$module." [controller]:".$controller. " [action]:".$action." [Error]:".$err. "\n";
    	fwrite($Handle, $stringData);
    	fclose($Handle);
    
    }
    
    // ****************** Check Product Location  **************************
    public function productLocationInventoryCheck($pro_id, $location_id){
    	$db=$this->getAdapter();
    	$sql="SELECT pl.ProLocationID, pl.qty, p.qty_onorder
			FROM tb_prolocation AS pl
			INNER JOIN tb_product AS p ON p.pro_id = pl.pro_id
			WHERE pl.LocationId =".$location_id." AND pl.pro_id = ".$pro_id." LIMIT 1";
    	$row = $db->fetchRow($sql);
    	return $row;
    }
    public function getQtyFromProductById($id){
    	$db = $this->getAdapter();
    	$sql="SELECT `qty_onhand`,`qty_onsold`,`qty_onorder`,`qty_available`
    	      FROM `tb_product` where `pro_id`= ".$db->quote($id);
    	return $db->fetchRow($sql);
    }
    public function getSettingById($id){
    	$sql = "SELECT CODE,key_name,key_value FROM tb_setting where code = ".$id;
    	return $this->getAdapter()->fetchRow($sql);
    }
    public function getMeasureById($id){
    	$db = $this->getAdapter();
    	$sql = "SELECT `qty_perunit` FROM tb_product WHERE pro_id= '$item_id' LIMIT 1 ";
    }
}
?>