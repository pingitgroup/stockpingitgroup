<?php

class purchase_Model_DbTable_DbPurchaseVendor extends Zend_Db_Table_Abstract
{
	//use for add purchase order 29-13
	public function vendorPurchaseOrderPayment($data)
	{
		try{
			$db_global = new Application_Model_DbTable_DbGlobal();
			$db = $this->getAdapter();	
			$db->beginTransaction();
		
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
			
			
			
		//	print_r($data);
			$idrecord=$data['v_name'];
	// 		$datainfo=array(
					
	// 				"contact_name"=>$data['contact'],
	// 				"phone"       =>$data['txt_phone'],
	// 		);
	// 		//updage vendor info
	// 		$itemid=$db_global->updateRecord($datainfo,$idrecord,"vendor_id","tb_vendor");
			if($data['txt_order']==""){
				$date= new Zend_Date();
				$order_add="PO".$date->get('hh-mm-ss');
			}
			else{
				$order_add=$data['txt_order'];
					
			}
			$info_purchase_order=array(
					"vendor_id"      => $data['v_name'],
					"LocationId"     => $data["LocationId"],
					"order"          => $order_add,
					"date_order"     => $data['order_date'],
					"date_in"     	 => $data['date_in'],
					"status"         => $data['status'],
					//"payment_method" => $data['payment_name'],
					//"currency_id"    => $data['currency'],
					"remark"         => $data['remark'],
					"user_mod"       => $GetUserId,
					"timestamp"      => new Zend_Date(),
	// 				"net_total"      => $data['net_total'],
	// 				"discount_type"	 => $data['discount_type'],
	// 				"discount_value" => $data['discount_value'],
	// 				"discount_real"  => $data['discount_real'],
					"paid"           => $data['paid'],
					"all_total"      => $data['totalAmoun'],
					"balance"        => $data['remain']
			);
			//and info of purchase order
			
			$purchase_id = $db_global->addRecord($info_purchase_order,"tb_purchase_order");
		
			unset($info_purchase_order);
			
			
			
			// Insert invoice order
			if($data["status"]==5 OR $data["status"]==4){
			$data_recieved_purchase_order = array(
						
					"recieve_type" => 1,
					"order_id" 		=> $purchase_id,
					"order_no"		=>$order_add,
					"vendor_id"		=>$data['v_name'],
					"location_id" 	=>$data["LocationId"],
					"order_date"	=> new Zend_Date(),
					"date_recieve"	=> new Zend_Date(),
					"status"		=> $data['status'],
					"paid"			=> $data['paid'],
					"all_total"		=>$data['totalAmoun'],
					"balance"		=>$data['remain'],
					"user_recieve"	=>$GetUserId
			);
			$recieved_order = $db_global->addRecord($data_recieved_purchase_order, "tb_recieve_order");
			unset($data_recieved_purchase_order);
			}
			//end Insert invoice order
			
			unset($datainfo); unset($idrecord);
		
			$ids=explode(',',$data['identity']);
			//print_r($ids);exit();
			$locationid=$data['LocationId'];
			//print_r($locationid); exit();
			foreach ($ids as $i)
			{
					
				$itemId=$data['item_id_'.$i];
				
				$qtyrecord=$data['qty'.$i];//qty on 1 record
				//add history purchase order
				$data_history[$i] = array(
						'pro_id'	 	=> 	$data['item_id_'.$i],
						'type'		 	=> 	1,
						'order'		 	=> 	$purchase_id,
						'customer_id'	=> 	$data['v_name'],
						'date'		 	=> 	new Zend_Date(),
						'status'	 	=> 	$data['status'],
						'order_total'	=> 	$data['total'.$i],
						'qty'		 	=> 	$data['qty'.$i],
						'unit_price' 	=> 	$data['price'.$i],
						'sub_total'  	=> 	$data['remain'],
				);
				$order_history = $db_global->addRecord($data_history[$i],"tb_purchase_order_history");
				unset($data_history[$i]);
				
				//add purchase order item
				$data_item[$i]= array(
						'order_id'	  => 	$purchase_id,
						'pro_id'	  => 	$data['item_id_'.$i],
						'qty_order'	  => 	$data['qty'.$i],
						'price'		  => 	$data['price'.$i],
						'total_befor' => 	$data['total'.$i],
	// 					'disc_type'	  => $data['dis-type-'.$i],
	// 					'disc_value'  => $data['dis-value'.$i],
						'sub_total'	  => $data['total'.$i],
						'remark'	  => $data['remark_'.$i]
				);
				
				//echo $data_item ; 
				 $db->insert("tb_purchase_order_item", $data_item[$i]);
				//echo $idss ; exit();
				unset($data_item[$i]);
				//check stock product location
				
				
				
				
				// insert Invoice order item
				if($data["status"]==5 OR $data["status"]==4){
				$recieved_item[$i] = array(
						"recieve_id"	=> 		$recieved_order["recieve_id"],
						"pro_id"		=> 		$data['item_id_'.$i],
						"order_id"		=> 		$purchase_id,
						"qty_order"		=> 		$data['qty'.$i],
						"qty_recieve"	=> 		$data['qty'.$i],
						//"qty_remian"	=> ,
						"price"			=> 		$data['price'.$i],
						"total_before"	=> 		$data['total'.$i],
						"sub_total"		=> 		$data['total'.$i],
				
				);
				$db->insert("tb_recieve_order_item", $recieved_item[$i]);
				unset($recieved_item[$i]);
				}
				// End insert Invoice order item
				$rows=$db_global -> productLocationInventory($itemId, $locationid);
				if($rows)
				{
					
					$qtyold       = $rows['qty_onorder'];
					$getrecord_id = $rows["ProLocationID"];
					//$qty_onhand   = $rows["QuantityOnHand"]+$qtyrecord;
					if($data["status"]==4 OR $data["status"]==5){
						$itemOnOrder   = array(
								'qty_onhand'   => $rows["qty_onhand"]+$qtyrecord,
								'qty_available'=> $rows["qty_available"]+$qtyrecord
						);
					}else
					$itemOnOrder   = array(
							'qty_onorder'   => $rows["qty_onorder"]+$qtyrecord,
							
					);
					//update total stock
					$itemid=$db_global->updateRecord($itemOnOrder,$itemId,"pro_id","tb_product");
					//update stock dork
					//$newqty       = ;
					
					if($data["status"]==4 OR $data["status"]==5){
						$updatedata   = array(
								'qty'   => $rows["qty"]+$qtyrecord,
								
						);
					}else
						$updatedata=array(
								'qty_onorder' => $rows['qty_onorder']+$qtyrecord
						);
					//update stock product location
					$itemid=$db_global->updateRecord($updatedata,$getrecord_id,"ProLocationID","tb_prolocation");
					//add move hostory
				}else{
				
					//insert stock ;
					
					$row_prolo=$db_global->productLocation($itemId,$locationid);
					//print_r($row_prolo);exit();
					if($row_prolo)
					{
						if($data["status"]==4 OR $data["status"]==5){
							$updatedata=array(
									'qty' => $row_prolo['qty']+$qtyrecord
							);
						}else 
							$updatedata=array(
									'qty_onorder' => $row_prolo['qty_onorder']+$qtyrecord
							);
						//update stock product location
						$itemid=$db_global->updateRecord($updatedata,$row_prolo['ProLocationID'], "ProLocationID", "tb_prolocation");
					}else{
						if($data["status"]==4 OR $data["status"]==5){
							$insertdata=array(
									'pro_id'       => $data['item_id_'.$i],
									'LocationId'   => $locationid,
									'last_usermod' => $GetUserId,
									'qty'          => $qtyrecord,
									'last_mod_date'=>new Zend_Date()
							);
						}else 
						$insertdata=array(
								'pro_id'       => $data['item_id_'.$i],
								'LocationId'   => $locationid,
								'last_usermod' => $GetUserId,
								'qty_onorder'          => $qtyrecord,
								'last_mod_date'=>new Zend_Date()
						);
						//update stock product location
						$db->insert("tb_prolocation", $insertdata);
					}
					
					//add move hostory
					$rows=$db_global->InventoryExist($itemId);
					//print_r($rows);exit();
					if($rows)
					{
						//$qty_onhand   = $rowitem["QuantityOnHand"]+$data['qty'.$i];
						$itemOnOrder   = array(
								'qty_onorder'    	=> $rows["qty_onorder"]+$data['qty'.$i],
								//"QuantityAvailable" => $rows["QuantityAvailable"]+$data['qty'.$i],
								'pro_id'		    => $itemId
						);
						//update total stock
						 $db_global->updateRecord($itemOnOrder,$itemId,"pro_id","tb_product");
					}
					else
					{
						$dataInventory= array(
								'pro_id'            => $itemId,
								'qty_onorder'    	=> $data['qty'.$i],
								//'QuantityAvailable' => $data['qty'.$i],
								'last_mod_date'      => new Zend_date()
						);
						$db->insert("tb_product", $dataInventory);
						//add move hostory
					}
				}
			}
			
			$db->commit();
		}catch(Exception $e){
			$db->rollBack();
			$e->getMessage();
		}
	} 
	//for add purchase order then click save
	public function VendorOrder($data)
	{
		$db_global = new Application_Model_DbTable_DbGlobal();
		$db= $this->getAdapter(); 
	
		$session_user=new Zend_Session_Namespace('auth');
		$userName=$session_user->user_name;
		$GetUserId= $session_user->user_id;
	
		$idrecord=$data['v_name'];
		$datainfo=array(
				"contact_name"=> $data['contact'],
				"phone"       => $data['txt_phone']
		);
		//updage vendor info
		$itemid=$db_global->updateRecord($datainfo,$idrecord,"vendor_id","tb_vendor");
		if($data['txt_order']==""){
			$date= new Zend_Date();
			$order_add="PO".$date->get('hh-mm-ss');
		}
		else{
			$order_add=$data['txt_order'];
			
		}
		$info_purchase_order=array(
				"vendor_id"      => $data['v_name'],
				"LocationId"     => $data["LocationId"],
				"order"          => $order_add,
				"date_order"     => $data['order_date'],
				"status"         => 2,
				"payment_method" => $data['payment_name'],
				"currency_id"    => $data['currency'],
				"remark"         => $data['remark'],
				"user_mod"       => $GetUserId,
				"timestamp"      => new Zend_Date(),
				"version"        => 1,
				"net_total"      => $data['net_total'],
				"discount_type"	 => $data['discount_type'],
				"discount_value" => $data['discount_value'],
				"discount_real"  => $data['discount_real'],
				"paid"           => $data['paid'],
				"all_total"      => $data['all_total'],
				"balance"        => $data['all_total']-$data['paid']
		);
		//and info of purchase order
		$purchase_id = $db_global->addRecord($info_purchase_order,"tb_purchase_order");
		unset($info_purchase_order);
			
		$ids=explode(',',$data['identity']);
		//   $qtyonhand=0;
		foreach ($ids as $i)
		{
			//add history purchase order
				$data_history = array(
						'pro_id'	 => $data['item_id_'.$i],
						'type'		 => 1,
						'order'		 => $purchase_id,//$data['txt_order']
						'customer_id'=> $data['v_name'],
						'date'		 => new Zend_Date(),
						'status'	 => 2,
						'order_total'=> $data['all_total'],
						'qty'		 => $data['qty'.$i],
						'unit_price' => $data['price'.$i],
						'sub_total'  => $data['after_discount'.$i],
				);
				
				$db_global->addRecord($data_history,"tb_purchase_order_history");
				unset($data_history);
			//add purchase order item
			$data_item[$i]= array(
					'order_id'	  => $purchase_id,
					'pro_id'	  => $data['item_id_'.$i],
					'qty_order'	  => $data['qty'.$i],
					'price'		  => $data['price'.$i],
					'total_befor' => $data['total'.$i],
					'disc_type'	  => $data['dis-type-'.$i],
					'disc_value'  => $data['dis-value'.$i],
					'sub_total'	  => $data['after_discount'.$i]
			);
			$id_order_item=$db_global->addRecord($data_item[$i],"tb_purchase_order_item");
			unset($data_item[$i]);
				
			//update stock total inventory
			$locationid=$data['LocationId'];
			$itemId=$data['item_id_'.$i];
			$qtyrecord=$data['qty'.$i];//qty on 1 record
			$sql="SELECT tv.ProdId, tv.QuantityOnOrder,tv.QuantityAvailable
			FROM tb_inventorytotal AS tv
			INNER JOIN tb_product AS p ON tv.ProdId = p.pro_id
			WHERE p.pro_id = ".$data['item_id_'.$i];
			$rows=$db_global->getGlobalDbRow($sql);
			if($rows)
			{
				$qty_onhand   = $rows["QuantityOnOrder"]+$qtyrecord;
	
				$qty_on_order = array(
						"QuantityOnOrder"=>$rows["QuantityOnOrder"]+$qtyrecord
				);
				//update total stock
				$db_global->updateRecord($qty_on_order,$itemId,"ProdId","tb_inventorytotal");
				unset($qty_on_order);
			}
			else{
				$row = $db_global->InventoryExist($itemId);
				if($row){
					$qty_onhand   = $rows["QuantityOnOrder"]+$qtyrecord;
					$qty_on_order = array(
							"QuantityOnOrder"=>$rows["QuantityOnOrder"]+$qtyrecord
					);
					//update total stock
					$db_global->updateRecord($qty_on_order,$itemId,"ProdId","tb_inventorytotal");
					unset($qty_on_order);
				}
				else{
					$addInventory= array(
							'ProdId'            => $itemId,
							'QuantityOnOrder'    => $data['qty'.$i],
							'Timestamp'         => new Zend_date()
					);
					$db_global->addRecord($addInventory,"tb_inventorytotal");
					unset($addInventory);
				}
	
			}
	
		}
	
	}
	/*for update page purchase
	 * 
	 * 
	 * */
	///page update purchase order when click update
	public function updateVendorOrder($data)
	{
		try{
			$db = $this->getAdapter();
			$db->beginTransaction();
			$db_global = new Application_Model_DbTable_DbGlobal();
		
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
		
			//for update order by id\
			$id_order_update=$data['id'];
			$info_purchase_order=array(
					"vendor_id"      => $data['v_name'],
					"LocationId"     => $data["LocationId"],
					"order"          => $data['txt_order'],
					"date_order"     => $data['order_date'],
					"status"         => $data["status"],
					"remark"         => $data['remark'],
					"user_mod"       => $GetUserId,
					"timestamp"      => new Zend_Date(),
					"paid"           => $data['paid'],
					"all_total"      => $data['totalAmoun'],
					"balance"        => $data['remain']
			);
			//update info of order
			$db_global->updateRecord($info_purchase_order,$id_order_update,"order_id","tb_purchase_order");
		
// 			$sql_itm="SELECT p.pro_id,p.qty_onorder,sum(po.qty_order) AS qty_order FROM tb_purchase_order_item AS po
// 			INNER JOIN tb_product AS p ON p.pro_id = po.pro_id WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
			$sql_itm ="SELECT 
					  (SELECT p.pro_id FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS pro_id
					  
					  ,(SELECT p.qty_onorder FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_onorder
					  
					 , SUM(po.`qty_order`) AS qty_order FROM
					 
					  tb_purchase_order_item AS po WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
			
			$rows_order=$db_global->getGlobalDb($sql_itm);
			if($rows_order){
				foreach ($rows_order as $row_order){
					$qty_on_order = array(
							"qty_onorder"	=> $row_order["qty_onorder"]-$row_order["qty_order"],
							//"QuantityAvailable"	=> $row_order["QuantityAvailable"]-$row_order["qty_order"],
							"last_mod_date" 		=> new Zend_Date()
					);
					//update total stock
					$db_global->updateRecord($qty_on_order,$row_order["pro_id"],"pro_id","tb_product");
					
					$row_get = $db_global->porductLocationExist($row_order["pro_id"],$data["old_location"]);
					if($row_get){
						$qty_on_location = array(
								"qty"			=> $row_get["qty"]-$row_order["qty_order"],
								"last_usermod" 	=> $GetUserId,
								"last_mod_date" => new Zend_Date()
						);
						//update total stock
						$db_global->updateRecord($qty_on_location,$row_get["ProLocationID"],"ProLocationID","tb_prolocation");
					}
				}
				
			}
			unset($rows_order);
			$sql= "DELETE FROM tb_purchase_order_item WHERE order_id IN ($id_order_update)";
			$db_global->deleteRecords($sql);
		
			$ids=explode(',',$data['identity']);
			//add order in tb_inventory must update code again 9/8/13
			foreach ($ids as $i)
			{
				$data_item[$i]= array(
						'order_id'	  => $id_order_update,
						'pro_id'	  => $data['item_id_'.$i],
						'qty_order'	  => $data['qty'.$i],
						'price'		  => $data['price'.$i],
						'sub_total'	  => $data['total'.$i],
						'total_befor' => $data['total'.$i],
						'remark'	  => $data['remark_'.$i],//just add new 
				);
				$db->insert("tb_purchase_order_item", $data_item[$i]);
				unset($data_item[$i]);
				
				$locationid=$data['LocationId'];
				$itemId=$data['item_id_'.$i];
				$qtyrecord=$data['qty'.$i];//qty on 1 record
					
				$rows=$db_global -> productLocationInventory($itemId, $locationid);//to check product location
				if($rows)
				{
					$getrecord_id = $rows["ProLocationID"];
					$itemOnHand = array(
							'qty_onorder'   => $rows["qty_onorder"]+$qtyrecord
							//'QuantityAvailable'=> $rows["QuantityAvailable"]+$qtyrecord
					);
					//update total stock
					$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
					unset($itemOnHand);
					//update stock dork
					//$newqty       = $rows['qty']-$qtyrecord;
					$updatedata=array(
							'qty' => $rows['qty']+$qtyrecord
					);
					//update stock product location
					$db_global->updateRecord($updatedata,$getrecord_id,"ProLocationID","tb_prolocation");
					unset($updatedata);
					//update stock record
				}else
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
									'qty'          => $qtyrecord,
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
									'qty_onhand'=>$rowitem["qty_onhand"]+$qtyrecord,
									//'QuantityAvailable'=>$rowitem["QuantityAvailable"]+$qtyrecord,
					
							);
							//update total stock
							$itemid=$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
							unset($itemOnHand);
						}
						else
						{
							$dataInventory= array(
									'pro_id'            => $itemId,
									'qty_onhand'    => $qtyrecord,
									//'QuantityAvailable' => $qtyrecord,
									'last_mod_date'         => new Zend_date()
							);
							$db->insert("tb_product", $dataInventory);
							unset($dataInventory);
							//update stock product location
						}
					}
			}
			
			$db->commit();
			
		}catch(Exception $e){
			$db->rollBack();
		}
	}
	/*
	 * for update purchase order then click payment
	 * 29-13
	 * 
	 * */
	///when click payment on page update purchase order
	public function updateVendorOrderPayment($data){
	
		$db_global = new Application_Model_DbTable_DbGlobal();
		$db = $this->getAdapter();
	
		$session_user=new Zend_Session_Namespace('auth');
		$userName = $session_user->user_name;
		$GetUserId = $session_user->user_id;
	
// 		$idrecord=$data['v_name'];
// 		$datainfo=array(
// 				"contact_name"=>$data['contact'],
// 				"phone"       =>$data['txt_phone'],
// 				//	"add_remark"  =>$data['remark_add']
// 		);
// 		//updage customer info
// 		$itemid=$db_global->updateRecord($datainfo,$idrecord,"vendor_id","tb_vendor");
		$id_order_update=$data['id'];
		$info_order = array(
				"vendor_id"      => $data['v_name'],
				"LocationId"     => $data["LocationId"],
				"order"          => $data['txt_order'],
				"date_order"     => $data['order_date'],
				"status"         => $data["status"],
// 				"payment_method" => $data['payment_name'],
// 				"currency_id"    => $data['currency'],
				"remark"         => $data['remark'],
				"user_mod"       => $GetUserId,
				"timestamp"      => new Zend_Date(),
				"version"        => 1,
				"net_total"      => $data['net_total'],
				"discount_type"	 => $data['discount_type'],
				"discount_value" => $data['discount_value'],
				"discount_real"  => $data['discount_real'],
				"paid"           => $data['all_total'],
				"all_total"      => $data['all_total'],
				"balance"        => 0
		);
		//update info of order not done
		$db_global->updateRecord($info_order,$id_order_update,"order_id","tb_purchase_order");
			$rows_exist=$db_global->purchaseOrderHistoryExitAll($id_order_update);
			print_r($rows_exist);exit();
			if($rows_exist){
				foreach ($rows_exist as $id_history){
					$data_status=array(
							'status'=> 4
					);
					
					$db_global->updateRecord($data_status, $id_history['history_id'], "history_id", "tb_purchase_order_history");	
					unset($data_status);				
				}
				
			}
		unset($info_order);//if error check this here
		//and info of order
		$sql_item="SELECT iv.ProdId, iv.QuantityOnOrder,sum(po.qty_order) AS qtyorder
		FROM tb_purchase_order_item AS po
		INNER JOIN tb_inventorytotal AS iv ON iv.ProdId = po.pro_id WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
		$rows_order=$db_global->getGlobalDb($sql_item);
			if($rows_order){
				foreach ($rows_order as $row_order){
					$qty_on_order = array(
							"QuantityOnOrder"=>$row_order["QuantityOnOrder"]-$row_order["qtyorder"] ,
					);
							//update total stock
					$db_global->updateRecord($qty_on_order,$row_order["ProdId"],"ProdId","tb_inventorytotal");
				}
			}
				unset($rows_order); unset($rows_order);
				$sql= "DELETE FROM tb_purchase_order_item WHERE order_id IN ($id_order_update)";
				$db_global->deleteRecords($sql);
				//$db->DeleteData("tb_purchase_order_item"," WHERE order_id = ".$id_order_update);
				$ids=explode(',',$data['identity']);
				$qtyonhand=0;
				foreach ($ids as $i)
				{
					$data_item[$i]= array(
					'order_id'	  => $id_order_update,
					'pro_id'	  => $data['item_id_'.$i],
					'qty_order'	  => $data['qty'.$i],
					'price'		  => $data['price'.$i],
					'total_befor' => $data['total'.$i],
					'disc_type'	  => $data['dis-type-'.$i],
					'disc_value'  => $data['dis-value'.$i],
					'sub_total'	  => $data['after_discount'.$i]
					);
					$db->insert("tb_purchase_order_item", $data_item[$i]);
						
					unset($data_item[$i]);
					//UPDATE STOCK
					//check stock product location
					$locationid=$data['LocationId'];
					$itemId=$data['item_id_'.$i];
					$qtyrecord=$data['qty'.$i];//qty on 1 record

					$rows=$db_global->inventoryLocation($locationid, $itemId);
					if($rows)
					{
						$qty_on_order = array(
								"QuantityAvailable" => $rows["QuantityAvailable"] + $data['qty'.$i] ,
								"QuantityOnHand"    => $rows["QuantityOnHand"] + $data['qty'.$i]
						);
								//update total stock
						$db_global->updateRecord($qty_on_order,$itemId,"ProdId","tb_inventorytotal");
								unset($qty_on_order);
								//update stock dork
						$newqty       = $rows["qty"]+$qtyrecord;
						$updatedata=array(
								'qty' => $newqty
						);
						//update stock product location
						$db_global->updateRecord($updatedata,$rows["ProLocationID"],"ProLocationID","tb_prolocation");
								unset($updatedata);
								//update stock record
					}
							else
							{
								$insertdata=array(
										'pro_id'     => $itemId,
										'LocationId' => $locationid,
										'qty'        => $qtyrecord
								);
								//update stock product location
								$db->insert("tb_prolocation", $insertdata);
								
								$rows_stock=$db_global->InventoryExist($itemId);
								if($rows_stock){
									$dataInventory= array(
											'ProdId'            => $itemId,
											'QuantityOnHand'    => $rows_stock["QuantityOnHand"]+ $data['qty'.$i],
											'QuantityAvailable' => $rows_stock["QuantityAvailable"]+$data['qty'.$i],
											'Timestamp'         => new Zend_date()
									);
									$db_global->updateRecord($dataInventory,$rows_stock["ProdId"],"ProdId","tb_inventorytotal");
									unset($dataInventory);
								}//add new to stock inventory if don't have in stock inventory
								else{
									$addInventory= array(
											'ProdId'            => $itemId,
											'QuantityOnHand'    => $data['qty'.$i],
											'QuantityAvailable' => $data['qty'.$i],
											'Timestamp'         => new Zend_date()
									);
									$db->insert("tb_inventorytotal", $addInventory);
									unset($addInventory);
								}
							}
					}
				}
				public function cancelPurchaseOrder($data){
					try{
						$db_global= new Application_Model_DbTable_DbGlobal();
						$db = $this->getAdapter();
						$db->beginTransaction();
				
						$session_user=new Zend_Session_Namespace('auth');
						$GetUserId= $session_user->user_id;
				
						$id_order_update=$data['id'];
// 						$sql_itm="SELECT iv.ProdId, iv.QuantityOnHand,iv.QuantityAvailable,sum(po.qty_order) AS qty_order FROM tb_purchase_order_item AS po
// 						INNER JOIN tb_inventorytotal AS iv ON iv.ProdId = po.pro_id WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
						$sql_itm="SELECT 
									  SUM(po.`qty_order`) AS qty,
									  (SELECT p.pro_id FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS pro_id,
									  (SELECT p.qty_onhand FROM  tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_onhand ,
									  (SELECT p.qty_available FROM  tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_available ,
									  (SELECT p.qty_onorder FROM  tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_onorder
									FROM
									  tb_purchase_order_item AS po WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
						$rows_order=$db_global->getGlobalDb($sql_itm);
						//print_r($rows_order);exit();
						if($rows_order){
							foreach ($rows_order as $row_order){
								$row_get = $db_global->porductLocationExist($row_order["pro_id"],$data["old_location"]);
								//print_r($row_get);exit();
								$qty_prol = $row_get["qty"]-$row_order["qty"];
								$qty_order_prol= $row_get["qty_onorder"]-$row_order["qty"];
								//print_r($qty_prol ."and" . $qty_order_prol);exit();
								
								if($row_get){
									if($data["oldStatus"]==4 OR $data["oldStatus"]==5){
										if($qty_prol<0){
												
											Application_Form_FrmMessage::message("Can not Cancel Beacause Qty in stock in this Location is less than order");
											Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
										}else
											$qty_on_location = array(
													"qty"			=> $row_get["qty"]-$row_order["qty"],
													"last_usermod" 	=> $GetUserId,
													"last_mod_date" => new Zend_Date()
											);
									}else
									if($qty_order_prol<0){
										Application_Form_FrmMessage::message("Can not Cancel Beacause Qty order in stock in this Location is less than order ");
										Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
									}else
										$qty_on_location = array(
												"qty_onorder"			=> $row_get["qty_onorder"]-$row_order["qty"],
												"last_usermod" 			=> $GetUserId,
												"last_mod_date" 		=> new Zend_Date()
										);
									$db_global->updateRecord($qty_on_location,$row_get["ProLocationID"],"ProLocationID","tb_prolocation");
								}
								
								
								//print_r($row_order["qty_onhand"]);//exit();
								$qty=$row_order["qty_onhand"]-$row_order["qty"];
								$qty_order=$row_order["qty_onorder"]-$row_order["qty"];
								//print_r($qty);exit();
								if($data["oldStatus"]==4 OR $data["oldStatus"]==5){
									if($qty<0){
										
										Application_Form_FrmMessage::message("Can not Cancel Beacause Qty in stock is less then qty order  ");
										Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
									}else
									
										$qty_on_order = array(
												"qty_onhand"	=> $row_order["qty_onhand"]-$row_order["qty"],
												"qty_available"	=> $row_order["qty_available"]-$row_order["qty"],
												"last_mod_date" 		=> new Zend_Date()
										);
								}else 
									if($qty_order<0){
										Application_Form_FrmMessage::message("Can not Cancel Beacause Qty order in stock is less then qty order");
										Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
									}else
									$qty_on_order = array(
											"qty_onorder"	=> $row_order["qty_onorder"]-$row_order["qty"],
											"last_mod_date" 		=> new Zend_Date()
									);
									
								$db_global->updateRecord($qty_on_order,$row_order["pro_id"],"pro_id","tb_product");
								
								//End Update tb_product
								
								
								//Start update tb_prolocation
								
								
								$this->getPurchaseHistory($id_order_update, $row_order["pro_id"]);
							}
							
							$update =array("status"=>6);
							$db_global->updateRecord($update, $id_order_update,"order_id","tb_purchase_order");
						}
						$db->commit();
					}catch(Exception $e){
						$db->rollBack();
					}
				
				}
				public function getPurchaseHistory($order_id,$item_name){
					$db = $this->getAdapter();
					$sql = " SELECT history_id FROM tb_purchase_order_history 
					WHERE `order` = $order_id AND pro_id =$item_name ";
					$rows=$db->fetchAll($sql);
					//print_r($rows);exit();
					$db_global= new Application_Model_DbTable_DbGlobal();
					$update =array(
							"status"			=>	6,
							"last_update_date"	=>  new Zend_Date()
					
					);
					if(!empty($rows)){
						foreach ($rows AS $row){
							$db_global->updateRecord($update, $row["history_id"],"history_id","tb_purchase_order_history");
						}
					}
				}
				
//update purchase function test

				
	public function updateVendorStock($data){
		try{
			$db = $this->getAdapter();
			$db->beginTransaction();
			$db_global = new Application_Model_DbTable_DbGlobal();
				
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
				
						//for update order by id\
			$id_order_update=$data['id'];
			$recieved_id = $data["recieve_id"];
// 			$sql_recieve = new purchase_Model_DbTable_DbPurchaseOrder();
// 			$result = $sql_recieve->purchaseInfo($id_order_update);
// 			print_r($result["recieve_id"]);exit();
			//update info of order in tb_purchase order
			$info_purchase_order=array(
								"vendor_id"      => $data['v_name'],
								"LocationId"     => $data["LocationId"],
								"order"          => $data['txt_order'],
								"date_order"     => $data['order_date'],
								"status"         => $data["status"],
								"remark"         => $data['remark'],
								"user_mod"       => $GetUserId,
								"timestamp"      => new Zend_Date(),
								"paid"           => $data['paid'],
								"all_total"      => $data['totalAmoun'],
								"balance"        => $data['remain']
			);
						
			$db_global->updateRecord($info_purchase_order,$id_order_update,"order_id","tb_purchase_order");
			// end update info of order in tb_purchase order
			
			
			// Select all qty in tb_product and tb_purchase_order_item for compare product exist or not for update qty to old qty
			$sql_itm ="SELECT
								(SELECT p.pro_id FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS pro_id
									
								,(SELECT p.qty_onorder FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_onorder
								
								,(SELECT p.qty_onhand 	FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_onhand
								
								,(SELECT p.qty_available 	FROM tb_product AS p WHERE p.pro_id = po.`pro_id`) AS qty_available
									
								, SUM(po.`qty_order`) AS qty_order FROM
						
					tb_purchase_order_item AS po WHERE po.order_id = $id_order_update GROUP BY po.pro_id";
							
				$rows_order=$db_global->getGlobalDb($sql_itm);
				//print_r($rows);exit()
				// if product in purchase order item 
				if($rows_order){
					foreach ($rows_order as $row_order){
						
						
						//update qty and qty_onorder to old qty in tb_prolocation
						$row_get = $db_global->porductLocationExist($row_order["pro_id"],$data["old_location"]); // Check product Location exit
						//echo $row_get["ProLocationID"];
						//print_r($row_get["qty"]);//exit();
						if($row_get){
							
								$qty_prolo = $row_get["qty"]-$row_order["qty_order"];
								$qty_order_prolo = $row_get["qty_onorder"]-$row_order["qty_order"];
								//print_r($qty_prolo);
								//print_r($qty_order_prolo);exit();
								if($data["oldStatus"]==5 OR $data["oldStatus"]==4){
									if($qty_prolo<0){
										Application_Form_FrmMessage::message("You can't update purchase order because product in stock is less than Quality order");
										Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
									}else
										$qty_on_location = array(
												"qty"				=> $row_get["qty"]-$row_order["qty_order"],
												"last_usermod" 		=> $GetUserId,
												"last_mod_date" 	=> new Zend_Date()
										);
									
								}else {
									if($qty_order_prolo<0){
										Application_Form_FrmMessage::message("You can't update purchase order because product in stock is less than Quality order");
										Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
									}else 
									$qty_on_location = array(
											"qty_onorder"			=> $row_get["qty_onorder"]-$row_order["qty_order"],
											"last_usermod" 			=> $GetUserId,
											"last_mod_date" 		=> new Zend_Date()
									);
								}
							}
							//update total stock
							$db_global->updateRecord($qty_on_location,$row_get["ProLocationID"],"ProLocationID","tb_prolocation");
						
						//End update qty and qty_onorder to old qty in tb_prolocation
						
						
						//update qty_onhand and qty_avaolable to old qty in tb_product
						// in condition status = Recieved and Paid
						$qty = $row_order["qty_onhand"]-$row_order["qty_order"];
						$qty_order = $row_order["qty_onorder"]-$row_order["qty_order"];
						if($data["oldStatus"]==5 OR $data["oldStatus"] == 4){
								if($qty<0){
									Application_Form_FrmMessage::message("You can't update purchase order because product in stock is less than Quality order");
									Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
								}else{
									$qty_on_order = array(
											"qty_onhand"			=> $row_order["qty_onhand"]-$row_order["qty_order"],
											"qty_available"			=> $row_order["qty_available"] - $row_order["qty_order"],
											"last_mod_date" 		=> new Zend_Date()
									);
							}
						
						}else {
							if($qty_order<0){
								Application_Form_FrmMessage::message("You can't update purchase order because product onorder is less than Quality order");
								Application_Form_FrmMessage::redirectUrl("/purchase/index/index");
							}else
								$qty_on_order = array(
										"qty_onorder"			=> $row_order["qty_onorder"]-$row_order["qty_order"],
										"last_mod_date" 		=> new Zend_Date()
								);
						}
						
								//update total stock
						$db_global->updateRecord($qty_on_order,$row_order["pro_id"],"pro_id","tb_product");
						
						//end update qty_onhand and qty_avaolable to old qty in tb_product
						
					}
			}
			unset($rows_order);
			
			// Delete old purchase order item before insert new purchase order item in old order_id
			$sql= "DELETE FROM tb_purchase_order_item WHERE order_id IN ($id_order_update)";
			$db_global->deleteRecords($sql);
			unset($sql);
			
			$sql_history= "DELETE FROM tb_purchase_order_history WHERE `order` IN ($id_order_update)";
			$db_global->deleteRecords($sql_history);
			unset($sql_history);
			/// update 
				
			$ids=explode(',',$data['identity']);
							//add order in tb_inventory must update code again 9/8/13
			//print_r($ids);exit();
			
			foreach ($ids as $i) {
				// Insert New purchase order item in old order_id
				
				$data_item[$i]= array(
						'order_id'	 	 => 	$id_order_update,
						'pro_id'	  	 => 	$data['item_id_'.$i],
						'qty_order'	 	 => 	$data['qty'.$i],
						'price'		 	 => 	$data['price'.$i],
						'sub_total'	 	 => 	$data['total'.$i],
						'total_befor'	 => 	$data['total'.$i],
						'remark'	 	 => 	$data['remark_'.$i]//just add new
				);
			//print_r($data_item); echo "<br />";echo "<br />";
				$db->insert("tb_purchase_order_item", $data_item[$i]);
				unset($data_item[$i]);
				
				$data_history[$i] = array(
							'order'	 	 		=> 		$id_order_update,
							'pro_id'	 		=> 		$data['item_id_'.$i],
							'type'		 		=> 		1,
							'customer_id'		=>	 	$data['v_name'],
							'status'	 		=> 		$data["status"],
							'order_total'		=>		$data['total'.$i],
							'qty'		 		=> 		$data['qty'.$i],
							'unit_price' 		=> 		$data['price'.$i],
							'sub_total'  		=> 		$data['total'.$i],
							'date'				=> 		$data["old_history_date"],
							'last_update_date' 	=> 		new Zend_Date()
									);
				//print_r($data_history);exit();
				$db->insert("tb_purchase_order_history", $data_history[$i]);
				unset($data_history[$i]);
				
				
				
				
				$locationid=$data['LocationId'];
				$itemId=$data['item_id_'.$i];
				$qtyrecord=$data['qty'.$i];//qty on 1 record

				// Update stock in tb_product
 				$rows=$db_global -> productLocationInventory($itemId, $locationid);//to check product location
				//print_r($rows); exit();
				if($rows){
					$getrecord_id = $rows["ProLocationID"];
					
					if($data["status"]==5 OR $data["status"]==4){
						$itemOnHand = array(
							'qty_onhand'   		=> 		$rows["qty_onhand"]+$qtyrecord,
							'qty_available'		=> 		$rows["qty_available"]+$qtyrecord,
							'last_mod_date'     => 		new Zend_date()
						);
					}else{ 
						$itemOnHand = array(
							'qty_onorder'  		=> 		$rows["qty_onorder"]+$qtyrecord,
							'last_mod_date'     => 		new Zend_date()
													
						);
					}
				
					$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
					unset($itemOnHand);
					//End update total stock
					
					// Update product Location					
					if($data["status"]==5 OR $data["status"]==4){
						$updatedata=array(
							'qty' => $rows['qty']+$qtyrecord,
							'last_mod_date'     => new Zend_date()
						);
					}else {
						$updatedata=array(
							'qty_onorder' 		=> 		$rows['qty_onorder']+$qtyrecord,
							'last_mod_date'     => 		new Zend_date()
						);
					}
					
					$db_global->updateRecord($updatedata,$getrecord_id,"ProLocationID","tb_prolocation");
					unset($updatedata);
					//End update stock product location						//update stock record
				}
				
				//End if product in purchase order item
				
				// If product don't exist in old purchase oreder item
				else {
					//insert stock ;
					$rows_pro_exit= $db_global->productLocation($itemId, $locationid); // check product location exist
					
					// if product exist Update qty in tb_prolocation 
					if($rows_pro_exit){
						if($data["status"]==5 OR $data["status"]==4){
							$updatedata=array(
									'qty' 				=> 		$rows['qty']+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}else {
							$updatedata=array(
									'qty_onorder' 		=> 		$rows['qty_onorder']+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}
					$itemid=$db_global->updateRecord($updatedata,$rows_pro_exit['ProLocationID'], "ProLocationID", "tb_prolocation");
					unset($updatedata);
					// End if product exist Update qty in tb_prolocation
					
					}else{ // If product not exist insert New product in tb_prolocation
						$insertdata=array(
							'pro_id'       		=> 		$itemId,
							'LocationId'   		=> 		$locationid,
							'last_usermod'		=> 		$GetUserId,
							'qty'         		=> 		$qtyrecord,
							'last_mod_date'		=> 		new Zend_Date()
						);
								//update stock product location
					$db->insert("tb_prolocation", $insertdata);
					unset($insertdata);
					}
					// End If product not exist insert New product in tb_prolocation
													
					$rowitem=$db_global->InventoryExist($itemId);//to check product exist 
					// If productt exist update product in tb_product
					if($rowitem){
						if($data["status"]==5 OR $data["status"]==4){
							$itemOnHand = array(
									'qty_onhand'   		=> 		$rows["qty_onhand"]+$qtyrecord,
									'qty_available'		=> 		$rows["qty_available"]+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}else{
							$itemOnHand = array(
									'qty_onorder'   	=> 		$rows["qty_onrder"]+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
										
							);
						}
						
								//update total stock
					$itemid=$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
					unset($itemOnHand);
					// If productt exist update product in tb_product
					}else { // If product not exist insert new product in tb_product
						$dataInventory= array(
							'pro_id'            => 		$itemId,
							'qty_onhand'    	=> 		$qtyrecord,
							'qty_available' 	=> 		$qtyrecord,
							'last_mod_date'     => 		new Zend_date()
					);
					$db->insert("tb_product", $dataInventory);
					unset($dataInventory);
							//update stock product location
					}
			}
		}										
 		$db->commit();
													
		}catch(Exception $e){
			$db->rollBack();
			$e->getMessage();
			//echo $theCauseOfErrorOnlyDoNotRedirectToError;
			exit();
		}
	}
	public function updateVendorCancellOrder($data){
		try{
			$db = $this->getAdapter();
			$db->beginTransaction();
			$db_global = new Application_Model_DbTable_DbGlobal();
	
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
	
			//for update order by id\
			$id_order_update=$data['id'];

				
			//update info of order in tb_purchase order
			$info_purchase_order=array(
					"vendor_id"      => $data['v_name'],
					"LocationId"     => $data["LocationId"],
					"order"          => $data['txt_order'],
					"date_order"     => $data['order_date'],
					"status"         => $data["status"],
					"remark"         => $data['remark'],
					"user_mod"       => $GetUserId,
					"timestamp"      => new Zend_Date(),
					"paid"           => $data['paid'],
					"all_total"      => $data['totalAmoun'],
					"balance"        => $data['remain']
			);
	
			$db_global->updateRecord($info_purchase_order,$id_order_update,"order_id","tb_purchase_order");
			// end update info of order in tb_purchase order
			
			// Insert recieved order
			
			if($data["status"]==5 OR $data["status"]==4){
			$recieved_order = array(
					"recieve_type" => 1,
					"order_id" 		=> $id_order_update,
					"order_no"		=>$data["txt_order"],
					"vendor_id"		=>$data['v_name'],
					"location_id" 	=>$data["LocationId"],
					"order_date"	=> new Zend_Date(),
					"date_recieve"	=> new Zend_Date(),
					"status"		=> $data['status'],
					"paid"			=> $data['paid'],
					"all_total"		=>$data['totalAmoun'],
					"balance"		=>$data['remain'],
					"user_recieve"	=>$GetUserId
			);
			$recieved_orders = $db_global->addRecord($recieved_order, "tb_recieve_order");
			unset($recieved_orders);
			}
				
			// Delete old purchase order item before insert new purchase order item in old order_id
			$sql= "DELETE FROM tb_purchase_order_item WHERE order_id IN ($id_order_update)";
			$db_global->deleteRecords($sql);
			unset($sql);
				
			$sql_history= "DELETE FROM tb_purchase_order_history WHERE `order` IN ($id_order_update)";
			$db_global->deleteRecords($sql_history);
			unset($sql_history);
			/// update
	
			$ids=explode(',',$data['identity']);
			//add order in tb_inventory must update code again 9/8/13
			//print_r($ids);exit();
				
			foreach ($ids as $i) {
				
				// Insert recieved order item 
				if($data["status"]==5 OR $data["status"]==4){
					$recieved_order_item[$i] = array(
							"recieve_id"	=> 		$recieved_orders,
							"pro_id"		=> 		$data['item_id_'.$i],
							"order_id"		=> 		$id_order_update,
							"qty_order"		=> 		$data['qty'.$i],
							"qty_recieve"	=> 		$data['qty'.$i],
							//"qty_remian"	=> ,
							"price"			=> 		$data['price'.$i],
							"total_before"	=> 		$data['total'.$i],
							"sub_total"		=> 		$data['total'.$i],
					);
					$db->insert("tb_recieve_order_item", $recieved_order_item[$i]);
					unset($recieved_order_item[$i]);
				}
				
				// Insert New purchase order item in old order_id
	
				$data_item[$i]= array(
						'order_id'	 	 => 	$id_order_update,
						'pro_id'	  	 => 	$data['item_id_'.$i],
						'qty_order'	 	 => 	$data['qty'.$i],
						'price'		 	 => 	$data['price'.$i],
						'sub_total'	 	 => 	$data['total'.$i],
						'total_befor'	 => 	$data['total'.$i],
						'remark'	 	 => 	$data['remark_'.$i]//just add new
				);
				//print_r($data_item); echo "<br />";echo "<br />";
				$db->insert("tb_purchase_order_item", $data_item[$i]);
				unset($data_item[$i]);
	
				$data_history[$i] = array(
						'order'	 	 		=> 		$id_order_update,
						'pro_id'	 		=> 		$data['item_id_'.$i],
						'type'		 		=> 		1,
						'customer_id'		=>	 	$data['v_name'],
						'status'	 		=> 		$data["status"],
						'order_total'		=>		$data['total'.$i],
						'qty'		 		=> 		$data['qty'.$i],
						'unit_price' 		=> 		$data['price'.$i],
						'sub_total'  		=> 		$data['total'.$i],
						'date'				=> 		$data["old_history_date"],
						'last_update_date' 	=> 		new Zend_Date()
				);
				//print_r($data_history);exit();
				$db->insert("tb_purchase_order_history", $data_history[$i]);
				unset($data_history[$i]);
	
				$locationid=$data['LocationId'];
				$itemId=$data['item_id_'.$i];
				$qtyrecord=$data['qty'.$i];//qty on 1 record
	
				// Update stock in tb_product
				$rows=$db_global -> productLocationInventory($itemId, $locationid);//to check product location
				//print_r($rows); exit();
				if($rows){
					$getrecord_id = $rows["ProLocationID"];
						
					if($data["status"]==5 OR $data["status"]==4){
						$itemOnHand = array(
								'qty_onhand'   		=> 		$rows["qty_onhand"]+$qtyrecord,
								'qty_available'		=> 		$rows["qty_available"]+$qtyrecord,
								'last_mod_date'     => 		new Zend_date()
						);
					}else{
						$itemOnHand = array(
								'qty_onorder'  		=> 		$rows["qty_onorder"]+$qtyrecord,
								'last_mod_date'     => 		new Zend_date()
									
						);
					}
	
					$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
					unset($itemOnHand);
					//End update total stock
						
					// Update product Location
					if($data["status"]==5 OR $data["status"]==4){
						$updatedata=array(
								'qty' => $rows['qty']+$qtyrecord,
								'last_mod_date'     => new Zend_date()
						);
					}else {
						$updatedata=array(
								'qty_onorder' 		=> 		$rows['qty_onorder']+$qtyrecord,
								'last_mod_date'     => 		new Zend_date()
						);
					}
						
					$db_global->updateRecord($updatedata,$getrecord_id,"ProLocationID","tb_prolocation");
					unset($updatedata);
					//End update stock product location						//update stock record
				}
	
				//End if product in purchase order item
	
				// If product don't exist in old purchase oreder item
				else {
					//insert stock ;
					$rows_pro_exit= $db_global->productLocation($itemId, $locationid); // check product location exist
						
					// if product exist Update qty in tb_prolocation
					if($rows_pro_exit){
						if($data["status"]==5 OR $data["status"]==4){
							$updatedata=array(
									'qty' 				=> 		$rows['qty']+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}else {
							$updatedata=array(
									'qty_onorder' 		=> 		$rows['qty_onorder']+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}
						$itemid=$db_global->updateRecord($updatedata,$rows_pro_exit['ProLocationID'], "ProLocationID", "tb_prolocation");
						unset($updatedata);
						// End if product exist Update qty in tb_prolocation
							
					}else{ // If product not exist insert New product in tb_prolocation
						$insertdata=array(
								'pro_id'       		=> 		$itemId,
								'LocationId'   		=> 		$locationid,
								'last_usermod'		=> 		$GetUserId,
								'qty'         		=> 		$qtyrecord,
								'last_mod_date'		=> 		new Zend_Date()
						);
						//update stock product location
						$db->insert("tb_prolocation", $insertdata);
						unset($insertdata);
					}
					// End If product not exist insert New product in tb_prolocation
						
					$rowitem=$db_global->InventoryExist($itemId);//to check product exist
					// If productt exist update product in tb_product
					if($rowitem){
						if($data["status"]==5 OR $data["status"]==4){
							$itemOnHand = array(
									'qty_onhand'   		=> 		$rows["qty_onhand"]+$qtyrecord,
									'qty_available'		=> 		$rows["qty_available"]+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
							);
						}else{
							$itemOnHand = array(
									'qty_onorder'   	=> 		$rows["qty_onrder"]+$qtyrecord,
									'last_mod_date'     => 		new Zend_date()
	
							);
						}
	
						//update total stock
						$itemid=$db_global->updateRecord($itemOnHand,$itemId,"pro_id","tb_product");
						unset($itemOnHand);
						// If productt exist update product in tb_product
					}else { // If product not exist insert new product in tb_product
						$dataInventory= array(
								'pro_id'            => 		$itemId,
								'qty_onhand'    	=> 		$qtyrecord,
								'qty_available' 	=> 		$qtyrecord,
								'last_mod_date'     => 		new Zend_date()
						);
						$db->insert("tb_product", $dataInventory);
						unset($dataInventory);
						//update stock product location
					}
				}
			}
			$db->commit();
				
		}catch(Exception $e){
			$db->rollBack();
			$e->getMessage();
			//echo $theCauseOfErrorOnlyDoNotRedirectToError;
			exit();
		}
	}
}