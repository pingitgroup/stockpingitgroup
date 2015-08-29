<?php 
	class setting_Form_FrmStockInfo extends Zend_Form{
	public function FrmStockInfo($_data=NULL){
		
		
		//$detail = new Zend_Form_Element_Textarea('detail');
		//$detail->setAttribs(array('class'=>'validate[required]','placeholder'=>'
		//		enter '));
		//$this->addElement($detail);
		
		$logo= new Zend_Form_Element_File('file');
		//$this->addElement($logo);
		
		$stock_name=new Zend_Form_Element_Text("stock_name");
		$stock_name->setAttribs(array('class'=>'validate[required]','placeholder'=> 'Inventory Stock Name'));
		//$this->addElement($stock_name);
		
		$address=new Zend_Form_Element_Text("address");
		$address->setAttribs(array('class'=>'validate[required]','placeholder'=> 'Address'));
		//$this->addElement($address);
		
		//$address2=new Zend_Form_Element_Text("address2");
		//$this->addElement($address2);
		
		$city=new Zend_Form_Element_Select("city");
		//$this->addElement($city);
		
		$counntry=new Zend_Form_Element_Select("country");
		//$this->addElement($counntry);
		
		$phone=new Zend_Form_Element_Text("phone");
		//->addElement($phone);
		
		$fax=new Zend_Form_Element_Text("fax");
//	$this->addElement($fax);
		
		$email=new Zend_Form_Element_Text("email");
		$email->setAttribs(array('class'=>'validate[required]','placeholder'=> 'email'));
//	$this->addElement($email);
		
		$website=new Zend_Form_Element_Text("website");
		$website->setAttribs(array('class'=>'validate[required]','placeholder'=> 'website'));
//	$this->addElement($website);
		
		//$IS_info=new Zend_Form_Element_Text("SI_info");
		//$IS_info->setAttribs(array('class'=>'validate[required]','placeholder'=> 'Inventory Stock Info'));
//	$this->addElement($IS_info);
		$this->addElements(array($logo,$stock_name,$address,$city,$counntry,$phone,$fax,$email,$website));
		return $this;
		}
	}
?>