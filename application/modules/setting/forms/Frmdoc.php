<?php 
class setting_Form_Frmdoc extends Zend_Form
{
	public function init()
	{
		
	}
	public function FrmDocNumber($_data=NULL){
	
		$prefix=new Zend_Form_Element_Text("prefix");
		//$this->addElement(prefix);
		$next_number=new Zend_Form_Element_Text("next_number");
		//->addElement(next_number);
		$suffix=new Zend_Form_Element_Text("suffix");
		//->addElement($suffix);
		$preview=new Zend_Form_Element_Text("preview");
		//$this->addElement($preview);
		$this->addElements(array($prefix,$next_number,$suffix,$preview));
	return $this;
	}	
}