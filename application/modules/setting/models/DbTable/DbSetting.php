<?php

class Setting_Model_DbTable_DbSetting extends Zend_Db_Table_Abstract
{
	protected $_name = "tb_setting";
	public function getAllSetting(){
		$_db = $this->getAdapter();
		$_sql = "SELECT code,title,key_value FROM tb_setting ";
		return $_db->fetchAll($_sql);
	}
	
}