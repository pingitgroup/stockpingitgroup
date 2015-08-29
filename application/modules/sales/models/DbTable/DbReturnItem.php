<?php

class sales_Model_DbTable_DbReturnItem extends Zend_Db_Table_Abstract
{
	public function returnItem($post)
	{
	    $db_global = new Application_Model_DbTable_DbGlobal();
		$db = $this->getAdapter();	
		$db->beginTransaction();
		try{
			$session_user=new Zend_Session_Namespace('auth');
			$userName=$session_user->user_name;
			$GetUserId= $session_user->user_id;
			if($post['return_no']==""){
				$date= new Zend_Date();
				$return_no="RCI".$date->get('hh-mm-ss');
			}
			else{
				$return_no=$post['return_no'];
			
			}
			$data_return = array(
					"customer_id"	=> $post["customer_id"],
					"return_no"		=> $return_no,
					"invoice_no"	=> $post["invoice_no"],
					"date_return"	=> $post["return_date"],
					"location_id"	=> $post["LocationId"],
					"remark"		=> $post["remark_return"],
					"user_mod"		=> $GetUserId,
					"timestamp"		=> new Zend_Date(),
					//"paid"			=> $post["paid"],
					"all_total"		=> $post["all_total"],
					//"balance" 		=> $post["all_total"]-$post["paid"]			
					);
			$return_id = $db_global->addRecord($data_return, "tb_return_customer_in");
			unset($data_update);
			$ids=explode(',',$post['identity']);
			foreach($ids as $i){
				$add_data = array(
						"return_id" 	=> $return_id,
						"pro_id" 		=> $post["item_id_".$i],
						"qty_return" 	=> $post["qty_return_".$i],
						"price" 		=> $post["price_".$i],
						"sub_total" 	=> $post["sub_total_".$i],
						"return_remark" => $post["remark_".$i]
				);
				$db->insert("tb_return_customer_item_in", $add_data);
				$rows=$db_global->inventoryLocation($post["LocationId"], $post["item_id_".$i]);
				
				if($rows){
					$updatedata=array(
							'qty' 				=> $rows["qty"]+$post["qty_return_".$i],
							"last_usermod"		=> $GetUserId,
							"last_mod_date"		=> new Zend_Date()
					);
					//update stock product location
					$db_global->updateRecord($updatedata,$rows["ProLocationID"],"ProLocationID","tb_prolocation");
					unset($updatedata);			
					$qty_on_return = array(
							"QuantityOnHand"    => $rows["QuantityOnHand"] + $post["qty_return_".$i],
							"QuantityAvailable" => $rows["QuantityAvailable"] + $post["qty_return_".$i],
							"Timestamp"			=> new zend_date()
					);
					//update total stock
					$db_global->updateRecord($qty_on_return,$post["item_id_".$i],"ProdId","tb_inventorytotal");
					unset($qty_on_return);
							$data_history = array
								(		'transaction_type'  => 7,
										'pro_id'     		=> $post["item_id_".$i],
										'date'				=> new Zend_Date(),
										'location_id' 		=> $post["LocationId"],
										'Remark'			=> $return_no,
										'qty_edit'        	=> $post["qty_return_".$i],
										'qty_before'        => $rows["qty"],
										'qty_after'        	=> $rows["qty"]+$post["qty_return_".$i],
										'user_mod'			=> $GetUserId
								);
						$db->insert("tb_move_history", $data_history);
						unset($data_history);	
				}
				else{
					
					$insertdata=array(
							'pro_id'     => $post["item_id_".$i],
							'LocationId' => $post["LocationId"],
							'qty'        => $post["qty_return_".$i]
					);
					//update stock product location
					$db->insert("tb_prolocation", $insertdata);
					unset($insertdata);
						$data_history = array
								(		'transaction_type'  => 7,
										'pro_id'     		=> $post["item_id_".$i],
										'date'				=> new Zend_Date(),
										'location_id' 		=> $post["LocationId"],
										'Remark'			=> $return_no,
										'qty_edit'        	=> $post["qty_return_".$i],
										'qty_before'        => 0,
										'qty_after'        	=> $post["qty_return_".$i],
										'user_mod'			=> $GetUserId
								);
						$db->insert("tb_move_history", $data_history);//add history
						unset($data_history);	
					
					$rows_stock=$db_global->InventoryExist($post["item_id_".$i]);
					if($rows_stock){
						$dataInventory= array(
								'QuantityOnHand'    => $rows_stock["QuantityOnHand"]+$post["qty_return_".$i],
								'QuantityAvailable' => $rows_stock["QuantityAvailable"] + $post["qty_return_".$i],
								'Timestamp'         => new Zend_date()
						);
						$db_global->updateRecord($dataInventory,$rows_stock["ProdId"],"ProdId","tb_inventorytotal");
						unset($dataInventory);
					}
					else{
						$addInventory= array(
								'ProdId'            => $post["item_id_".$i],
								'QuantityOnHand'    => $post["qty_return_".$i],
								'QuantityAvailable' => $post["qty_return_".$i],
								'Timestamp'         => new Zend_date()
						);
						$db->insert("tb_inventorytotal", $addInventory);
						unset($addInventory);
					}
					
				}
			
			}
			$db->commit();
		}catch(Exception $e){
			$db->rollBack();
		}
  }
  
