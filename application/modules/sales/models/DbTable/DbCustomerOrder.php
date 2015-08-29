<?php

class sales_Model_DbTable_DbCustomerOrder extends Zend_Db_Table_Abstract
{
	/*
	 * for update then save 29-13
	 * */
	public function updateCustomerOrder($data)//when click update customer and have status paid (new version)
	{
		try{
			$db_global= new Application_Model_DbTable_DbGlobal();
			$db = $this->getAdapter();
			$db->beginTransaction();
		
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
			//for update order by id\
			$id_order_update=$data['id'];
			$info_order=array(
					"customer_id"    => $data['customer_id'],
					"LocationId"     => $data['LocationId'],
					//"order"          => $order_add,
					"sales_ref"      => $data['sales_ref'],
					"date_order"     => $data['order_date'],
					"status"         => $data['status'],
					//	"payment_method" => $data['payment_name'],
					//	"currency_id"    => $data['currency'],
					"remark"         => $data['remark'],
					"user_mod"       => $GetUserId,
					"timestamp"      => new Zend_Date(),
					"net_total"      => $data['net_total'],
					//"discount_type"	 => $data['discount_types'],
					"discount_value" => $data['dis_value'],
					"discount_real"  => $data["global_disc"],
					"paid"			 => $data['paid'],
					"all_total"      => $data['all_total'],
					"balance"        => $data['remain']
				
			);
			//update info of order
			$db_global->updateRecord($info_order,$id_order_update,"order_id","tb_sales_order");
			unset($info_order);
// 			$sql_item="SELECT iv.ProdId, iv.QuantityOnHand, iv.QuantityAvailable, sum(so.qty_order) AS qtysold ,s.LocationId 
// 			FROM tb_sales_order AS s,tb_sales_order_item AS so
// 			, tb_inventorytotal AS iv WHERE iv.ProdId = so.pro_id AND so.order_id=s.order_id AND so.order_id =$id_order_update GROUP BY so.pro_id";
			$sql = "SELECT 
						p.`pro_id`,
						p.`qty_available`,
						p.`qty_onhand`,
						p.`qty_onsold`,
						SUM(soi.`qty_order`) AS qty_sold_order,
						so.`LocationId`
			
						
					FROM
					  tb_product AS p,
					  tb_sales_order AS so,
					  tb_sales_order_item AS soi 
					WHERE 
						p.`pro_id`=soi.`pro_id`
						AND soi.`order_id`=so.`order_id`
						AND soi.`order_id`= $id_order_update
					GROUP BY soi.`pro_id`";
			$rows_sold=$db_global->getGlobalDb($sql);
			if($rows_sold){
				foreach ($rows_sold as $row_sold){
					//just add to stock inventory tmp then withdrawal
					if($data["oldStatus"]==5){
						$update_product = array(
							
							"qty_onhand" 	=>	$row_sold["qty_onhand"]+$row_sold[" qty_sold_order"],
							"qty_available"	=> $row_sold["qty_available"]+$row_sold["qty_sold_order"],
							"last_mod_date"	=> new Zend_Date()
						
						);
						$db_global->updateRecord($update_product,$row_sold["pro_id"],"pro_id","tb_product");
					}else {
						$update_pro = array(
							
							"qty_onsold" 	=> $row_sold["qty_onsold"]-$row_sold["qty_sold_order"],
							"qty_available"	=> $row_sold["qty_available"]+$row_sold["qty_sold_order"],
							"last_mod_date"	=> new Zend_Date()
						);
						$db_global->updateRecord($update_pro,$row_sold["pro_id"],"pro_id","tb_inventorytotal");
					}
					
					//update total stock
					
					
					//$row_get = $db_global->porductLocationExist($row_sold["ProdId"],$row_sold["LocationId"]);//old 
					$row_get = $db_global->porductLocationExist($row_sold["ProdId"],$data["old_location"]);
					if($row_get){
						if($data["oldStatus"]==5){
							$update_prolo= array(
								"qty"			=> $row_get["qty"]+$row_sold["qty_sold_order"],
								"last_usermod" 	=> $GetUserId,
								"last_mod_date" => new Zend_Date()
							);
						}else{
							$qty_on_location = array(
									"qty"			=> $row_get["qty"]+$row_sold["qty_sold_order"],
									"qty_onsold"	=> $row_get["qty"],
									"last_usermod" 	=> $GetUserId,
									"last_mod_date" => new Zend_Date()
							);
						}
						$qty_on_location = array(
								"qty"			=> $row_get["qty"]+$row_sold["qty_sold_order"],
								"last_usermod" 	=> $GetUserId,
								"last_mod_date" => new Zend_Date()
						);
						//update total stock
						$db_global->updateRecord($qty_on_location,$row_get["ProLocationID"],"ProLocationID","tb_prolocation");
					}
				}
				
			}
			unset($qty_on_order);
			//delete old sale order
			unset($rows_sold);
			$sql= "DELETE FROM tb_sales_order_item WHERE order_id IN ($id_order_update)";
			$db_global->deleteRecords($sql);
		
			$ids=explode(',',$data['identity']);
			foreach ($ids as $i)
			{
				$data_item[$i]= array(
						'order_id'	  => $id_order_update,
						'pro_id'	  => $data['item_id_'.$i],
						'qty_order'	  => $data['qty'.$i],
						'price'		  => $data['price'.$i],
						'total_befor' => $data['total'.$i],
						//'disc_type'	  => $data['dis-type-'.$i],
						//'disc_value'  => $data['dis-value'.$i],
						'sub_total'	  => $data['total'.$i]
				);
				$db->insert("tb_sales_order_item", $data_item[$i]);
				unset($data_item[$i]);
				$locationid=$data['LocationId'];
				$itemId=$data['item_id_'.$i];
				$qtyrecord=$data['qty'.$i];//qty on 1 record
					
				$rows=$db_global -> productLocationInventory($itemId, $locationid);//to check product location
				if($rows)
				{
					$qtyold       = $rows['qty'];
					$getrecord_id = $rows["ProLocationID"];
					$itemOnHand = array(
							'QuantityOnHand'   => $rows["QuantityOnHand"]-$qtyrecord,
							'QuantityAvailable'=> $rows["QuantityAvailable"]-$qtyrecord
					);
					//update total stock
					$db_global->updateRecord($itemOnHand,$itemId,"ProdId","tb_inventorytotal");
					unset($itemOnHand);
					//update stock dork
					//$newqty       = $rows['qty']-$qtyrecord;
					$updatedata=array(
							'qty' => $rows['qty']-$qtyrecord
					);
					//update stock product location
					$db_global->updateRecord($updatedata,$getrecord_id,"ProLocationID","tb_prolocation");
					unset($updatedata);
					//update stock record
				}
				else
				{
					//insert stock ;
					$rows_pro_exit= $db_global->productLocation($itemId, $locationid);
					if($rows_pro_exit){
						$updatedata=array(
								'qty' => $rows_pro_exit['qty']+$qtyrecord
						);
						//update stock product location
						$itemid=$db_global->updateRecord($updatedata,$rows_pro_exit['ProLocationID'], "ProLocationID", "tb_prolocation");
						unset($updatedata);
					}
					else{
						$insertdata=array(
								'pro_id'       => $itemId,
								'LocationId'   => $locationid,
								'last_usermod' => $GetUserId,
								'qty'          => -$qtyrecord,
								'last_mod_date'=>new Zend_Date()
						);
						//update stock product location
						$db->insert("tb_prolocation", $insertdata);
						unset($insertdata);
					}
				
					$rowitem=$db_global->InventoryExist($itemId);//to check product location
					if($rowitem)
					{
						$itemOnHand   = array(
								'QuantityOnHand'=>$rowitem["QuantityOnHand"]-$qtyrecord,
								'QuantityAvailable'=>$rowitem["QuantityAvailable"]-$qtyrecord,
				
						);
						//update total stock
						$itemid=$db_global->updateRecord($itemOnHand,$itemId,"ProdId","tb_inventorytotal");
						unset($itemOnHand);
					}
					else
					{
						$dataInventory= array(
								'ProdId'            => $itemId,
								'QuantityOnHand'    => -$data['qty'.$i],
								'QuantityAvailable' => -$data['qty'.$i],
								'Timestamp'         => new Zend_date()
						);
						$db->insert("tb_inventorytotal", $dataInventory);
						unset($dataInventory);
						//update stock product location
					}
				}
				
			}
			$db->commit();
		}catch (Exception $e){
			$db->rollBack();
			
		}
	}
	
