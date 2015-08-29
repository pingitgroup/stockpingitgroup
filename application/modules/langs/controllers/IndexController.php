<?php

class Langs_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    	defined('BASE_URL')	|| define('BASE_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
    }

    public function indexAction()
    {
        // action body       	
    	$session_language=new Zend_Session_Namespace('language');
    	$lang = $this->getRequest()->getParam('ln');
    	$session_language->unlock();
    	$session_language->language = $lang;
    	$session_language->lock();
    	$this->_redirect($_SERVER['HTTP_REFERER']);
    	//$this->_response->setRedirect( $_SERVER['HTTP_REFERER'])->sendResponse();
    	//exit();
    }


}

