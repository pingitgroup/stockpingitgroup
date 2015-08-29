<?php

class sales_Model_DbTable_DbOrder extends Zend_Db_Table_Abstract
{
	/////get order info to view for update
	public function getOrderItemInfoByID($id){
		$db = $this->getAdapter();
		$sql = "SELECT o.order_id,o.order,o.date_order,o.status,c.cust_name,l.Name,
				c.contact_name,c.phone,c.add_name,c.add_remark,o.remark,o.net_total,
				o.discount_type,o.discount_value,o.paid,sg.name,o.all_total,
				o.paid,o.balance FROM tb_sales_order AS o 
				INNER JOIN tb_customer AS c ON c.customer_id=o.customer_id
                LEFT JOIN tb_sale_agent AS sg ON sg.agent_id= o.sales_ref
				INNER JOIN tb_sublocation AS l ON o.LocationId = l.LocationId
		        WHERE o.`order_id`= $id LIMIT 1";
		$row = $db->fetchRow($sql);
		return $row;
	}
	//INNER JOIN tb_paymentmethod AS pay ON pay.payment_typeId = o.payment_method//not use cos update not payment method
	///modify from sale_ref to join tb_sale_agen and get agent Name
	
	//get update order but not well
	public function getSalesOderID($id){
		$db = $this->getAdapter();
		$sql = "SELECT si.order_id,CONCAT(p.item_name,' (',p.item_code,' )') AS item_name ,p.pro_id,si.qty_order,si.qty_order,si.price,si.total_befor,si.disc_type,
		si.disc_value,si.disc_amount,si.sub_total,si.is_free,s.discount_value
		FROM tb_sales_order_item AS si,tb_product AS p ,tb_sales_order AS s
		 WHERE p.pro_id = si.pro_id AND si.order_id = s.order_id AND  si.order_id=".$id;
		$row = $db->fetchAll($sql);
		return $row;
	}	
	//get customer info 28-8-13 
	public function getCustomerInfo($post){
		$db=$this->getAdapter();
		$sql ="SELECT  add_name AS address ,  contact_name, phone
				FROM tb_customer
				WHERE customer_id =".$post['customer_id']." LIMIT 1";
		$row = $db->fetchRow($sql);
		return $row;		
	}
	// for get current price 28-13
	public function getcurrentPrice($post){
		$db=$this->getAdapter();
		$sql="SELECT price
		FROM tb_product WHERE pro_id = ".$post['item_id']." LIMIT 1";
		$row=$db->fetchRow($sql);
		return $row;
	}
}