<?php
class sales_returnController extends Zend_Controller_Action
{	
    public function init()
    {
        /* Initialize action controller here */
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
    	$tr = Application_Form_FrmLanguages::getCurrentlanguage();
    }
    protected function GetuserInfoAction(){
    	$user_info = new Application_Model_DbTable_DbGetUserInfo();
    	$result = $user_info->getUserInfo();
    	return $result;
    }
	public function indexAction()
	{
		$formFilter = new sales_Form_FrmSearch();
		$frmFilter=$formFilter->FrmSearchFromCustomer();
		$this->view->formFilter = $frmFilter;
		Application_Model_Decorator::removeAllDecorator($frmFilter);
		
		$list = new Application_Form_Frmlist();
		$db = new Application_Model_DbTable_DbGlobal();
		$sql = "SELECT r.return_id, r.return_no,r.invoice_no, r.date_return, c.cust_name, r.all_total
						FROM tb_return_customer_in AS r INNER JOIN tb_customer AS c ON c.customer_id=r.customer_id ";
		
		$user = $this->GetuserInfoAction();
    	$str_condition = " AND r.location_id" ; 
    	$sql .= $db->getAccessPermission($user["level"], $str_condition, $user["location_id"]);
		
		if($this->getRequest()->isPost()){
				$post = $this->getRequest()->getPost();
				//echo $post["order"];
				if($post['order'] !=''){
						$sql .= " AND r.return_no LIKE '%".trim($post['order'])."%'";
				}
				if($post['customer_id'] !='' AND  trim($post['customer_id']) !=0){
					$sql .= " AND c.customer_id =".trim($post['customer_id']);
				}
				$start_date = trim($post['search_start_date']);
				$end_date = trim($post['search_end_date']);
				
				if($start_date != "" && $end_date != "" && strtotime($end_date) >= strtotime($start_date)) {
					$sql .= " AND r.date_return BETWEEN '$start_date' AND '$end_date'";
				}
		}
		$sql.=" ORDER BY r.return_id DESC";
		$rows=$db->getGlobalDb($sql);
		$columns=array("RETURN_NO","INVOICE_NO","RETURN_DATE_CAP", "CUSTOMER_CAP",
				 "TOTAL_CAP_DOLLAR");
		$link=array(
				'module'=>'sales','controller'=>'return','action'=>'detail-return-item',
		);
		$urlEdit = BASE_URL . "/sales/return/detail-return-item";
		$this->view->list=$list->getCheckList(1, $columns, $rows, array('return_no'=>$link),$urlEdit);
	}	
	public function addReturnItemAction(){
		if($this->getRequest()->isPost()){
			try{//for get return item form customer
			    $data = $this->getRequest()->getPost();
				$return = new sales_Model_DbTable_DbReturnItem();
				$return ->returnItem($data);
				if(isset($data["Save"])){
					$this->_redirect("/sales/return");
				}else{
					Application_Form_FrmMessage::message("Data has been insert!");
				}
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}
		$get_form = new Application_Form_FrmReturnItem();
		$frm_return = $get_form->CustomerReturnItem(null);
		Application_Model_Decorator::removeAllDecorator($frm_return);
		$this->view->form_return = $frm_return;
		
		$formAddProdcut = new Application_Form_FrmAction(null);
		$FrmAdd = $formAddProdcut->AllAction(null);
		Application_Model_Decorator::removeAllDecorator($FrmAdd);
		$this->view->control = $FrmAdd;
		
		///view on select location form table
		$getOption = new Application_Model_GlobalClass();
		$locationRows = $getOption->getLocationOption();
		$this->view->locationOption = $locationRows;
		///view on select location form table
		$itemRows = $getOption->getProductOption();
		$this->view->productOption = $itemRows;
		
		
		
		//for add product;
		$formpopup = new Application_Form_FrmPopup(null);
		$formproduct = $formpopup->popuProduct(null);
		Application_Model_Decorator::removeAllDecorator($formproduct);
		$this->view->form = $formproduct;
		
		//for add CUSTOMER
		
		$formcustomer = $formpopup->popupCustomer(null);
		Application_Model_Decorator::removeAllDecorator($formcustomer);
		$this->view->form_customer = $formcustomer;
		
		//for add location
		$formAdd = $formpopup->popuLocation(null);
		Application_Model_Decorator::removeAllDecorator($formAdd);
		$this->view->form_addstock = $formAdd;
	}
	public function returnOutAction()
	{
		$formFilter = new sales_Form_FrmSearch();
		$frmFilter=$formFilter->FrmSearchFromCustomer();
		$this->view->formFilter = $frmFilter;
		Application_Model_Decorator::removeAllDecorator($frmFilter);
	
		$list = new Application_Form_Frmlist();
		$db = new Application_Model_DbTable_DbGlobal();
		$sql = "SELECT ro.returnout_id, ro.returnout_no, ri.return_no, ro.date_return, ro.all_total
		FROM tb_return_customer_in AS ri,tb_return_customer_out AS ro 
		WHERE ri.return_id = ro.returnin_id ";
	
		$user = $this->GetuserInfoAction();
		$str_condition = " AND ro.location_id" ;
		$sql .= $db->getAccessPermission($user["level"], $str_condition, $user["location_id"]);
	
		if($this->getRequest()->isPost()){
			$post = $this->getRequest()->getPost();
			if($post['order'] !=''){
				$sql .= " AND ro.returnout_no LIKE '%".trim($post['order'])."%'";
			}
			if($post['return_in']!=''){
				$sql .= " AND ri.return_no LIKE '%".trim($post['return_in'])."%'";
			}
			$start_date = trim($post['search_start_date']);
			$end_date = trim($post['search_end_date']);
	
			if($start_date != "" && $end_date != "" && strtotime($end_date) >= strtotime($start_date)) {
				$sql .= " AND r.date_return BETWEEN '$start_date' AND '$end_date'";
			}
		}
		$sql.=" ORDER BY ro.returnout_id DESC";
		$rows=$db->getGlobalDb($sql);
		$columns=array("RETURN_OUT_CAP","RETURN_IN_CAP","RETURN_DATE_CAP",
				"TOTAL_CAP_DOLLAR");
		$link=array(
				'module'=>'sales','controller'=>'return','action'=>'detail-return-itemout',
		);
		$urlEdit = BASE_URL . "/sales/return/detail-return-itemout";
		$this->view->list=$list->getCheckList(1, $columns, $rows, array('returnout_no'=>$link),$urlEdit);
	}
	
	public function itemTocustomerAction(){ ///return item to customer
		if($this->getRequest()->isPost()){
			try{
				$data = $this->getRequest()->getPost();
				$return = new sales_Model_DbTable_DbReturnItem();
				$return ->returnItemToCustomer($data);///return item to customer out
				if(isset($data["Save"])){
					$this->_redirect("/sales/return");
				}else{
					Application_Form_FrmMessage::message("Data has been insert!");
				}
			}catch (Exception $e){
				echo $e->getMessage();
			}
		}
		$get_form = new Application_Form_FrmReturnItem();
		$frm_return = $get_form->ReturnItemToCustomer(null);
		Application_Model_Decorator::removeAllDecorator($frm_return);
		$this->view->form_return = $frm_return;
	
		$formAddProdcut = new Application_Form_FrmAction(null);
		$FrmAdd = $formAddProdcut->AllAction(null);
		Application_Model_Decorator::removeAllDecorator($FrmAdd);
		$this->view->control = $FrmAdd;
	
		///view on select location form table
		$getOption = new Application_Model_GlobalClass();
		$locationRows = $getOption->getLocationOption();
		$this->view->locationOption = $locationRows;
		///view on select location form table
		$itemRows = $getOption->getProductOption();
		$this->view->productOption = $itemRows;
	
	
	
// 		//for add product;
// 		$formpopup = new Application_Form_FrmPopup(null);
// 		$formproduct = $formpopup->popuProduct(null);
// 		Application_Model_Decorator::removeAllDecorator($formproduct);
// 		$this->view->form = $formproduct;
	
// 		//for add CUSTOMER
	
// 		$formcustomer = $formpopup->popupCustomer(null);
// 		Application_Model_Decorator::removeAllDecorator($formcustomer);
// 		$this->view->form_customer = $formcustomer;
	
// 		//for add location
// 		$formAdd = $formpopup->popuLocation(null);
// 		Application_Model_Decorator::removeAllDecorator($formAdd);
// 		$this->view->form_addstock = $formAdd;
	}
	
	public function updateReturnItemAction(){
		$session_stock = new Zend_Session_Namespace('stock');
		$id = ($this->getRequest()->getParam('id'))? $this->getRequest()->getParam('id'): '0';
		if($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost();
			if($data['Save']){
				$update_return = new purchase_Model_DbTable_DbReturnItem();
				$update_return ->updateReturnItem($data);
			}
		}
		$purchase = new purchase_Model_DbTable_DbPurchaseOrder();
		$rows = $purchase->purchaseInfo($id);
		$db = new Application_Model_DbTable_DbGlobal();
		
		$returnModel = new purchase_Model_DbTable_DbSQLReturnItem();
		$row_info= $returnModel->returnInfo($id);
		
		$get_form = new Application_Form_FrmReturnItem();
		$session_stock = new Zend_Session_Namespace('stock');
		$frm_return = $get_form->returnItemForm($row_info);
		Application_Model_Decorator::removeAllDecorator($frm_return);
		$this->view->form_return = $frm_return;
		
		//get qty of return item
		$getReturnItem = $returnModel->getReturnItem($id);
		$this->view->returnItemDetail = $getReturnItem;
		
		//get return item		
		
		$getOption = new Application_Model_GlobalClass();
		$locationRows = $getOption->getLocationOption();
		$this->view->locationOption = $locationRows;
		
		$itemRows = $getOption->getProductOption();
		$this->view->productOption = $itemRows;
		
		$formControl = new Application_Form_FrmAction(null);
		$formViewControl = $formControl->AllAction(null);
		Application_Model_Decorator::removeAllDecorator($formViewControl);
		$this->view->control = $formViewControl;
				
		//for add product;
		$formpopup = new Application_Form_FrmPopup(null);
		$formproduct = $formpopup->popuProduct(null);
		Application_Model_Decorator::removeAllDecorator($formproduct);
		$this->view->form_add_product = $formproduct;
		
		//for add vendor
		$formvendor= $formpopup->popupVendor(null);
		Application_Model_Decorator::removeAllDecorator($formvendor);
		$this->view->form_vendor = $formvendor;
		
		//for add location
		$formAdd = $formpopup->popuLocation(null);
		Application_Model_Decorator::removeAllDecorator($formAdd);
		$this->view->form_addstock = $formAdd;
		
		//for link advane
		$this->view->getorder_id = $id;
	}
	public function detailReturnItemAction() {
		if($this->getRequest()->getParam('id')) {
			$id = ($this->getRequest()->getParam('id'))? $this->getRequest()->getParam('id'): '0';
			$returnModel = new sales_Model_DbTable_DbReturnQuery();
			$customer_info = $returnModel->getCustomerInfoIn($id);
			if(!empty($customer_info)){
				$this->view->return_info = $customer_info;
			}
			else{
				$this->_redirect("/sales/return");
			}
			//get qty of itme 
 			$getReturnItem = $returnModel->getReturnInItem($id);
			$this->view->returnItemDetail = $getReturnItem;
		}
	}
	public function detailReturnItemoutAction() {//detail return customer to stock out
		if($this->getRequest()->getParam('id')) {
			$id = ($this->getRequest()->getParam('id'))? $this->getRequest()->getParam('id'): '0';
	
			$returnModel = new sales_Model_DbTable_DbReturnQuery();
			$reference_info = $returnModel->getReferenceInfo($id);
			if(!empty($reference_info)){
				$this->view->refer_info = $reference_info;
			}
			else{
				$this->_redirect("/sales/return/return-out");
			}
			//get qty of itme
			$getReturnItem = $returnModel->getReturnItemOut($id);
			$this->view->returnItemDetail = $getReturnItem;
		}
	}
	
	
}