  //
  public function returnItemToCustomer($post)//for return item to customer not yet done
  {
  	$db_global = new Application_Model_DbTable_DbGlobal();
  	$db = $this->getAdapter();
  	$db->beginTransaction();
  	try{
  		$session_user=new Zend_Session_Namespace('auth');
  		$userName=$session_user->user_name;
  		$GetUserId= $session_user->user_id;
  		
  		if($post["invoice_no"]==""){
  			$date= new Zend_Date();
  			$returnout_no="RCO".$date->get('hh-mm-ss');
  		}
  		else{
  			$returnout_no=$post['invoice_no'];
  				
  		}
  		
  			
  		$data_return = array(
  				"returnin_id"	=> $post["return_id"],
  				"returnout_no"	=> $returnout_no,
  				"location_id"	=> $post["LocationId"],
  				"date_return"	=> $post["return_date"],
  				"remark"		=> $post["remark_return"],
  				"user_mod"		=> $GetUserId,
  				"timestamp"		=> new Zend_Date(),
  				//"paid"			=> $post["paid"],
  				"all_total"		=> $post["all_total"],
  				//"balance" 		=> $post["all_total"]-$post["paid"]
  		);
  		$returnout_id = $db_global->addRecord($data_return, "tb_return_customer_out");
  		unset($data_update);
  		$ids=explode(',',$post['identity']);
  		foreach($ids as $i){
  			$add_data = array(
  					"return_id" 	=> $returnout_id,
  					"pro_id" 		=> $post["item_id_".$i],
  					"qty_return" 	=> $post["qty_return_".$i],
  					"price" 		=> $post["price_".$i],
  					"sub_total" 	=> $post["sub_total_".$i],
  					"return_remark" => $post["remark_".$i]
  			);
  			$db->insert("tb_return_customer_item_out", $add_data);
  			$rows=$db_global->inventoryLocation($post["LocationId"], $post["item_id_".$i]);
  
  			if($rows){
  				$updatedata=array(
  						'qty' 				=> $rows["qty"]-$post["qty_return_".$i],
  						"last_usermod"		=> $GetUserId,
  						"last_mod_date"		=> new Zend_Date()
  				);
  				//update stock product location
  				$db_global->updateRecord($updatedata,$rows["ProLocationID"],"ProLocationID","tb_prolocation");
  				unset($updatedata);
  				$qty_on_return = array(
  						"QuantityOnHand"    => $rows["QuantityOnHand"] - $post["qty_return_".$i],
  						"QuantityAvailable" => $rows["QuantityAvailable"] - $post["qty_return_".$i],
  						"Timestamp"			=> new zend_date()
  				);
  				//update total stock
  				$db_global->updateRecord($qty_on_return,$post["item_id_".$i],"ProdId","tb_inventorytotal");
  				unset($qty_on_return);
  							$data_history = array //not info for return item to customer
								(		'transaction_type'  => 6,
										'pro_id'     		=> $post["item_id_".$i],
										'date'				=> new Zend_Date(),
										'location_id' 		=> $post["LocationId"],
										'Remark'			=> $returnout_no,
										'qty_edit'        	=> $post["qty_return_".$i],
										'qty_before'        => $rows["qty"],
										'qty_after'        	=> $rows["qty"]-$post["qty_return_".$i],
										'user_mod'			=> $GetUserId
								);
							$db->insert("tb_move_history", $data_history);
							unset($data_history);	
  			}
  			else{
  					
  				$insertdata=array(
  						'pro_id'     => $post["item_id_".$i],
  						'LocationId' => $post["LocationId"],
  						'qty'        => -$post["qty_return_".$i]
  				);
  				//update stock product location
  				$db->insert("tb_prolocation", $insertdata);
  				unset($insertdata);
  							$data_history = array //not info for return item to customer
								(		'transaction_type'  => 6,
										'pro_id'     		=> $post["item_id_".$i],
										'date'				=> new Zend_Date(),
										'location_id' 		=> $post["LocationId"],
										'Remark'			=> $returnout_no,
										'qty_edit'        	=> $post["qty_return_".$i],
										'qty_before'        => 0,
										'qty_after'        	=> -$post["qty_return_".$i],
										'user_mod'			=> $GetUserId
								);
							$db->insert("tb_move_history", $data_history);
							unset($data_history);	
  					
  				$rows_stock=$db_global->InventoryExist($post["item_id_".$i]);
  				if($rows_stock){
  					$dataInventory= array(
  							'QuantityOnHand'    => $rows_stock["QuantityOnHand"]-$post["qty_return_".$i],
  							'QuantityAvailable' => $rows_stock["QuantityAvailable"] - $post["qty_return_".$i],
  							'Timestamp'         => new Zend_date()
  					);
  					$db_global->updateRecord($dataInventory,$rows_stock["ProdId"],"ProdId","tb_inventorytotal");
  					unset($dataInventory);
  				}
  				else{
  					$addInventory= array(
  							'ProdId'            => $post["item_id_".$i],
  							'QuantityOnHand'    => -$post["qty_return_".$i],
  							'QuantityAvailable' => -$post["qty_return_".$i],
  							'Timestamp'         => new Zend_date()
  					);
  					$db->insert("tb_inventorytotal", $addInventory);
  					unset($addInventory);
  				}
  			}
  				
  		}
  		$db->commit();
  	}catch(Exception $e){
  		$db->rollBack();
  	}
  
  
  }
  public function updateReturnItem($post){	
  	$db = $this->getAdapter();
  	$db_global = new Application_Model_DbTable_DbGlobal();
  	$session_user=new Zend_Session_Namespace('auth');
  	$userName=$session_user->user_name;
  	$GetUserId= $session_user->user_id;
  	
	  $idrecord=$post['v_name'];
			$datainfo=array(
					"contact_name" => $post['contact'],
					"phone"        => $post['txt_phone'],
					"add_name"     => $post["vendor_address"]
			);
			//updage vendor info
			$db_global->updateRecord($datainfo,$idrecord,"vendor_id","tb_vendor");
			unset($datainfo);
			$return_id = $post["id"];
			$data_update = array(
					"vendor_id"		=> $post["v_name"],
					"return_no"		=> $post['retun_order'],
					"date_return"	=> $post["return_date"],
					"payment_method"=> $post["payment_name"],
					"currency_id"	=> $post["currency"],
					"remark"		=> $post["return_remark"],
					"user_mod"		=> $GetUserId,
					"timestamp"		=> new Zend_Date(),
					"paid"			=> $post["paid"],
					"all_total"		=> $post["all_total"],
					"balance" 		=> $post["all_total"]-$post["paid"]			
					);
			 $db_global->addRecord($data_update, "tb_return");
			 $db_global->updateRecord($data_update, $return_id, "return_id", "tb_return");
			unset($data_update);
		/////
  	
  	$sql_item="SELECT iv.ProdId, iv.QuantityOnHand,iv.QuantityAvailable,rv.location_id,rv.qty_return FROM tb_return_vendor_item AS rv
  	INNER JOIN tb_inventorytotal AS iv ON iv.ProdId = rv.pro_id WHERE rv.return_id = $return_id";
  	$rows_return=$db_global->getGlobalDb($sql_item);
  	if($rows_return){
  		foreach ($rows_return as $row_return){
  			$qty_on_order = array(
  					"QuantityOnHand"	=> $row_return["QuantityOnHand"] + $row_return["qty_return"],
  					"QuantityAvailable"	=> $row_return["QuantityAvailable"] + $row_return["qty_return"],
  					"Timestamp"			=> new zend_date()
  			);
  			//update total stock
  			$db_global->updateRecord($qty_on_order,$row_return["ProdId"],"ProdId","tb_inventorytotal");
  			unset($qty_on_order);
  			
  			$rowitem_exist=$db_global->porductLocationExist($row_return["ProdId"], $row_return["location_id"]);
  			if($rowitem_exist){
  				$updatedata=array(
  						'qty' 				=> $rowitem_exist["qty"]+$row_return["qty_return"],
  						"last_usermod"		=> $GetUserId,
  						"last_mod_date"		=> new Zend_Date()
  				);
  				//update stock product location
  				$db_global->updateRecord($updatedata,$rowitem_exist["ProLocationID"],"ProLocationID","tb_prolocation");
  				unset($updatedata);
  				
  			}
  			
  		}
  	}
	  	$sql= "DELETE FROM tb_return_vendor_item WHERE return_id IN ($return_id)";
	  	$db_global->deleteRecords($sql);
  	
  	$ids=explode(',',$post['identity']);
  	//add order in tb_inventory must update code again 9/8/13
  	foreach ($ids as $i)
  	{
  				$add_data = array(
					"return_id" 	=> $return_id,
					"pro_id" 		=> $post["item_id_".$i],
					"location_id"	=> $post["LocationId_".$i],
					"qty_return" 	=> $post["qty_return_".$i],
					"price" 		=> $post["price_".$i],
					"sub_total" 	=> $post["sub_total_".$i],
					"return_remark" => $post["remark_".$i]
			);
			$db->insert("tb_return_vendor_item", $add_data);
			$rows=$db_global->inventoryLocation($post["LocationId_".$i], $post["item_id_".$i]);
			
			if($rows){
				$updatedata=array(
						'qty' 				=> $rows["qty"]-$post["qty_return_".$i],
						"last_usermod"		=> $GetUserId,
						"last_mod_date"		=> new Zend_Date()
				);
				//update stock product location
				$db_global->updateRecord($updatedata,$rows["ProLocationID"],"ProLocationID","tb_prolocation");
				unset($updatedata);			
				$qty_on_return = array(
						"QuantityOnHand"    => $rows["QuantityOnHand"] - $post["qty_return_".$i],
						"QuantityAvailable" => $rows["QuantityAvailable"] - $post["qty_return_".$i],
						"Timestamp"			=> new zend_date()
				);
				//update total stock
				$db_global->updateRecord($qty_on_return,$post["item_id_".$i],"ProdId","tb_inventorytotal");
				unset($qty_on_return);
				//add return history
						$data_history = array
						(		'transaction_type'  => 4,
								'pro_id'     		=> $post["item_id_".$i],
								'date'				=> new Zend_Date(),
								'location_id' 		=> $post["LocationId_".$i],
								'Remark'			=> $post['remark_'.$i],
								'qty_edit'        	=> $post["qty_return_".$i],
								'qty_before'        => $rows["qty"],
								'qty_after'        	=> $rows["qty"]-$post["qty_return_".$i],
								'user_mod'			=> $GetUserId
						);
						$db->insert("tb_move_history", $data_history);
						unset($data_history);	
				
			}
			else{
				
				$insertdata=array(
						'pro_id'     => $post["item_id_".$i],
						'LocationId' => $post["LocationId_".$i],
						'qty'        => -$post["qty_return_".$i]
				);
				//update stock product location
				$db->insert("tb_prolocation", $insertdata);
				unset($insertdata);
				//add return history
				$data_history = array
				(		'transaction_type'  => 4,
						'pro_id'     		=> $post["item_id_".$i],
						'date'				=> new Zend_Date(),
						'location_id' 		=> $post["LocationId_".$i],
						'Remark'			=> $post['remark_'.$i],
						'qty_edit'        	=> $post["qty_return_".$i],
						'qty_before'        => 0,
						'qty_after'        	=> -$post["qty_return_".$i],
						'user_mod'			=> $GetUserId
				);
				$db->insert("tb_move_history", $data_history);
				unset($data_history);
				
				$rows_stock=$db_global->InventoryExist($post["item_id_".$i]);
				if($rows_stock){
					$dataInventory= array(
							'QuantityOnHand'    => $rows_stock["QuantityOnHand"]- $post["qty_return_".$i],
							'QuantityAvailable' => $rows_stock["QuantityAvailable"] - $post["qty_return_".$i],
							'Timestamp'         => new Zend_date()
					);
					$db_global->updateRecord($dataInventory,$rows_stock["ProdId"],"ProdId","tb_inventorytotal");
					unset($dataInventory);
				}
				else{
					$addInventory= array(
							'ProdId'            => $post["item_id_".$i],
							'QuantityOnHand'    => -$post["qty_return_".$i],
							'QuantityAvailable' => -$post["qty_return_".$i],
							'Timestamp'         => new Zend_date()
					);
					$db->insert("tb_inventorytotal", $addInventory);
					unset($addInventory);
				}
				
			}
  	
  	}
  	
   }
	
}