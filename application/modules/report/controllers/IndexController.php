<?php
class report_indexController extends Zend_Controller_Action
{
	
    public function init()
    {
        /* Initialize action controller here */
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
    	$tr = Application_Form_FrmLanguages::getCurrentlanguage();
    }
    protected function GetuserInfo(){
    	$user_info = new Application_Model_DbTable_DbGetUserInfo();
    	$result = $user_info->getUserInfo();
    	return $result;
    }
	public function indexAction()
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
			
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getSaleItem = $query->getSalesItem($data);
				//print_r($getSaleItem);exit();
				$this->view->getsales_item = $getSaleItem;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$brand=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $brand;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$dbuser = new report_Model_DbQuery();
		$brand=$dbuser->getBrandByUser();
		if(!empty($brand)){
			$this->view->branch = $brand;
		}
		
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_salse = $form_search;
		
	}
	public function rptSummaryAction()
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
				
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
				
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getSaleItem = $query->getProductSummary($data);
				$this->view->getsales_item = $getSaleItem;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$branch=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $branch;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$user = $this->GetuserInfo();
		if($user["level"]!=1 AND $user["level"]!=2){
			$this->_redirect("/default/index/home");
				
		}
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_salse = $form_search;
	
	}
	
	public function getItemAction(){
		if($this->getRequest()->isPost()){
			$post=$this->getRequest()->getPost();
			$location_id = $post["location_id"];
			$brand = $post["branch_id"];
			$cate_id = $post["category_id"];
			$sql = " SELECT 
				  p.pro_id,
				  p.`item_code`,
				  p.`item_name`
				FROM
				  tb_product AS p , tb_prolocation AS pl WHERE pl.pro_id = p.pro_id  AND pl.`LocationId` = $location_id
				 ";
			
			if($post["branch_id"]!="" OR $post["branch_id"]!=0){
				$sql.=" AND p.`brand_id` = $brand";
			}
			if($post["category_id"]!="" OR $post["category_id"]!=0){
				$sql.=" AND p.`cate_id`= $cate_id";
			}
			$sql.="ORDER BY p.`item_name`";
			$db = new Application_Model_DbTable_DbGlobal();
			$row=$db->getGlobalDb($sql);
			echo Zend_Json::encode($row);
			exit();
		}
	}
	public function getItemByBrandAction(){
		if($this->getRequest()->isPost()){
			$post=$this->getRequest()->getPost();
			$location_id = $post["location_id"];
			$brand = $post["branch_id"];
// 			$sql = " SELECT
// 					p.pro_id,
// 					p.`item_code`,
// 					p.`item_name`
// 					FROM
// 					tb_product AS p WHERE p.`brand_id` = $brand ";
			
			$sql = " SELECT
						p.pro_id,
						p.`item_code`,
						p.`item_name`
					 FROM
						tb_product AS p";
			if($post["location_id"]!="" OR $post["location_id"]!=0){
				$sql.=" INNER JOIN location ON location.pro_id = p.`pro_id` AND location.LocationId=$location_id ";
			}
			$sql.="  WHERE p.`brand_id` = $brand  ORDER BY p.`item_name`";
			
			$db = new Application_Model_DbTable_DbGlobal();
			$row=$db->getGlobalDb($sql);
			echo Zend_Json::encode($row);
			exit();
		}
	}
	
	public function getItemFilterAction(){
		if($this->getRequest()->isPost()){
			$post=$this->getRequest()->getPost();
			$location_id = $post["location_id"];
			$brand = $post["branch_id"];
			$cate_id = $post["category_id"];
				
			$sql = " SELECT
			p.pro_id,
			p.`item_code`,
			p.`item_name`
			FROM
				tb_product AS p";
			if($post["location_id"]!="" OR $post["location_id"]!=0){
				$sql.=" INNER JOIN location ON location.pro_id = p.`pro_id` AND location.LocationId=$location_id ";
			}
			if($post["branch_id"]!="" OR $post["branch_id"]!=0){
				$sql.=" AND p.`brand_id` = $brand";
			}
			if($post["category_id"]!="" OR $post["category_id"]!=0){
				$sql.=" AND p.`cate_id` = $cate_id";
			}
			$sql.=" WHERE p.`item_name`!='' ORDER BY p.`item_name`";
				
			$db = new Application_Model_DbTable_DbGlobal();
			$row=$db->getGlobalDb($sql);
			echo Zend_Json::encode($row);
			exit();
		}
	}
	public function rptProductDetailAction()
	{
			if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			if($data["report_type"]==4){
				$this-> rptTransferAction();
			}elseif ($data["report_type"]==2){
				$this->rptpurchaseAction();
			}elseif ($data["report_type"]==3){
				$this->indexAction();
			}elseif ($data["report_type"]==5){
				$this->rptProductQtyAction();
			}elseif ($data["report_type"]==6){
				if($this->getRequest()->getPost("report_num")){
					$one = $this->getRequest()->getPost("report_num");
					foreach ($one as $report){
						if($report == 1){
							$this->rptpurchaseAction();							
						}elseif ($report==2){
							$this->indexAction();
						}elseif ($report==3){
							$this-> rptTransferAction();
						}else {
							$this->rptProductQtyAction();
						}
					}
				}
				
			}else{
				$this->rptpurchaseAction();
				$this->indexAction();
				$this-> rptTransferAction();
				$this->rptProductQtyAction();
			}
		}
		$user = $this->GetuserInfo();
		if($user["level"]!=1 AND $user["level"]!=2){
			$this->_redirect("/default/index/home");
		}
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->productDetailReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_salse = $form_search;
	
	}

	
	public function rptProductSummaryAction()
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
				
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
				
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getProduct_summary = $query->getProductSummary($data);
				$this->view->get_product_summary = $getProduct_summary;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$branch=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $branch;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$user = $this->GetuserInfo();
		if($user["level"]!=1 AND $user["level"]!=2){
			$this->_redirect("/default/index/home");
	
		}
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->productDetailReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_salse = $form_search;
	
	}
	
	public function rptpurchaseAction()//purchase report
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
	
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
	
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getPurchaseItem = $query->getPurchaseItem($data);
				$this->view->get_purchase_item = $getPurchaseItem;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$branch=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $branch;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$user = $this->GetuserInfo();
		if($user["level"]!=1 AND $user["level"]!=2){
			$this->_redirect("/default/index/home");
			
		}
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_salse = $form_search;
	
	}
	
	public function rptTransferAction()
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
				
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
				
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getTransferItem = $query->getQtyTransfer($data);
				$this->view->get_transfer_item = $getTransferItem;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$brand=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $brand;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$dbuser = new report_Model_DbQuery();
		$brand=$dbuser->getBrandByUser();
		if(!empty($brand)){
			$this->view->branch = $brand;
		}
	
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_transfer = $form_search;
	
	}
	public function rptProductQtyAction()
	{
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
	

				$query = new report_Model_DbQuery();
				$geProducttQty = $query->geProducttQty($data);
				$this->view->get_product_qty = $geProducttQty;
				if(!empty($data["LocationId"])){
					$brand=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $brand;
				}
		}
		$dbuser = new report_Model_DbQuery();
		$brand=$dbuser->getBrandByUser();
		if(!empty($brand)){
			$this->view->branch = $brand;
		}
	
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_product = $form_search;
	
	}
	
	//for view-report /27/8/13
	public function veiwReportAction(){
		$this->_helper->layout->disableLayout();
		
		
	}	
	public function printReportAction(){
		$this->_helper->layout->disableLayout();
	}
	public  function monthAction(){
		
	}
	
	
	public function sochartbydateAction(){
		$data = $this->getRequest()->getPost();
		
		
		if(strtotime($end_date)+86400 > strtotime($start_date)) {
			$_db = new report_Model_DbQuery();
			$_rows=$_db->getTopTenProductSOByDate();
			$_arr="";
			$this->view->getsales_item = $getSaleItem;
			$this->view->start_date = $start_date;
			$this->view->end_date = $end_date;
			if(!empty($data["LocationId"])){
				$brand=$query->getLocationName($data["LocationId"]);
				$this->view-> branch = $brand;
			}
		}
		else {
			Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
		}
		
		if(!empty($brand)){
			$this->view->branch = $brand;
		}
		
		
		
		
// 		$_db = new report_Model_DbQuery();
// 		$_rows=$_db->getTopTenProductSOByDate();
// 		$_arr="";
// 		foreach ($_rows As $i =>$row){
// 			if($i==count($_rows)-1){
// 				$_arr.= "['".$row["item_name"]."',".$row["qty"]."]";
// 			}
// 			else{
// 				$_arr.= "['".$row["item_name"]."',".$row["qty"]."],";
// 			}
// 		}
		$this->view->top_product = $_arr;
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_transfer = $form_search;
	}
	
	public function sochartAction(){
	$_db = new report_Model_DbQuery();
	$_rows=$_db->getTopTenProductSO();$_arr="";
	foreach ($_rows As $i =>$row){
		if($i==count($_rows)-1){
			$_arr.= "['".$row["item_name"]."',".$row["qty"]."]";
		}
		else{
			$_arr.= "['".$row["item_name"]."',".$row["qty"]."],";
		}
	}
	$this->view->top_product = $_arr;
	
	}
	public function pochartAction(){
		$_db = new report_Model_DbQuery();
		$_rows=$_db->getTopTenProductPO();
		$_arr="";
		foreach ($_rows As $i =>$row){
			if($i==count($_rows)-1){
				//$_arr.= $row["item_name"].";".$row["qty"];
				$_arr.= "['".$row["item_name"]."',".$row["qty"]."]";
			//	['Work',     11],
			}
			else{
				$_arr.= "['".$row["item_name"]."',".$row["qty"]."],";
				//$_arr.= $row["item_name"].";".$row["qty"].";";
					
			}
		}
		$this->view->top_product = $_arr;
	//echo $_arr;
	
	}
	public function pochartdateAction(){
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
				
			$start_date = $data["start_date"];
			$end_date = $data["end_date"];
				
			if(strtotime($end_date)+86400 > strtotime($start_date)) {
				$query = new report_Model_DbQuery();
				//$vendor_sql .= " AND p.date_order BETWEEN '$start_date' AND '$end_date'";
				$getSaleItem = $query->getQtyTransfer($data);
				$this->view->getsales_item = $getSaleItem;
				$this->view->start_date = $start_date;
				$this->view->end_date = $end_date;
				if(!empty($data["LocationId"])){
					$brand=$query->getLocationName($data["LocationId"]);
					$this->view-> branch = $brand;
				}
			}
			else {
				Application_Form_FrmMessage::message("End Date Must Greater Then Start Date");
			}
		}
		$dbuser = new report_Model_DbQuery();
		$brand=$dbuser->getBrandByUser();
		if(!empty($brand)){
			$this->view->branch = $brand;
		}
		$data = $this->getRequest()->getPost();		
		$_db = new report_Model_DbQuery();
		$_rows=$_db->getTopTenProductSO();$_arr="";
		foreach ($_rows As $i =>$row){
			if($i==count($_rows)-1){
				$_arr.= "['".$row["item_name"]."',".$row["qty"]."]";
			}
			else{
				$_arr.= "['".$row["item_name"]."',".$row["qty"]."],";
			}
		}
		$this->view->top_product = $_arr;
		$frm = new Application_Form_FrmReport();
		$form_search=$frm->salseReport();
		Application_Model_Decorator::removeAllDecorator($form_search);
		$this->view->form_transfer = $form_search;
	}
	
}