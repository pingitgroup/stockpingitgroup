<?php

class setting_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
    //	$tr = Application_Form_FrmLanguages::getCurrentlanguage();
    }

    public function indexAction()
    {
    	$db = new setting_Model_DbTable_DbSetting();
    	$rs = $db->getAllSetting();
    	$columns=array("KEY_NAME","KEY_VALUE");
    	$link=array(
    			'module'=>'product','controller'=>'index','action'=>'product-detail',
    	);
    	 
    	$list = new Application_Form_Frmlist();    	
    	
    	$this->view->list=$list->getCheckList(1, $columns, $rs, array('item_name'=>$link,'Name'=>$link));
    	
    	
    	
    }
    public function siAction(){
    	$frm=new setting_Form_FrmStockInfo();
    	$this->view->form=$frm->FrmStockInfo();
    	Application_Model_Decorator::removeAllDecorator($frm);
    }
    public function docAction(){
    	$frm=new setting_Form_Frmdoc();
   $this->view->form=	$frm->FrmDocNumber();
   Application_Model_Decorator::removeAllDecorator($frm);
    }
 }
    