	public function cancelCustomerOrder($data){
		try{
				$db_global= new Application_Model_DbTable_DbGlobal();
				$db = $this->getAdapter();
				$db->beginTransaction();
				
				$session_user=new Zend_Session_Namespace('auth');
				$GetUserId= $session_user->user_id;
				
				$id_order_update=$data['id'];
				
				$sql_item="SELECT iv.ProdId, iv.QuantityOnHand, iv.QuantityAvailable, sum(so.qty_order) AS qtysold,
				 so.price, so.total_befor, so.sub_total, s.LocationId
				FROM tb_sales_order AS s,tb_sales_order_item AS so
				, tb_inventorytotal AS iv WHERE iv.ProdId = so.pro_id AND so.order_id=s.order_id AND so.order_id =$id_order_update GROUP BY so.pro_id";
				$rows_sold=$db_global->getGlobalDb($sql_item);
				if($rows_sold){
					foreach ($rows_sold as $row_sold){
						//just add to stock inventory tmp then withdrawal
						$qty_on_order = array(
								"QuantityOnHand"	=> $row_sold["QuantityOnHand"]+$row_sold["qtysold"],
								"QuantityAvailable"	=> $row_sold["QuantityAvailable"]+$row_sold["qtysold"],
								"Timestamp" 		=> new Zend_Date()
						);
						//update total stock
						$db_global->updateRecord($qty_on_order,$row_sold["ProdId"],"ProdId","tb_inventorytotal");
				
						$row_get = $db_global->porductLocationExist($row_sold["ProdId"],$row_sold["LocationId"]);
						if($row_get){
							$qty_on_location = array(
									"qty"			=> $row_get["qty"]+$row_sold["qtysold"],
									"last_usermod" 	=> $GetUserId,
									"last_mod_date" => new Zend_Date()
							);
							//update total stock
							$db_global->updateRecord($qty_on_location,$row_get["ProLocationID"],"ProLocationID","tb_prolocation");
						}
						
						///note history
						$data_history = array(
								'pro_id'	 => $row_sold["ProdId"],
								'type'		 => 2,
								'order'		 => $id_order_update,//$data['order']
								'customer_id'=> $data['customer_id'],
								'date'		 => new Zend_Date(),
								'status'	 => 6,
								'order_total'=> $row_sold["qtysold"],
								'qty'		 => $row_sold['qtysold'],
								'unit_price' => $row_sold['price'],
								'sub_total'  => $row_sold['sub_total'],
						);
						$db->insert("tb_order_history", $data_history);
					}
						
				}
		// 		    $sql_sales= "DELETE FROM tb_sales_order WHERE order_id IN ($id_order_update)";
		// 		    $db_global->deleteRecords($sql_sales);
					$update =array("status"=>6);
					$db_global->updateRecord($update, $id_order_update,"order_id","tb_sales_order");
		// 			$sql= "DELETE FROM tb_sales_order_item WHERE order_id IN ($id_order_update)";
		// 			$db_global->deleteRecords($sql);
		$db->commit();
		}catch(Exception $e){
			$db->rollBack();
		}
				
	}
	

}