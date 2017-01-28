<?php

// 13.01.2017



/*
	1   - Неоформленный заказ
	5   - Заказ оформлен - готов к оплате
	10  - Производство
	20  - Отправка
	30  - Доставка
	40  - Ожидание заказчика в ПВЗ
	50  - Доставка по адресу
	60  - Ожидание оплаты
	70  - Завершено
	100 - Возврат товара
	200 - Отмена заказа заказчиком
	210 - Отмена заказа администратором
*/








function YaKa($info)
{
	$infos= array(
		'shopid'       => '',
		'scid'         => '',
		'shoppassword' => '',
	);
	return $infos[$info];
}




function _AJAX()
{
	global $nc_core, $sub;

	$action= $_GET['a'];



	if($action=='catalogfilter_items')
	{
		print OBOI_Catalog();
	}
	if($action=='catalog_itempage')
	{
		print OBOI_Catalog_Item();
	}
	if($action=='catalog_to_favorite')
	{
		if( ! $_SESSION['catalog_user_favorite'][$_POST['itemart']]) $_SESSION['catalog_user_favorite'][$_POST['itemart']]= $_POST['itemart'];
		print OBOI_Favorite();
	}
	if($action=='clear_favorite')
	{
		$_SESSION['catalog_user_favorite']= array();
		print OBOI_Favorite();
	}



	if($action=='megaform_send')
	{
		if($_POST['formid']=='send') exit();
		$formid= intval($_POST['formid']);

		$pageid = intval($_POST['pageid']);
		$nm     = htmlspecialchars(trim(urldecode($_POST['nm'])));
		$phn    = htmlspecialchars(trim(urldecode($_POST['phn'])));
		$em     = htmlspecialchars(trim(urldecode($_POST['em'])));
		$prim   = htmlspecialchars(trim(urldecode($_POST['prim'])));
		$otz    = htmlspecialchars(trim(urldecode($_POST['otz'])));
		$otz    = str_replace("\r", '', $otz);
		$otz    = str_replace("\n", '<br />', $otz);

		if($phn || $em)
		{
			$subjects= array(
				/*0*/ '',
				/*1*/ 'Обратный звонок',
				/*2*/ 'Нужен дизайн макет',
				/*3*/ 'Есть дизайн макет',
				/*4*/ 'Отзыв',
				/*5*/ 'Расчет стоимости',
				/*6*/ 'Обратный звонок',
			);
			if( ! $subjects[$formid]) $subjects[$formid]= 'Письмо';

			$subject= $subjects[$formid].' — '.$nc_core->url->get_parsed_url('host');
			$mail= '<h2>'.$subject.'</h2>';

			if($pageid) $mail .= '<p><a target="_blank" href="'.nc_folder_url($pageid).'">Отправлено со страницы</a></p>';
			if($formid!=1 && $formid!=6) $mail .= '<p><b>Имя:</b> '.$nm.'</p>';
			$mail .= '<p><b>Телефон:</b> +'.$phn.'</p>';
			if($formid!=1 && $formid!=6) $mail .= '<p><b>E-mail:</b> '.$em.'</p>';
			if($formid!=6) $mail .= '<p><b>Примечание:</b> '.$prim.'</p>';
			if($formid==4) $mail .= '<p><b>Отзыв:</b><br />'.$otz.'</p>';

			if($formid==2 || $formid==3 || $formid==5)
			{
				if(is_array($_SESSION['megaform']['files']) && count($_SESSION['megaform']['files']))
				{
					$mail .= '<p><b>Файлы:</b><br />';
					foreach($_SESSION['megaform']['files'] AS $row)
					{
						$fs= $row[1]; $sn='байт';
						if($fs>1024){ $fs/=1024; $sn='Кб'; }
						if($fs>1024){ $fs/=1024; $sn='Мб'; }
						$fs= round($fs,1);
						$mail .= '&mdash; <a target="_blank" href="https://'.$nc_core->url->get_parsed_url('host') .'/assets/tmp/forms/'.$_SESSION['megaform']['code'].'/'.$row[2].'">'.$row[0].'</a>, '.$fs.' '.$sn.'<br />';
					}
					$mail .= '</p>';
				}
			}

			$subject= $nc_core->db->escape($subject);
			$body= $nc_core->db->escape($mail);
			$nc_core->db->query("INSERT INTO BN_Queue_Mail SET formid='{$formid}', `to`='[manager1]', subject='{$subject}', body='{$body}', dt=".time());
		}else{
			$pupf_err= '<b>Не отправлено!</b><br />Введите номер телефона.';
			if($formid==6) $pupf_err= 'Ошибка!';
		}
		if($pupf_err) print '{"result":"err","answer":"'.$pupf_err.'"}';
		else{
			if($formid==6) print '{"result":"ok","answer":"Отправлено!"}';
				else print '{"result":"ok","answer":"<b>Отправлено!</b><br />В&nbsp;самое&nbsp;ближайшее&nbsp;время с&nbsp;Вами&nbsp;свяжется наш&nbsp;специалист."}';
		}

		exit();
	}
	
	if($action=='megaform_fileChunkUpload')
	{
		$fs= intval($_GET['fs']);
		$ii= intval($_GET['ii']);
		$cc= intval($_GET['cc']);
		$kk= intval($_GET['kk']);
		$chunk= $_POST['chunkblob'];
		if(strpos($chunk,',') !== false) $chunk= substr($chunk,strpos($chunk,',')+1);
		$chunk= base64_decode($chunk);
		
		if( ! $_SESSION['megaform']['code'])
		{
			$code= 'w'.(date('Y')-2015).date('mdH');
			$num= 0;
			do{
				$num++;
			}while(file_exists($nc_core->DOCUMENT_ROOT.'/assets/tmp/forms/'.$code.$num.'/'));
			$code .= $num;
			mkdir($nc_core->DOCUMENT_ROOT.'/assets/tmp/forms/'.$code.'/', 0777, true);
			if(file_exists($nc_core->DOCUMENT_ROOT.'/assets/tmp/forms/'.$code.'/')) $_SESSION['megaform']['code']= $code;
				else exit();
		}
		$code= $_SESSION['megaform']['code'];

		$tmpfolder= '/assets/tmp/forms/'.$code.'/';
		if( ! file_exists($nc_core->DOCUMENT_ROOT.$tmpfolder)) mkdir($nc_core->DOCUMENT_ROOT.$tmpfolder, 0777, true);
		
		$fn= trim(urldecode($_GET['fn']));
		$fn= generAlias($fn);
		$fn_bez_fs= $fn;
		$fn= $fs.'_'.$fn;
		
		clearstatcache();
		
		$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$kk, 'w');
		fwrite($fo, $chunk);
		fclose($fo);
		
		$chunkssizesum= 0;
		for($ww=0; $ww<$cc; $ww++)
		{
			$chunkssizesum += filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
		}
		
		if($chunkssizesum<$fs)
		{
			print $chunkssizesum;
		}else{
			print 'lastchunk';
			
			$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn, 'w');
			if($fo)
			{
				flock($fo, LOCK_EX);
				for($ww=0; $ww<$cc; $ww++)
				{
					$fo2= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww, 'r');
					$contents= fread($fo2, filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww));
					fwrite($fo, $contents);
					fclose($fo2);
					unlink($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
				}
				fclose($fo);
			}
			
			if(filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn)==$fs)
			{
				$_SESSION['megaform']['files'][md5($fn)]= array($fn_bez_fs,$fs,$fn);
			}
		}
		exit();
	}

	if($action=='megaform_Files')
	{
		print megaForm_Files();
		exit();
	}

	if($action=='megaform_FileDelete')
	{
		$file= $_SESSION['megaform']['files'][$_GET['id']];
		unset($_SESSION['megaform']['files'][$_GET['id']]);
		unlink($nc_core->DOCUMENT_ROOT.'/assets/tmp/forms/'.$_SESSION['megaform']['code'].'/'.$file[2]);
		print megaForm_Files();
		exit();
	}








	
	if($action=='shopBasketFileDelete')
	{
		$id= intval($_GET['id']);
		$code= getShopOrderStatus_1();
		$nc_core->db->query("UPDATE BN_Shop_Order_Files SET enabled='n' WHERE code='{$code}' AND id={$id} LIMIT 1");
		print shopBasketPage_Data(true);
	}

	if($action=='shopBasketFiles')
	{
		print shopBasketPage_Data(true);
	}
	
	if($action=='fileChunkUpload')
	{
		$fs= intval($_GET['fs']);
		$ii= intval($_GET['ii']);
		$cc= intval($_GET['cc']);
		$kk= intval($_GET['kk']);
		$chunk= $_POST['chunkblob'];
		if(strpos($chunk,',') !== false) $chunk= substr($chunk,strpos($chunk,',')+1);
		$chunk= base64_decode($chunk);
		
		// usleep($kk*300000); //0.3 секунды
		// usleep($kk*10000); //0.01 секунды
		
		$code= getShopOrderStatus_1();
		
		$tmpfolder= '/assets/tmp/orders/'.$code.'/';
		if( ! file_exists($nc_core->DOCUMENT_ROOT.$tmpfolder)) mkdir($nc_core->DOCUMENT_ROOT.$tmpfolder, 0777, true);
		
		$fn= trim(urldecode($_GET['fn']));
		$fn= generAlias($fn);
		$fn= $fs.'_'.$fn;
		
		clearstatcache();
		
		$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$kk, 'w');
		fwrite($fo, $chunk);
		fclose($fo);
		
		$chunkssizesum= 0;
		for($ww=0; $ww<$cc; $ww++)
		{
			$chunkssizesum += filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
		}
		
		if($chunkssizesum<$fs)
		{
			print $chunkssizesum;
		}else{
			print 'lastchunk';
			
			$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn, 'w');
			if($fo)
			{
				flock($fo, LOCK_EX);
				for($ww=0; $ww<$cc; $ww++)
				{
					$fo2= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww, 'r');
					$contents= fread($fo2, filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww));
					fwrite($fo, $contents);
					fclose($fo2);
					unlink($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
				}
				fclose($fo);
			}
			
			if(filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn)==$fs)
			{
				$row= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order_Files WHERE code='{$code}' AND `name`='{$fn}' LIMIT 1", ARRAY_A);
				if(is_array($row) && count($row))
				{
					$nc_core->db->query("UPDATE BN_Shop_Order_Files SET location='server', enabled='y' WHERE code='{$code}' AND `name`='{$fn}' LIMIT 1");
				}else{
					$nc_core->db->query("INSERT INTO BN_Shop_Order_Files SET code='{$code}', `name`='{$fn}'");
				}
				
				$diskpath= '/ORDERS/'.$code.'/';
				$diskresponse= disk('resources/?path='.urlencode($diskpath));
				if($diskresponse[0]['http_code']!=200)
				{
					$diskresponse= disk('resources/?path='.urlencode($diskpath), 'PUT');
					$diskresponse= disk('resources/publish/?path='.urlencode($diskpath), 'PUT');
					$diskresponse= disk('resources/?path='.urlencode($diskpath));
					if($diskresponse[0]['http_code']==200)
					{
						$diskresponse_arr= json_decode($diskresponse[1], true);
						$public_url= $nc_core->db->escape($diskresponse_arr['public_url']);
						$nc_core->db->query("UPDATE BN_Shop_Order SET files='{$public_url}' WHERE code='{$code}' LIMIT 1");
					}
				}
				$diskresponse= disk('resources/?path='.urlencode($diskpath.$fn));
				if($diskresponse[0]['http_code']!=200)
				{
					$diskresponse= disk('resources/upload/?overwrite=true&path='.urlencode($diskpath.$fn));
					$diskresponse_arr= json_decode($diskresponse[1], true);
					$diskuploadhref= $diskresponse_arr['href'];
					$diskresponse= disk($diskuploadhref, 'PUT', true, $nc_core->DOCUMENT_ROOT.$tmpfolder.$fn);
				}
			}
		}
	}



	
	if($action=='scalc_userfile')
	{
		shopBasketCheck_Table();
		$code= getShopOrderStatus_1();

		$fs= intval($_GET['fs']);
		$cc= intval($_GET['cc']);
		$kk= intval($_GET['kk']);
		$chunk= $_POST['chunkblob'];
		if(strpos($chunk,',') !== false) $chunk= substr($chunk,strpos($chunk,',')+1);
		$chunk= base64_decode($chunk);
		$tmpfolder= '/assets/files/oboiusersfiles/'.$code.'/';
		if( ! file_exists($nc_core->DOCUMENT_ROOT.$tmpfolder.'crop/')) mkdir($nc_core->DOCUMENT_ROOT.$tmpfolder.'crop/', 0777, true);
		
		$fn= trim(urldecode($_GET['fn']));

		$ext= substr($fn, strpos($fn,'.'));
		if(stripos('/.jpg/.jpeg/.png/', '/'.$ext.'/')===false)
		{
			print '{"result":"err","text":"Неверный формат изображения!"}';
			exit();
		}
		if($fs>1024*1024*15)
		{
			print '{"result":"err","text":"Размер изображения не должен превышать 15 Мб"}';
			exit();
		}

		$fn= generAlias($fn);
		$fn= $fs.'_'.$fn;
		
		clearstatcache();
		
		$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$kk, 'w');
		fwrite($fo, $chunk);
		fclose($fo);
		
		$chunkssizesum= 0;
		for($ww=0; $ww<$cc; $ww++)
		{
			$chunkssizesum += filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
		}
		
		if($chunkssizesum<$fs)
		{
			print '{"result":"ok","response":"nextchunk"}';
		}else{
			print '{"result":"ok","response":"lastchunk","file":"'.substr($tmpfolder,1).$fn.'"}';
			
			$fo= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn, 'w');
			if($fo)
			{
				flock($fo, LOCK_EX);
				for($ww=0; $ww<$cc; $ww++)
				{
					$fo2= fopen($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww, 'r');
					$contents= fread($fo2, filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww));
					fwrite($fo, $contents);
					fclose($fo2);
					unlink($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn.'_'.$ww);
				}
				fclose($fo);
			}
			if(filesize($nc_core->DOCUMENT_ROOT.$tmpfolder.$fn)==$fs)
			{
				$_SESSION['oboiusersfiles']= substr($tmpfolder,1).$fn;
			}
		}
	}








	if(($sub==248 && $_POST['action']=='checkOrder') || ($sub=250 && $_POST['action']=='paymentAviso'))
	{
		$postaction= $_POST['action'];

		$code= $nc_core->db->escape($_POST['orderNumber']);
		$row= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order WHERE code='{$code}' AND status>=5 AND status<=60 AND payment_dt=0 LIMIT 1",ARRAY_A);
		if(is_array($row) && count($row))
		{
			$orderinfo= $row[0];
		}else{
			$code= false;
		}

		$md5= strtoupper($_POST['md5']);
		$mymd5= strtoupper(md5($postaction.";{$orderinfo[itogo]};{$_POST[orderSumCurrencyPaycash]};{$_POST[orderSumBankPaycash]};".YaKa('shopid').";{$_POST[invoiceId]};{$_POST[customerNumber]};".YaKa('shoppassword')));

		header('Content-Type: application/xml');

		if($mymd5==$md5)
		{
			$responsecode= '0';

			if($postaction=='paymentAviso')
			{
				$logs= "";
				if($orderinfo['payment']!='fizlico') $logs .= "\n".date('d.m.Y, H:i')." | Изменен способ оплаты. Оплата на сайте";
				$logs .= "\n".date('d.m.Y, H:i')." | Оплачен онлайн";
				$logs= $nc_core->db->escape($logs);
				$nc_core->db->query("UPDATE BN_Shop_Order SET payment='fizlico', payment_dt=".time().", logs=CONCAT(logs,'{$logs}')
					WHERE code='{$code}' LIMIT 1");

				$body= '<p>Заказ '.substr($code,1).' &mdash; оплачен онлайн!</p>';
				$subject= 'Оплачен онлайн! Заказ '.substr($code,1);
				$nc_core->db->query("INSERT INTO BN_Queue_Mail SET orderCode='{$code}', `to`='[manager666]', subject='{$subject}', body='{$body}', dt=".time());
			}

		}else $responsecode= '1';

		$date= new DateTime();
		$date= $date->format("Y-m-d")."T".$date->format("H:i:s").".000".$date->format("P");
		print '<?xml version="1.0" encoding="UTF-8"?><'.$postaction.'Response performedDatetime="'.$date.'" code="'.$responsecode.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.YaKa('shopid').'"/>';

		$invoiceId= $nc_core->db->escape($_POST['invoiceId']);
		$params= serialize($_POST);
		$params= $nc_core->db->escape($params);
		$orderSumAmount= $nc_core->db->escape($_POST['orderSumAmount']);
		$nc_core->db->query("INSERT INTO BN_YaKa_Logs SET orderCode='{$code}', invoiceId='{$invoiceId}', action='{$postaction}', sum='{$orderSumAmount}', mysum='{$orderinfo[itogo]}', md5='{$md5}', mymd5='{$mymd5}', dt=".time().", params='{$params}'");

		exit();
	}





	if($action=='citySearch')
	{
		$city= $nc_core->db->escape(trim(urldecode($_GET['city'])));
		$city2= keyboardLayout($city);
		$rr= $nc_core->db->get_results("SELECT id, City FROM BN_CDEK_City_Points WHERE (City LIKE '%{$city}%' OR City LIKE '%{$city2}%') AND enabled='y' GROUP BY City ORDER BY City", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			foreach($rr AS $row)
			{
				$pp .= '<div class="hct_itm hct_city '.($row['City']==$_SESSION['mycity']['city']?'hct_city_a':'').'" data-id="'.$row['id'].'"><span>'.$row['City'].'</span></div>';
			}
		}else{
			$pp .= '<div class="hcts_err">Город не найден! Проверьте запрос.</div>';
		}
		print $pp;
	}

	if($action=='cityConfirmed')
	{
		$ip= $nc_core->db->escape($_SERVER['REMOTE_ADDR']);
		$nc_core->db->query("UPDATE BN_City_User SET confirmed='y' WHERE ip='{$ip}' LIMIT 1");
	}

	if($action=='setCity')
	{
		$flag= false;
		$id= intval($_GET['id']);
		if($id)
		{
			$row= $nc_core->db->get_results("SELECT City, CityAlias FROM BN_CDEK_City_Points WHERE id='{$id}' AND enabled='y' LIMIT 1", ARRAY_A);
			if(is_array($row) && count($row))
				$flag= setCity($row[0]['City'], false, true);
		}
		if($flag)
		{
			if($_GET['page']==182) print '/contacts/'.$row[0]['CityAlias'].'/'; else print 'y';
		}else print 'n';
	}


	if($action=='shopAction')
	{
		$id= intval($_GET['id']);
		$prm= $_GET['p'];
		$vl= $_GET['vl'];
		$code= getShopOrderStatus_1();

		if($_GET['a2']=='count')
		{
			$count= intval($vl);
			if($count<1) $count= 1;
			if($count>99999) $count= 99999;
			$nc_core->db->query("UPDATE BN_Shop_Basket SET `count`={$count} WHERE code='{$code}' AND id={$id} LIMIT 1");
			shopBasketCheck_Sum();
			print shopBasketPage_Items();
		}

		if($_GET['a2']=='delete')
		{
			$nc_core->db->query("DELETE FROM BN_Shop_Basket WHERE code='{$code}' AND id={$id} LIMIT 1");
			shopBasketCheck_Sum();
			print shopBasketPage_Items();
		}

		if($_GET['a2']=='clear')
		{
			$nc_core->db->query("DELETE FROM BN_Shop_Basket WHERE code='{$code}'");
			shopBasketCheck_Sum();
			print shopBasketPage_Items();
		}

		if($_GET['a2']=='savedata')
		{
			$prm= trim(urldecode($_GET['p']));
			$val= trim(urldecode($_GET['vl']));
			if($prm=='phone') $val= '+'.preg_replace("/[^0-9]/",'',$val);
			$prm= $nc_core->db->escape($prm);
			$val= $nc_core->db->escape($val);
			$nc_core->db->query("UPDATE BN_Shop_Order SET `{$prm}`='{$val}' WHERE code='{$code}' LIMIT 1");
		}

		if($_GET['a2']=='get_itogo')
		{
			print shopBasketPage_Checkout();
		}

		if($_GET['a2']=='set_payment')
		{
			$nc_core->db->query("UPDATE BN_Shop_Order SET payment='{$prm}' WHERE code='{$code}' LIMIT 1");
		}

		if($_GET['a2']=='set_delivery')
		{
			if($prm=='rnd')
			{
				$delivery= 'pvz';
				$address= 'ул. Социалистическая, 103 — угол Газетного';
				$pvz= '0';
			}elseif($prm=='address'){
				$delivery= 'address';
				$address= '';
				$pvz= '0';
			}else{
				$delivery= 'pvz';
				$address= '';
				$pvz= intval($prm);
				$row= $nc_core->db->get_results("SELECT Address FROM BN_CDEK_City_Points WHERE id='{$pvz}' AND enabled='y' LIMIT 1", ARRAY_A);
				if(is_array($row) && count($row)) $address= $row[0]['Address'];
				else $pvz= false;
			}
			if($pvz!==false)
			{
				$address= $nc_core->db->escape($address);
				$nc_core->db->query("UPDATE BN_Shop_Order SET ability_deliver='{$abilityDeliver}', cost_delivery='{$deliveryCost}', delivery='{$delivery}', address='{$address}', pvz='{$pvz}', delivery_days='{$deliveryDays}' WHERE code='{$code}' LIMIT 1");
			}
			shopBasketCheck_Sum();
		}

		if($_GET['a2']=='shopbasketcheckout')
		{
			shopBasketCheck_Table();
			shopBasketCheck_Items();
			shopBasketCheck_Sum();

			$code= getShopOrderStatus_1();

			$fio         = $nc_core->db->escape(trim(urldecode($_POST['fio'])));
			$email       = $nc_core->db->escape(strtolower(trim(urldecode($_POST['email']))));
			$useraddress = $nc_core->db->escape(trim(urldecode($_POST['useraddress'])));
			$message     = $nc_core->db->escape(trim(urldecode($_POST['message'])));
			$phone       = $_POST['phone'];
			$phone       = '+'.preg_replace("/[^0-9]/",'',$phone);

			$nc_core->db->query("UPDATE BN_Shop_Order SET fio='{$fio}', email='{$email}', phone='{$phone}', useraddress='{$useraddress}', message='{$message}' WHERE code='{$code}' LIMIT 1");

			$order= getShopOrderStatus_1('*');

			$itogo= trim(urldecode($_GET['itogo']));
			if(floatval($itogo) != floatval($order['itogo'])) $errors .= '<div>'.icon('warning').'&nbsp; Изменилась сумма заказа! Проверьте данные</div>';

			if( ! preg_match("/^\+7[0-9]{10}$/", $order['phone'])) $errors .= '<div>'.icon('warning').'&nbsp; Введите корректный номер телефона</div>';

			if( ! preg_match("/^[a-z0-9-_\.]{1,}@[a-z0-9-\.]{1,}\.[a-z]{2,10}$/", $order['email'])) $errors .= '<div>'.icon('warning').'&nbsp; Введите корректный E-mail</div>';

			if($order['delivery']=='address' && ! $order['useraddress']) $errors .= '<div>'.icon('warning').'&nbsp; Введите адрес доставки</div>';

			if($order['payment']=='n') $errors .= '<div>'.icon('warning').'&nbsp; Выберите способ оплаты</div>';

			if( ! $order['city']) $errors .= '<div>'.icon('warning').'&nbsp; Выберите город доставки</div>';

			if($order['delivery']=='pvz' && $order['city']!='Ростов-на-Дону' && ! $order['pvz']) $errors .= '<div>'.icon('warning').'&nbsp; Выберите способ доставки</div>';

			if($order['city']!='Ростов-на-Дону')
			{
				$cityqq= $nc_core->db->escape($order['city']);
				if($order['delivery']=='pvz') $qq= "AND id={$order['pvz']}";
				$row= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE City='{$cityqq}' {$qq} AND enabled='y' LIMIT 1",ARRAY_A);
				if( ! $row) $errors .= '<div>'.icon('warning').'&nbsp; Выберите пункт выдачи заказа</div>';
			}

			$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Basket WHERE code='{$code}'",ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				foreach($rr AS $row)
				{
					if($row['enabled']!='y')
					{
						$errors .= '<div>'.icon('warning').'&nbsp; Уберите из корзины недействительный товар</div>';
						break;
					}
				}
			}else $errors .= '<div>'.icon('warning').'&nbsp; Наполните корзину</div>';

			if($errors) print $errors;
			else{
				srand(time());
				$secret .= $code.(substr($code,1)/3.1415).time().$order['itogo'].$order['user'].$order['phone'].$order['email'].$order['pvz'].$order['useraddress'].rand(100,999);
				$secret= md5($secret);

				$checkoutresult= $nc_core->db->query("UPDATE BN_Shop_Order SET status='5', logs=CONCAT(logs,'\n".date('d.m.Y, H:i')." | Заказ оформлен'),
					checkout='".time()."', secret='{$secret}'
					WHERE code='{$code}' AND status='1' LIMIT 1");
				if( ! $checkoutresult) exit();

				print 'go';

				$message= str_replace("\n",'<br />',$order['message']);

				$styles .= '<style>
					.tbl1 {
						border: none;
						width: 70%;
					}
						.tbl1 tr {
						}
							.tbl1 tr td {
								vertical-align: top;
								padding: 0;
								padding-bottom: 5px;
							}
								.tbl1 tr td >div {
									border-radius: 15px;
									background: #ecf1f5;
									padding: 7px 16px;
								}
									.tbl1 tr td >div.white {
										background: none;
										padding-top: 0;
									}
								.tbl1 tr td.lab {
									text-align: right;
									padding: 7px 20px 0 0;
								}
								.tbl1 tr td.bigbig2 {
									padding-bottom: 10px;
									padding-top: 7px;
								}
								.tbl1 tr td.bigbig {
									font-size: 150%;
									padding-left: 16px;
									padding-right: 16px;
								}
					.tbl2 {
						border: none;
						width: 100%;
						margin-top: 20px;
					}
						.tbl2 tr {
						}
							.tbl2 tr td {
								padding: 0 2px;
							}
								.tbl2 tr.tit td {
								}
									.tbl2 tr.tit td >div {
										border-radius: 15px;
										background: #ecf1f5;
										padding: 7px 14px;
										font-size: 90%;
										color: #777;
									}
								.tbl2 tr.row td {
									padding: 10px 16px;
									border-bottom: 1px solid #ecf1f5;
								}
								.tbl2 tr.itog td {
									text-align: right;
									padding: 15px 16px 0;
									font-size: 130%;
									white-space: nowrap;
								}

							.tbl2 tr .npp {
								text-align: center;
							}
							.tbl2 tr .name {
							}
							.tbl2 tr .sht {
								text-align: center;
								white-space: nowrap;
							}
							.tbl2 tr .price {
								text-align: right;
								white-space: nowrap;
							}
							.tbl2 tr .cc {
								text-align: center;
							}
							.tbl2 tr .sum {
								text-align: right;
								white-space: nowrap;
							}
				</style>';

				$mail1 .= '<table class="tbl1">
					<tr><td class="lab bigbig2">Код заказа</td><td class="bigbig">'.substr($code,1).'</td></tr>
					<tr><td class="lab"></td><td><div class="white">'.date('d.m.Y, H:i').'</div></td></tr>

					<tr><td class="lab">Имя</td><td><div>'.$order['fio'].'</div></td></tr>

					<tr><td class="lab">Телефон</td><td><div>+7 '.substr($order['phone'],2,3).' '.substr($order['phone'],5,3).'-'.substr($order['phone'],8,4).'</div></td></tr>

					<tr><td class="lab">E-mail</td><td><div><a target="_blank" href="mailto:'.$order['email'].'">'.$order['email'].'</a></div></td></tr>';

				if($message) $mail1 .= '<tr><td class="lab">Сообщение</td><td><div>'.$message.'</div></td></tr>';

				$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order_Files WHERE code='{$code}' ORDER BY enabled, location DESC",ARRAY_A);
				if(is_array($rr) && count($rr))
				{
					foreach($rr AS $row)
					{
						$fn= explode('_',$row['name']);
						if($row['enabled']=='y') $files_user .= '<div>&mdash; '.$fn[1].'</div>';
						$files_admin .= '<div>'.($row['enabled']=='y'?'+':'-').' ';
						if($row['location']=='disk' && $order['files']) $tmp= $order['files'];
							else $tmp= 'http://'.$nc_core->url->get_parsed_url('host').'/assets/tmp/orders/'.$code.'/'.$row['name'];
						$files_admin .= '<a target="_blank" href="'.$tmp.'">'.$row['name'].'</a>';
						$files_admin .= ($row['location']=='disk'?' на диске':' на сервере');
						$files_admin .= ($row['enabled']!='y'?' &mdash; удален':'').'</div>';
					}
				}
				if($files_user) $mail2 .= '<tr><td class="lab">Файлы</td><td><div>'.$files_user.'</div></td></tr>';
				if($files_admin) $mail3 .= '<tr><td class="lab">Файлы</td><td><div>'.$files_admin.'</div></td></tr>';

				$mail4 .= '<tr><td class="lab">Город</td><td><div>'.$order['city'].'</div></td></tr>';
				$mail4 .= '<tr><td class="lab">Доставка</td><td><div>'.($order['delivery']=='address'?'курьером по адресу':'самовывоз из пункта выдачи заказов').'</div></td></tr>';
				if($order['delivery']=='pvz') $mail4 .= '<tr><td class="lab">Адрес ПВЗ</td><td><div>'.$order['address'].'</div></td></tr>';
				if($order['delivery']=='address') $mail4 .= '<tr><td class="lab">Адрес доставки</td><td><div>'.$order['useraddress'].'</div></td></tr>';

				$mail4 .= '<tr><td class="lab">Способ оплаты</td><td><div>';
				if($order['payment']=='fizlico')
				{
					$mail4 .= 'как физическое лицо';
					$mail4 .= '<br /><a href="'.nc_folder_url(249).'?s='.$secret.'&c='.$code.'&a=payment">Оплатить сейчас на сайте</a>';
				}
				if($order['payment']=='urlico') $mail4 .= 'счет на юридическое лицо';
				if($order['payment']=='nalik') $mail4 .= 'при получении';
				if($order['payment']=='later') $mail4 .= 'определюсь позже';
				$mail4 .= '<br />(<a target="_blank" href="'.nc_folder_url(249).'?s='.$secret.'&c='.$code.'&a=change">выбрать другой способ оплаты</a>)</div></td></tr>';

				$mail4 .= '</table>';

				$mail5 .= '<h2>Заказ</h2>';

				$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Basket WHERE code='{$code}' AND enabled='y'",ARRAY_A);
				if(is_array($rr) && count($rr))
				{
					$mail5 .= '<table class="tbl2">
						<tr class="tit">
							<td class="npp"><div>#</div></td>
							<td class="name"><div>Наименование</div></td>
							<td class="sht"><div>Комплект</div></td>
							<td class="price"><div>Цена комплекта</div></td>
							<td class="cc"><div>Кол-во комплектов</div></td>
							<td class="sum"><div>Сумма</div></td>
						</tr>';
					foreach($rr AS $key=>$row)
					{
						$category= str_replace("\n", " / ", $row['category']);
						$params= unserialize($row['params']);
						$options= '';
						if(is_array($params['params']) && count($params['params']))
						{
							foreach($params['params'] AS $prm)
							{
								$options .= '<div>&mdash; '.$prm.'</div>';
							}
						}
						if(is_array($params['options']) && count($params['options']))
						{
							foreach($params['options'] AS $opt)
							{
								$options .= '<div>&mdash; ';
								if($opt['subname']) $options .= $opt['subname'].' ';
								$options .= $opt['name'].' ';
								$options .= '</div>';
							}
						}

						$mail5 .= '<tr class="row">
							<td class="npp">'.($key+1).'</td>

							<td class="name">
								<div>'.$category.'</div>
								<div>'.$row['title'] .($params['description']?' ('.$params['description'].')':''). '</div>
								<div>'.$options.'</div>';

						if($params['image'])
						{
							$mail5 .= '<div><a target="_blank" href="https://'.$nc_core->url->get_parsed_url('host').'/'.$params['imagecrop'].'">Изображение</a></div>';
							$mail5 .= '<div><a target="_blank" href="https://'.$nc_core->url->get_parsed_url('host').'/'.$params['image'].'">Изображение</a></div>';
						}

						$mail5 .= '</td>

							<td class="sht">'.$row['ed'].'</td>

							<td class="price"><span>'.Price($row['price']).'</span>&nbsp;<span>руб.</span></td>

							<td class="cc">'.$row['count'].'</td>

							<td class="sum"><span>'.Price($row['price']*$row['count']).'</span>&nbsp;<span>руб.</span></td>
						</tr>';
					}
					$mail5 .= '<tr class="itog">
							<td colspan="2"></td>
							<td colspan="2"></td>
							<td colspan="2">'.Price($order['sum']).'&nbsp;руб.</td>
						</tr>
						<tr class="itog">
							<td colspan="2"></td>
							<td colspan="2">Стоимость доставки</td>
							<td colspan="2">'.($order['ability_deliver']=='y' ? ''.Price($order['cost_delivery']).'&nbsp;руб.' : 'Наши менеджеры рассчитают стоимость доставки вручную!').'</td>
						</tr>
						<tr class="itog">
							<td colspan="2"></td>
							<td colspan="2"><b>Сумма заказа</b></td>
							<td colspan="2"><b>'.Price($order['itogo']).'&nbsp;руб.</b></td>
						</tr>
					</table>';
				}

				$styles= $nc_core->db->escape($styles);
				$subject= $nc_core->db->escape('Заказ '.substr($code,1).' — оформлен');
				$emailqq= $nc_core->db->escape($order['email']);

				$body= $nc_core->db->escape($mail1.$mail2.$mail4.$mail5);
				$nc_core->db->query("INSERT INTO BN_Queue_Mail SET orderCode='{$code}', `to`='{$emailqq}', subject='{$subject}', body='{$body}', styles='{$styles}', dt=".time());

				$body= $nc_core->db->escape($mail1.$mail3.$mail4.$mail5);
				$nc_core->db->query("INSERT INTO BN_Queue_Mail SET orderCode='{$code}', `to`='[manager666]', subject='{$subject}', body='{$body}', styles='{$styles}', dt=".time());
			}
		}
	}

	if($action=='shopAddToBasket')
	{
		shopBasketCheck_Table();
		shopBasketCheck_Items();
		$code= getShopOrderStatus_1();

		$catid          = intval($_GET['catid']);
		$kbid           = intval($_POST['kbid']);
		$edition        = intval($_GET['edition']);
		$options        = $_POST['option'];
		$options_values = $_POST['values'];

		if($catid && $edition)
		{
			if($catid==234 || $catid==235)
			{
				if($kbid)
				{
					$row= $nc_core->db->get_results("SELECT clrs_name FROM Message171 WHERE Subdivision_ID={$catid} AND Message_ID={$kbid} AND Checked=1 LIMIT 1",ARRAY_A);
					if(is_array($row) && count($row))
					{
						$params['params'][]= 'Блок: '.$row[0]['clrs_name'];
					}else exit();
				}else exit();
			}

			$row= $nc_core->db->get_results("SELECT cc.id AS ccid, ee.id AS eeid, ee.edition, cc.name, cc.description, ee.price, ee.range FROM BN_PG_Catalog AS cc
				INNER JOIN BN_PG_Catalog_Edition AS ee ON ee.idi=cc.id
					WHERE cc.parent={$catid} AND ee.id={$edition} AND ee.enabled='y' AND cc.enabled='y' LIMIT 1", ARRAY_A);
			if(is_array($row) && count($row))
			{
				$row= $row[0];

				$uniq= $row['name'].'_';

				$options_value= 0;
				if($row['range']=='y')
				{
					if(is_array($options_values) && count($options_values))
					{
						foreach($options_values AS $val)
						{
							if($val['c'])
							{
								$options_value= intval($val['c']);
							}elseif($val['w'] && $val['h']){
								$val['w']= intval($val['w']);
								$val['h']= intval($val['h']);
								$params['params'][]= 'Размер '.$val['w'].' x '.$val['h'].' см';
								$val['w'] /= 100;
								$val['h'] /= 100;
								$options_value= round($val['w'] * $val['h']);
							}
						}
					}
					$uniq .= $options_value.'_';

					$ed= $options_value.' '.(strpos($row['edition'], 'м2')!==false ? 'м2' : 'шт.');

					if( ! $options_value) exit();

				}else{
					$uniq .= $row['edition'].'_';
					$ed= $row['edition'];
				}

				$params['description']= $row['description'];

				$options= getOptions($catid, $options);
				if( ! $options) exit();

				$markup= $options[0];
				$markup= changeMarkupBySize($markup, $row['description']);

				$params['options']= $options[1];
				if(is_array($params['options']) && count($params['options']))
				{
					foreach($params['options'] AS $row2)
					{
						$uniq .= $row2['type'] .'_'. $row2['name'] .'_'. $row2['subname'].'_';
					}
				}

				$price= getShopBasketItemPrice($catid, $row['ccid'], $edition, $markup, $options_value);

				$category= getIdLvl(183, $catid, false, 'Subdivision_Name');
				if(is_array($category) && count($category))
				{
					foreach($category AS $key2=>$row2)
					{
						if($key2>=2) $categorytxt .= (!empty($categorytxt)?"\n":'').$row2['Subdivision_Name'];
					}
				}

				$uniq= md5($uniq);

				$row['name']= $nc_core->db->escape($row['name']);
				$ed= $nc_core->db->escape($ed);
				$params= serialize($params);
				$params= $nc_core->db->escape($params);
				$categorytxt= $nc_core->db->escape($categorytxt);
				
				$rr= $nc_core->db->get_results("SELECT id FROM BN_Shop_Basket WHERE code='{$code}' AND itemid={$row[ccid]} AND uniq='{$uniq}' LIMIT 1",ARRAY_A);
				if(is_array($rr) && count($rr))
				{
					$nc_core->db->query("UPDATE BN_Shop_Basket SET count=count+1 WHERE id={$rr[0][id]} LIMIT 1");
				}else{
					$nc_core->db->query("INSERT INTO BN_Shop_Basket SET code='{$code}', catid={$catid}, category='{$categorytxt}', itemid={$row[ccid]}, title='{$row[name]}', count=1,
						edid={$row[eeid]}, ed='{$ed}', price='{$price}', params='{$params}', dt=".time().", uniq='{$uniq}'");
				}
			}
		}
		shopBasketCheck_Sum();
	}

	if($action=='shopAddToBasketOboi')
	{
		shopBasketCheck_Table();
		shopBasketCheck_Items();
		$code= getShopOrderStatus_1();

		$width            = intval($_POST['width']);
		$height           = intval($_POST['height']);
		$typeprint        = ($_POST['typeprint'] == 2?2:1);
		$itemid           = $_POST['itemid'];
		$idwallp          = intval($_POST['idwallp']);
		$kub_ll           = intval($_POST['kub_ll']);
		$kub_tt           = intval($_POST['kub_tt']);
		$kub_ww           = intval($_POST['kub_ww']);
		$kub_hh           = intval($_POST['kub_hh']);

		if($itemid=='w0000000')
		{
			$designfile= $_SESSION['oboiusersfiles'];
		}else{
			$itemid= $nc_core->db->escape($itemid);
			$row= $nc_core->db->get_results( "SELECT CatImage FROM Message181 WHERE CatArticle='{$itemid}' AND Checked=1 LIMIT 1", ARRAY_A);
			if(is_array($row) && count($row))
			{
				$designfile= explode(':', $row[0]['CatImage']);
				$designfile= 'netcat_files/'.$designfile[3];
			}
		}
		$designfile_crop= ImgCrop72($designfile,652,503,true);

		$row= $nc_core->db->get_results("SELECT * FROM Classificator_CatTextures WHERE CatTextures_ID={$idwallp} AND Checked=1 LIMIT 1", ARRAY_A);
		if(is_array($row) && count($row)) $wallp= $row[0]['CatTextures_Name'];

		if( ! file_exists($nc_core->DOCUMENT_ROOT.'/'.$designfile))
			$error .= '<div>'.icon('warning').' Выберите изображение из каталога или загрузите свое</div>';
		if( ! $width || ! $height) $error .= '<div>'.icon('warning').' Задайте размер</div>';

		if($error)
		{
			print '{"result":"err","txt":"'.addslashes($error).'<br />"}';
			exit();
		}

		$pricebase= ($typeprint==2 ? 990 : 1290);
		$area= $width * $height;
		if($area<10000) $area= 10000;
		$price= round($area /10000 *$pricebase);

		$uniq= 'Фотообои'.'_'.$width.'_'.$height.'_'.$designfile.'_'.$typeprint.'_'.$idwallp.'_'.$kub_ll.'_'.$kub_tt.'_'.$kub_ww.'_'.$kub_hh.'_';
		$uniq= md5($uniq);

		$ext= substr($designfile, strpos($designfile,'.'));
		$cropfolder= 'assets/files/oboiusersfiles/'.$code.'/crop/';
		if( ! file_exists($nc_core->DOCUMENT_ROOT.$cropfolder)) mkdir($nc_core->DOCUMENT_ROOT.$cropfolder, 0777, true);
		ImgRamka10($designfile_crop, $cropfolder.$uniq.$ext, $kub_ll, $kub_tt, $kub_ww, $kub_hh);

		$params['image']= $designfile;
		$params['imagecrop']= $cropfolder.$uniq.$ext;
		$params['params']= array(
			'Арт.: '.$itemid,
			'Размер: '.$width.' x '.$height.' см',
			'Тип текстуры: '.($typeprint==2 ? 'Стандарт' : 'Премиум'),
		);
		if($wallp) $params['params'][]= 'Текстура: '. $wallp;

		$params= serialize($params);
		$params= $nc_core->db->escape($params);
		
		$rr= $nc_core->db->get_results("SELECT id FROM BN_Shop_Basket WHERE code='{$code}' AND uniq='{$uniq}' LIMIT 1",ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$nc_core->db->query("UPDATE BN_Shop_Basket SET count=count+1 WHERE id={$rr[0][id]} LIMIT 1");
		}else{
			$nc_core->db->query("INSERT INTO BN_Shop_Basket SET code='{$code}', type='fotooboi', title='Фотообои', count=1,
				price='{$price}', params='{$params}', dt=".time().", uniq='{$uniq}'");
		}
		shopBasketCheck_Sum();

		print '{"result":"ok"}';
		exit();
	}

	if($action=='shopAddToBasketServices')
	{
		shopBasketCheck_Table();
		shopBasketCheck_Items();
		$code= getShopOrderStatus_1();

		$itemid= intval($_GET['itemid']);
		
		$row= $nc_core->db->get_results("SELECT `name`,price,description FROM Message180 WHERE Message_ID={$itemid} AND Checked=1 LIMIT 1",ARRAY_A);
		if(is_array($row) && count($row)) $row= $row[0]; else exit();

		$params['description']= $row['description'];

		$price= str_replace(',', '.', $row['price']);
		$price= preg_replace("/[^0-9\.]/", '', $price);

		$uniq= $row['name'].'_';

		$uniq= md5($uniq);

		$row['name']= $nc_core->db->escape($row['name']);
		$params= serialize($params);
		$params= $nc_core->db->escape($params);
		
		$rr= $nc_core->db->get_results("SELECT id FROM BN_Shop_Basket WHERE code='{$code}' AND itemid={$itemid} AND uniq='{$uniq}' LIMIT 1",ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$nc_core->db->query("UPDATE BN_Shop_Basket SET count=count+1 WHERE id={$rr[0][id]} LIMIT 1");
		}else{
			$nc_core->db->query("INSERT INTO BN_Shop_Basket SET code='{$code}', `type`='simple', itemid={$itemid}, title='{$row[name]}', count=1,
				price='{$price}', params='{$params}', dt=".time().", uniq='{$uniq}'");
		}

		shopBasketCheck_Sum();
	}

	if($action=='catalogGetOptionsContent')
	{
		$catid= intval($_POST['catid']);
		$options= $_POST['option'];
		$options= getOptions($catid, $options);
		// print'<pre>'.print_r($options,1).'</pre>';

		if(is_array($options) && count($options))
		{
			$rr= $nc_core->db->get_results("SELECT optionsname, content FROM Message178 WHERE Subdivision_ID={$catid} AND Checked=1",ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				foreach($rr AS $row)
				{
					foreach($options[1] AS $opt)
					{
						if($row['optionsname']==$opt['name'])
						{
							print '<div class="ct_opthowitlooks">'.$row['content'].'</div>';
						}
					}
				}
			}

			$rr= $nc_core->db->get_results("SELECT Message_ID,clrs_cat,clrs_subcat,clrs_img,clrs_size,clrs_name FROM Message171
				WHERE Subdivision_ID={$catid} AND Checked=1 ORDER BY clrs_subcat,Priority",ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				print '<div class="ct_k_bloki">';
				$tit= false;
				foreach($rr AS $row)
				{
					if( ! $row['clrs_size']) continue;
					$size= $row['clrs_size'];
					$rr2= $nc_core->db->get_results("SELECT * FROM Classificator_catalog_kalendari_bloki_size WHERE Checked=1",ARRAY_A);
					foreach($rr2 AS $row2)
					{
						$size= str_replace(','.$row2['catalog_kalendari_bloki_size_ID'].',', ','.$row2['catalog_kalendari_bloki_size_Name'].',', $size);
					}
					$size= explode(',', trim($size, ','));
					foreach($options[1] AS $opt)
					{
						foreach($size AS $sz)
						{
							if(strpos($opt['name'], $sz)!==false)
							{
								if($row['clrs_subcat']!=$tit)
								{
									$tit= $row['clrs_subcat'];
									print '<br /><div class="ct_kb_tit font2">'.$row['clrs_subcat'].'</div>';
								}
								$img= explode(':', $row['clrs_img']);
								print '<div class="ct_kb_itm" data-kbid="'.$row['Message_ID'].'" data-img2="'.ImgCrop72('/netcat_files/'.$img[3],150,0,false,false,null,null,null,100).'"
									data-img3="'.ImgCrop72('/netcat_files/'.$img[3],666,666,false,false,null,null,null,100).'" data-cnm="'.$row['clrs_name'].'">
									<div class="ct_kbi_img"><img src="'.ImgCrop72('/netcat_files/'.$img[3],150,150,false,true,null,null,null,100).'" /></div>';
								print '</div>';
							}
						}
					}
				}
				print '<br /></div>';
			}

			$rr= $nc_core->db->get_results("SELECT optionsname, content FROM Message179 WHERE Subdivision_ID={$catid} AND Checked=1",ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				foreach($rr AS $row)
				{
					foreach($options[1] AS $opt)
					{
						if($row['optionsname']==$opt['name'])
							print '<div class="ct_optcontent">'.$row['content'].'</div>';
					}
				}
			}
		}
	}


	if($action=='shopPaymentChange')
	{
		$code   = $nc_core->db->escape($_GET['order']);
		$secret = $nc_core->db->escape($_GET['secret']);
		$sposob = ($_GET['sposob']=='nalik'?'nalik':'urlico');

		$row= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order WHERE code='{$code}' AND status>=5 AND status<=60 AND payment_dt=0 AND secret='{$secret}' LIMIT 1",ARRAY_A);
		if(is_array($row) && count($row))
		{
			if($sposob!=$row[0]['payment'])
			{
				$logs= "\n".date('d.m.Y, H:i')." | Изменен способ оплаты. ".($sposob=='nalik'?"При получении.":"Юр.лицо.");
				$logsqq= $nc_core->db->escape($logs);
				$nc_core->db->query("UPDATE BN_Shop_Order SET payment='{$sposob}', logs=CONCAT(logs,'{$logsqq}') WHERE code='{$code}' AND secret='{$secret}' LIMIT 1");

				$subject= 'Изменен способ оплаты! Заказ '.substr($code,1);
				$body= '<p>Заказ '.substr($code,1).' &mdash; изменен способ оплаты!</p>';
				$body .= '<p>'.($sposob=='nalik'?"Оплата при получении.":"Оплата счета через юр.лицо.").'</p>';

				$nc_core->db->query("INSERT INTO BN_Queue_Mail SET orderCode='{$code}', `to`='".$nc_core->db->escape($row[0]['email'])."',
					subject='{$subject}', body='{$body}', dt=".time());

				$logs= $row[0]['logs'].$logs;
				$logs= str_replace("\n", '<br />', $logs);
				$body2= '<p>История заказа:<br />'.$logs.'</p>';

				if($sposob=='urlico' && is_array($_SESSION['megaform']['files']) && count($_SESSION['megaform']['files']))
				{
					foreach($_SESSION['megaform']['files'] AS $file)
					{
						$files .= 'assets/tmp/forms/'.$_SESSION['megaform']['code'].'/'.$file[2] ."\n";
					}
					$files= $nc_core->db->escape($files);
				}

				$body .= $body2;
				$nc_core->db->query("INSERT INTO BN_Queue_Mail SET orderCode='{$code}', `to`='[manager666]', subject='{$subject}', body='{$body}', files='{$files}', dt=".time());
			}
		}
	}


	if($action=='catalogSetStickers')
	{
/*
0-1    = 1500  = 1600
1-5    = 1200  = 1300
5-10   = 800   = 900
10-30  = 700   = 800
30-50  = 600   = 700
50-    = 590   = 690

a2     = 0
a3     = 1
a4     = 3
a5     = 4
sz     = 5
*/
		$listi= array(
			'a2' => 594*420,
			'a3' => 420*297,
			'a4' => 297*210,
			'a5' => 210*148,
		);
		$prices_listi= array(
			'a2'   => 0,
			'a3'   => 1,
			'a4'   => 3,
			'a5'   => 4,
			'sz'   => 5,
		);
		$prices= array(
			array(0,  1,        1600, 1600),
			array(1,  5,        1300, 1300),
			array(5,  10,       900,  900),
			array(10, 30,       800,  800),
			array(30, 50,       700,  700),
			array(50, 99999999, 690,  690),
		);

		$row= $nc_core->db->get_results("SELECT catalogPrice FROM Subdivision WHERE Subdivision_ID=228 LIMIT 1",ARRAY_A);
		if(is_array($row) && count($row))
		{
			$prices= array();
			$pricelist= str_replace("\r",'',$row[0]['catalogPrice']);
			$pricelist= explode("\n",$pricelist);
			foreach($pricelist AS $row)
			{
				$row= preg_replace("/[^0-9-=asz]/",'',$row);
				if(preg_match("/([0-9]+)-([0-9]*)=([0-9]+)=([0-9]+)/",$row,$matches)===1)
				{
					$prices[]= array($matches[1], ($matches[2]?$matches[2]:99999999), $matches[3], $matches[4]);

				}elseif(preg_match("/([0-9asz]+)=([0-9]+)/",$row,$matches)===1){
					$prices_listi[$matches[1]]= intval($matches[2]);
				}
			}
		}


		$visechka= ($_POST['visechka'] == 'n'?'n':'y');
		$maket   = ($_POST['maket'] == 'n'?'n':'y');
		$forma   = ($_POST['forma'] == 'krug'?'krug':(($_POST['forma']=='pryamoug'?'pryamoug':'slozhn')));
		$ww      = intval($_POST['ww']);
		$hh      = intval($_POST['hh']); if($forma=='krug') $hh= $ww;
		$ccn     = intval($_POST['ccn']);
		$material= ($_POST['material'] == 'matov'?'matov':(($_POST['material']=='prozr'?'prozr':'glyan')));
		$narezka = $_POST['narezka'];
		$ccl     = intval($_POST['ccl']);
		$plenka  = ($_POST['plenka'] == 'y'?'y':'n');

		if($narezka!='a2' && $narezka!='a3' && $narezka!='a4' && $narezka!='a5' && $narezka!='sz') $narezka= 'a2';

		if($maket=='y')
		{
			if($hh>0 && $hh<10) $error .= '<div>'.addslashes(icon('warning')).' Мин.высота 10&nbsp;мм</div>';
			if($ww>0 && $ww<10) $error .= '<div>'.addslashes(icon('warning')).' Мин.ширина 10&nbsp;мм</div>';
			if($ww>1600) $error .= '<div>'.addslashes(icon('warning')).' Макс.ширина 1,6&nbsp;метра</div>';
			if($forma=='krug' && ($ww>1200 || $hh>1200)) $error .= '<div>'.addslashes(icon('warning')).' Макс.диаметр 1,2&nbsp;метра</div>';
		}

		if($visechka=='n') $narezka= 'sz';
		elseif($maket=='n') $narezka= 'a2';

		if($visechka=='n' || $maket=='y')
		{
			if( ! $ww || ! $hh) $error .= '<div>'.addslashes(icon('warning')).' Укажите размер наклейки</div>';
			if(! $ccn) $error .= '<div>'.addslashes(icon('warning')).' Укажите кол-во наклеек</div>';
			$area= $ww*$hh *1.1 *$ccn;
		}else{
			$area= $listi[$narezka] *$ccl;
		}
		$price= 0;
		if( ! $error)
		{
			$area /= 1000000;
			if($area<0.5) $area= 0.5;
		
			foreach($prices AS $row) if($row[0]<$area && $area<=$row[1]) $price= ($visechka=='y'?$row[3]:$row[2]);
			$price *= $area;
			if($visechka=='y' && $maket=='y' && $forma=='slozhn') $price*=1.5;
			if($plenka=='y') $price*=1.5;

			if($prices_listi[$narezka]) $price += $prices_listi[$narezka] *($maket=='y'?$ccn:$ccl);

			$price_za= 0;
			if($maket=='y') $price_za= round($price/$ccn,2);
		}

		if( ! $error && $_GET['go']=='go')
		{
			$prmsname= array(
				'visechka' => array(
					'y' => 'С резкой по контуру наклеек',
					'n' => 'Без резки по контуру',
				),
				'maket' => array(
					'y' => 'Все наклейки одинаковые',
					'n' => 'Разные наклейки',
				),
				'forma' => array(
					'krug'     => 'Круг',
					'pryamoug' => 'Прямоугольник',
					'slozhn'   => 'Сложная',
				),
				'material' => array(
					'matov' => 'Матовая пленка',
					'glyan' => 'Глянцевая пленка',
					'prozr' => 'Прозрачная пленка',
				),
				'narezka' => array(
					'a2' => 'A2 (494x420мм)',
					'a3' => 'A3 (420x297мм)',
					'a4' => 'A4 (297x210мм)',
					'a5' => 'A5 (210x148мм)',
					'sz' => 'В размер наклейки',
				),
			);

			shopBasketCheck_Table();
			shopBasketCheck_Items();
			$code= getShopOrderStatus_1();

			$catid= 228;
			$name= 'Стикеры на пленке';

			$priceqq= str_replace(',', '.', $price);
			$priceqq= preg_replace("/[^0-9\.]/", '', $priceqq);

			$uniq= $name.'_'.$visechka.'_'.$maket.'_'.$forma.'_'.$ww.'_'.$hh.'_'.$ccn.'_'.$material.'_'.$narezka.'_'.$ccl.'_'.$plenka.'_';
			$uniq= md5($uniq);

			$params['params'][]= $prmsname['visechka'][$visechka];
			if($visechka=='y')
			{
				$params['params'][]= 'Макет: '.$prmsname['maket'][$maket];
				if($maket=='y')
				{
					$params['params'][]= 'Форма высечки: '.$prmsname['forma'][$forma];
				}else{
					$params['params'][]= 'Количество листов: '.$ccl.' шт.';
				}
				$params['params'][]= 'Нарезка листов: '.$prmsname['narezka'][$narezka];
			}
			if($maket!='n')
			{
				$params['params'][]= 'Размер одной наклейки: '.$ww.'x'.$hh.'мм';
				$params['params'][]= 'Количество наклеек: '.$ccn.' шт.';
			}
			$params['params'][]= 'Материал: '.$prmsname['material'][$material];
			if($plenka=='y') $params['params'][]= 'С монтажной пленкой';

			$name= $nc_core->db->escape($name);
			$params= serialize($params);
			$params= $nc_core->db->escape($params);
			
			$rr= $nc_core->db->get_results("SELECT id FROM BN_Shop_Basket WHERE code='{$code}' AND catid={$catid} AND uniq='{$uniq}' LIMIT 1",ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				$nc_core->db->query("UPDATE BN_Shop_Basket SET count=count+1 WHERE id={$rr[0][id]} LIMIT 1");
			}else{
				$nc_core->db->query("INSERT INTO BN_Shop_Basket SET code='{$code}', `type`='simple', catid={$catid}, title='{$name}', count=1,
					price='{$priceqq}', params='{$params}', dt=".time().", uniq='{$uniq}'");
			}
			shopBasketCheck_Sum();
		}

		if($error)
		{
			print '{"result":"err","answer":"'.$error.'"}';
			exit();
		}else{
			print '{"result":"oke","pr1":"'.$price_za.'","pr2":"'.Price($price).'"}';
			exit();
		}
	}

	if($action=='catalogSetOption')
	{
		$catid         = intval($_POST['catid']);
		$editions_large= ($_POST['editions_large'] == 'y'?true:false);
		$options       = $_POST['option'];
		$values        = $_POST['values'];
		$options       = getOptions($catid, $options); //print'<pre>'.print_r($options,1).'</pre>';
		print _CATALOG($catid, true, $options[0], $editions_large, $values);
	}

	if($action=='shopHeaderBasket')
	{
		print shopHeaderBasket();
	}

	exit();
}









//--------------------------- PAYMENT ------------------------------------------
function paymentForm()
{
	global $nc_core;

	$code= $nc_core->db->escape($_GET['c']);
	$secret= $nc_core->db->escape($_GET['s']);
	$row= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order WHERE code='{$code}' AND status>=5 AND status<=60 AND payment_dt=0 AND secret='{$secret}' LIMIT 1",ARRAY_A);
	if(is_array($row) && count($row))
	{
		$row= $row[0];
		$code= substr($row['code'], 1);

		$p .= '<div class="paymentform">
			<div class="icon"><img src="assets/images/wallet.svg" /></div>

			<div class="fio">'.$row['fio'].',</div>

			<div class="txt1">'.text_gradient('есть возможность оплатить прямо сейчас!', '75,80,90', '255,102,0').'</div>

			<div class="code">Заказ&nbsp;<span class="font2">№'.$code.'</span></div>

			<div class="sum">на&nbsp;сумму&nbsp;<span><span class="font2">'.Price($row['itogo']).'</span><span class="ruble">руб.</span></span></div>

			<div class="pay"><form action="https://demomoney.yandex.ru/eshop.xml" method="post">
				<input type="hidden" name="shopId" value="'.YaKa('shopid').'" />
				<input type="hidden" name="scid" value="'.YaKa('scid').'" />
				<input type="hidden" name="customerNumber" value="'.$row['email'].'" />
				<input type="hidden" name="sum" value="'.$row['itogo'].'" />
				<input type="hidden" name="orderNumber" value="'.$row['code'].'" />
				<input type="hidden" name="cps_email" value="'.$row['email'].'" />
				<input type="hidden" name="cps_phone" value="'.substr($row['phone'],1).'" />
				<button class="font2" type="submit">Оплатить заказ</button>
			</form></div>

			<div class="icons"><div>
				<span><img src="assets/images/payment/mir.svg" /></span>
				<span><img src="assets/images/payment/visa.svg" /></span>
				<span><img src="assets/images/payment/mastercard.svg" /></span>
				<span><img src="assets/images/payment/maestro.svg" /></span>
				<span><img src="assets/images/payment/yamoney.svg" /></span>
				<span><img src="assets/images/payment/webmoney.svg" /></span>
				<span><img src="assets/images/payment/qiwi.svg" /></span>
				<span><img src="assets/images/payment/sber.svg" /></span>
				<span><img src="assets/images/payment/alfa.svg" /></span>
				<span><img src="assets/images/payment/beeline.svg" /></span>
				<span><img src="assets/images/payment/megafon.svg" /></span>
				<span><img src="assets/images/payment/mts.svg" /></span>
				<span><img src="assets/images/payment/euroset.svg" /></span>
				<span><img src="assets/images/payment/svyaznoy.svg" /></span>
				<span>и другие ...</span><br />
			</div></div>
		</div>';
	}

	return $p;
}
//--------------------------- PAYMENT ------------------------------------------








//--------------------------- CATALOG ------------------------------------------

function _CATALOG($id, $onlytable=false, $markup=false, $editions_large=false, $options_values=false)
{
	global $nc_core;

	$id= intval($id);
	// $mainCategory= getMainCategory($id);
	// $parentList= getIdLvl($mainCategory[0], $id);

	if($id==189)
	{
		$subBigCategories= $nc_core->db->get_results("SELECT Subdivision_ID AS id, Subdivision_Name AS name FROM Subdivision
			WHERE Parent_Sub_ID={$id} AND Checked=1 ORDER BY Priority", ARRAY_A);
		if(is_array($subBigCategories) && count($subBigCategories))
		{
			foreach($subBigCategories AS $row)
			{
				$subinfo= $nc_core->subdivision->get_by_id($row['id']);

				$subCategories= $nc_core->db->get_results("SELECT Subdivision_Name AS name FROM Subdivision
					WHERE Parent_Sub_ID={$row[id]} AND Checked=1 ORDER BY Priority", ARRAY_A);

				$tabs .= '<div class="bc_tab '.(empty($tabs)?'bc_tab_a':'').'" data-id="'.$row['id'].'">
					<div class="bc_t_img"><img src="'.ImgCrop72($subinfo['PageImage'],150,150,false,true).'" /></div>
					<div class="bc_t_nm font2">'.$row['name'].'</div>
					<div class="bc_t_sub">';
				if(is_array($subCategories) && count($subCategories))
				{
					foreach($subCategories AS $row2)
					{
						$tabs .= '<div>'.$row2['name'].'</div>';
					}
				}
				$tabs .= '</div>
				</div>';

				$tabscontent .= '<div class="bc_tabcontent '.(empty($tabscontent)?'bc_tabcontent_a':'').' bc_tabcontent_'.$row['id'].'">';
				$tabscontent .= _CATALOG($row['id']);
				$tabscontent .= '</div>';
			}

			$pp .= '<div class="box_bigcatalog">
				<div class="bigcatalog">
					<div class="bc_tabs">
					'.$tabs.'<br />
					</div>
					<div class="bc_tabscontent">
					'.$tabscontent.'
					</div>
				</div>
			</div>';
		}
		return $pp;
	}


	if(!$onlytable) $pp .= '<div class="box_catalog"><div class="catalog">';

	if(!$onlytable) $subCategories= $nc_core->db->get_results("SELECT Subdivision_ID AS id, Subdivision_Name AS name FROM Subdivision
		WHERE Parent_Sub_ID={$id} AND Checked=1 ORDER BY Priority", ARRAY_A);
	if(empty($subCategories)) $subCategories[]= array('id'=>$id, 'name'=>false);
	if(is_array($subCategories) && count($subCategories))
	{
		foreach($subCategories AS $key=>$row)
		{
			$optionsdescriptions= array();

			if(!$onlytable)
			{
				$markup= getOptions($row['id']);
				$markup= $markup[0];
			}

			$options_value= 0;
			if(is_array($options_values) && count($options_values))
			{
				foreach($options_values AS $val)
				{
					if($val['c'])
					{
						$options_value= intval($val['c']);
					}elseif($val['w'] && $val['h']){
						$val['w']= intval($val['w']);
						$val['h']= intval($val['h']);
						$val['w'] /= 100;
						$val['h'] /= 100;
						$options_value= round($val['w'] * $val['h']);
					}
				}
			}

			$subinfo= $nc_core->subdivision->get_by_id($row['id']);

			if(!$onlytable)
			{
				$options= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Option WHERE parent={$row[id]} AND enabled='y' ORDER BY ido, ii", ARRAY_A);

				$pp .= '<div class="ct_border ct_border_'.$row['id'].'" data-id="'.$row['id'].'">
					<div class="ct_left" style="'.(empty($options)?'float:none;width:auto;':'').'">';

				if($row['name'] || $subinfo['AlterTitle'] || $subinfo['productionTime']) $pp .= '<div class="ct_hd">';

				if($row['name']) $pp .= '<div class="ct_tit ct_tit_'.($key+1).' tiptop" title="'.$subinfo['categoryDescription'].'">'.$row['name'].'</div>';
				if($subinfo['AlterTitle'])
				{
					if( ! $row['name']) $pp .= '<div class="ct_tit ct_tit_'.($key+1).' tiptop" title="'.$subinfo['categoryDescription'].'">'.$subinfo['AlterTitle'].'</div>';
					else $pp .= '<div class="ct_subtit">'.$subinfo['AlterTitle'].'</div>';
				}

				if($subinfo['productionTime']) $pp .= '<div class="ct_txt">Срок изготовления '.icon('update').' <span class="tiptop" title="После оплаты и утверждения макета">'.$subinfo['productionTime'].' *</span></div>';

				if($row['name'] || $subinfo['AlterTitle'] || $subinfo['productionTime']) $pp .= '<br /></div>';

				$pp .= '<div class="ct_c">';
			}

			if($row['id']==228)
			{
				$pp .= '<div class="stickers"><form>
					<div class="sck_col sck_col1">
						<div class="sck_opt sck_opt_visechka">
							<div class="sck_o_lab">Резка</div>
							<div class="sck_o_inp"><select name="visechka">
								<option value="y">С резкой по контуру наклеек</option>
								<option value="n">Без резки по контуру</option>
							</select></div>
							<br />
						</div>

						<div class="sck_opt sck_opt_maket">
							<div class="sck_o_lab">'.icon('warning').'&nbsp;<span class="as1 dashed">Макет&nbsp;наклейки</span></div>
							<div class="sck_o_inp"><select name="maket">
								<option value="y">Все наклейки одинаковые</option>
								<option value="n">Разные наклейки</option>
							</select></div>
							<br />
						</div>

						<div class="sck_opt sck_opt_forma">
							<div class="sck_o_lab">Форма высечки</div>
							<div class="sck_o_inp"><select name="forma">
								<option value="krug">Круг</option>
								<option value="pryamoug">Прямоугольник</option>
								<option value="slozhn">Сложная</option>
							</select></div>
							<br />
						</div>

						<div class="sck_opt sck_opt_wwhh">
							<div class="sck_o_lab">Размер одной наклейки</div>
							<div class="sck_o_inp"><input class="sck_opt_ww mini" type="text" name="ww" /> x <input class="sck_opt_hh mini" type="text" name="hh" />&nbsp;мм</div>
							<br />
						</div>

						<div class="sck_opt sck_opt_ccn">
							<div class="sck_o_lab">Количество наклеек</div>
							<div class="sck_o_inp"><input type="text" name="ccn" />&nbsp;шт.</div>
							<br />
						</div>
					</div>

					<div class="sck_col sck_col2">
						<div class="sck_opt">
							<div class="sck_o_lab"><span class="dashed tiptop" title="Пленка европейская, 100 мкр.">Материал</span></div>
							<div class="sck_o_inp"><select name="material">
								<option value="matov">Матовая пленка</option>
								<option value="glyan">Глянцевая пленка</option>
								<option value="prozr">Прозрачная пленка</option>
							</select></div>
							<br />
						</div>

						<div class="sck_opt sck_opt_narezka">
							<div class="sck_o_lab">Нарезка листов</div>
							<div class="sck_o_inp"><select name="narezka">
								<option value="a2">A2 (594x420мм)</option>
								<option value="a3">A3 (420x297мм)</option>
								<option value="a4">A4 (297x210мм)</option>
								<option value="a5">A5 (210x148мм)</option>
								<option value="sz">В размер наклейки</option>
							</select></div>
							<br />
						</div>

						<div class="sck_opt sck_opt_ccl">
							<div class="sck_o_lab">Количество листов</div>
							<div class="sck_o_inp"><input type="text" name="ccl" />&nbsp;шт.</div>
							<br />
						</div>

						<div class="sck_opt"><label>
							<div class="sck_o_lab"><input type="checkbox" name="plenka" value="y"></div>
							<div class="sck_o_inp"><span class="dashed tiptop" title="Эта&nbsp;пленка наклеивается поверх&nbsp;наклейки для&nbsp;простого переноса наклейки на&nbsp;поверхность">монтажная пленка</span></div>
							<br />
						</label></div>
					</div>

					<div class="sck_col sck_col3">
						<div class="sckc_tit font2">Стоимость</div>
						<div class="sckc_errs"></div>
						<div class="sckc_sum sckc_sum1">Наклейка: <span class="price font2">'.Price('47.50',2).'</span> <span class="ruble">руб.</span></div>
						<div class="sckc_sum sckc_sum2">Тираж: <span class="price font2">'.Price('4750').'</span> <span class="ruble">руб.</span></div>
						<div class="sckc_tobasket font2">Заказать</div>
						<div class="sckc_gook font2">'.icon('check').'<br />Добавлено в корзину</div>
						<div class="ct_loading">&nbsp;</div>
					</div>
					<br />
				</form></div><!--/.stickers-->';

			}else{
				$pp .= '<div class="ct_loading">&nbsp;</div>
							<table class="ct_t">';

				$options_value_key= false;
				$editions_large_flag= false;
				$editionslist= array();
				$items= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog WHERE parent={$row[id]} AND enabled='y' ORDER BY ii", ARRAY_A);
				if(is_array($items) && count($items))
				{
					foreach($items AS $itemkey => $item)
					{
						$editions= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Edition WHERE idi={$item[id]} AND enabled='y' ORDER BY ii", ARRAY_A);


						if( ! $itemkey)
						{
							if(count($editions)>10) $editions_large_flag= true;

							$pp .= '<tr class="ctt_tit"><td><span>'.$subinfo['nameCatalogTableCol'].'</span></td>';
							foreach($editions AS $editionkey=>$edition)
							{
								if($editions_large_flag)
								{
									if( ! $editions_large && $editionkey>=7){ $editionslist[]= false; break; }
									elseif($editions_large && $editionkey<7){ $editionslist[]= false; continue; }
								}

								unset($edition['id']);
								unset($edition['idi']);
								unset($edition['price']);
								$edition['ed']= (strpos($edition['edition'],'м2')!==false?'м<span class="vkvadrate">2</span>':'шт.');
								$edition['range']= ($edition['range']=='y'?true:false);
								$edition['edition']= str_replace(' шт.', '', $edition['edition']);
								$edition['edition']= str_replace(' м2', '', $edition['edition']);

								if( ! $edition['range'])
								{
									$edition['edition_val']= intval(preg_replace("/[^0-9]/", '', $edition['edition']));
								}else{
									if(strpos($edition['edition'], '-')!==false)
									{
										$tmp= explode('-', $edition['edition']);
										$ot= intval(preg_replace("/[^0-9]/", '', $tmp[0]));
										$do= intval(preg_replace("/[^0-9]/", '', $tmp[1]));
									}else{
										if( ! $editionkey)
										{
											$ot= 0;
											$do= intval(preg_replace("/[^0-9]/", '', $edition['edition']));
										}elseif($editionkey=count($editions)-1){
											$ot= intval(preg_replace("/[^0-9]/", '', $edition['edition']));
											$do= 99999999;
										}
									}
									$edition['edition_ot']= $ot;
									$edition['edition_do']= $do;
								}

								$editionslist[]= $edition;

								$pp .= '<td class="ctt_tit_td">'.$edition['edition'].' <span>'.$edition['ed'].'</span></td>';

								if($options_value_key===false && $options_value && $edition['edition_ot']<=$options_value && $options_value<=$edition['edition_do'])
								{
									$options_value_key= $editionkey;
									if($edition['range'])
									{
										$pp .= '<td class="ctt_tit_td cttt_td_myedition">'.$options_value.' <span>'.$edition['ed'].'</span></td>';
									}
								}
							}
							$pp .= '</tr>';
						}


						$item['name']= str_replace('м2','м<span class="vkvadrate">2</span>',$item['name']);
						$pp .= '<tr><td class="ctt_name">'.($item['description']?'<span class="tiptop" title="'.$item['description'].'">'.$item['name'].'</span>':$item['name']).'</td>';

						$markup_by_size= $markup;
						$markup_by_size= changeMarkupBySize($markup_by_size, $item['description']);

						$options_valueflag= false;
						foreach($editions AS $editionkey=>$edition)
						{
							if($editions_large_flag)
							{
								if( ! $editions_large && $editionkey>=7) break;
								elseif($editions_large && $editionkey<7) continue;
							}

							$editioninfo= $editionslist[$editionkey];

							$edition_markup= $markup_by_size;

							if( ! $editioninfo['range']) $edition_markup *= $editioninfo['edition_val'];
							$price= $edition['price'];
							if($edition_markup) $price += $edition_markup;
							$pp .= '<td class="ctt_itm_td '.($editioninfo['range']?'ctt_itm_disabled':'ctt_itm').'" '.($editioninfo['range']?'':'data-edition="'.$edition['id'].'"').'><span class="price">'.Price($price,($editioninfo['range']?2:0)).'</span> <span class="ed"><span class="ruble">руб</span>'.($editioninfo['range']?' /'.$editioninfo['ed']:'').'</span><span class="check">'.icon('check').'</span></td>';

							if($options_value_key===$editionkey)
							{
								if($editioninfo['range'])
								{
									$options_valueprice= $price * $options_value;
									$pp .= '<td class="ctt_itm_td ctt_itm ctt_itm_myedition '.($itemkey==count($items)-1?'ctt_itm_myedition_last':'').'" data-edition="'.$edition['id'].'"><span class="price">'.Price($options_valueprice).'</span> <span class="ed"><span class="ruble">руб</span></span><span class="check">'.icon('check').'</span></td>';
								}
							}
						}
						$pp .= '</tr>';
					}
				}
				$pp .= '</table>';
			}

			if($onlytable) return $pp;

			$pp .= '</div><!-- /.ct_c -->
				</div><!-- /.ct_left -->';

			if(is_array($options) && count($options))
			{
				$pp .= '<div class="ct_right">
					<div class="ct_hd"><div class="ct_tit">'.icon('cog').' Опции</div></div>
					<div class="ct_c"><form>';

				if($subinfo['optionTitle']) $pp .= '<div class="ctc_opttit">'.$subinfo['optionTitle'].'</div>';

				if($editions_large_flag)
				{
					$pp .= '<div class="ctc_opt">';
					$pp .= '<label><input type="checkbox" name="editions_large" value="y" /> Большой тираж</label>';
					$pp .= '</div>';
				}

				$ido= false;
				$option_type= false;
				foreach($options AS $optionkey => $option)
				{
					$firstoptionval= false;
					if($option['ido']!==$ido)
					{
						$firstoptionval= true;
						$subname= false;

						if($option_type)
						{
							if($option_type=='select') $pp .= '</select>';
							if($option_type=='radio') $pp .= '</div>';

							if($row['id']==217 && $ido==1) $pp .= '<div class="option_descr">&mdash; отделка доступна только на стандартной бумаге</div>';

							$pp .= '</div>';
						}

						$pp .= '<div class="ctc_opt ctc_opt_'.$option['type'].'">';
						if($option['type']=='select') $pp .= '<select class="ido'.$option['ido'].'" name="option['.$option['ido'].']" data-ido="'.$option['ido'].'">';
						if($option['type']=='radio') $pp .= '<input type="hidden" name="option['.$option['ido'].']" value="'.$option['id'].'" />';

						$ido= $option['ido'];
						$option_type= $option['type'];
					}

					if($option['type']=='select')
					{
						$pp .= '<option class="'.($row['id']==217 && !$firstoptionval ?'notfirst':'').'" '.($option['default']=='y'?'selected="selected"':'').' value="'.$option['id'].'">'.$option['name'].'</option>';

					}elseif($option['type']=='checkbox'){
						$pp .= '<label><input type="checkbox" '.($option['default']=='y'?'checked="checked"':'').' name="option['.$option['ido'].']" value="'.$option['id'].'" /> '.$option['name'].'</label>';

					}elseif($option['type']=='radio'){
						if($option['subname']!=$subname)
						{
							if($subname) $pp .= '</div>';
							$pp .= '<div class="ctco_point '.(!$subname?'ctco_point_a':'').'">
								<div class="ctco_p_butt">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').' '.$option['subname'].'</div>';
							$subname= $option['subname'];
						}
						$option['name']= str_replace('м2','м<span class="vkvadrate">2</span>',$option['name']);
						$pp .= '<div class="ctco_p_itm" data-val="'.$option['id'].'" data-default="'.$option['default'].'">'.$option['name'].'</div>';

					}elseif($option['type']=='count'){
						$pp .= icon('warning').' <span>Для&nbsp;расчета&nbsp;стоимости укажите&nbsp;количество</span>
						<br /><input class="ctco_p_inp" type="text" name="values['.$option['ido'].'][c]" /> '. $option['name'];
						$pp .= '&nbsp; <div class="ctco_p_ok">OK</div>';

					}elseif($option['type']=='size'){
						$pp .= icon('warning').' <span>Для&nbsp;расчета&nbsp;стоимости задайте&nbsp;размер</span>
						<br /><input class="ctco_p_inp ctco_p_inp_mini" type="text" name="values['.$option['ido'].'][w]" /> x <input class="ctco_p_inp ctco_p_inp_mini" type="text" name="values['.$option['ido'].'][h]" /> '. $option['name'];
						$pp .= '&nbsp; <div class="ctco_p_ok">OK</div>';
					}
				}
				if($option_type=='select') $pp .= '</select>';
				if($option_type=='radio') $pp .= '</div>';
				$pp .= '</div>';

				$subinfo['catalogOptionTxt']= str_replace('м2','м<span class="vkvadrate">2</span>',$subinfo['catalogOptionTxt']);
				$pp .= '<div class="ctc_opttxt">'.$subinfo['catalogOptionTxt'].'</div>';
					
				$pp .= '<div class="ct_opthowitlooks_butt">'.icon('warning').' <span class="as1 dashed">Как это выглядит?</span></div>';


				if($row['id']==234 || $row['id']==235)
				{
					$pp .= '<div class="ct_kbloki_selected">
						<div class="ct_kbs_tit ct_kbs_tit1 font2"><span>'.icon('warning').'&nbsp;</span> Выберите дизайн календарного блока</div>
						<div class="ct_kbs_tit ct_kbs_tit2 font2">Выбранный дизайн календарного блока</div>
						<div class="ct_kbs_sel"><img src="assets/images/krivayastrelka2.svg" /><br /></div>
						<div class="ct_kbs_open font2">Показать '.icon('arrow_down').'</div>
					</div>';

					$pp .= '<input class="kalblokid" type="hidden" name="kbid" value="" />';
				}


				$pp .= '<input type="hidden" name="catid" value="'.$row['id'].'" />';
				$pp .= '</form>
					</div><!-- /.ct_c -->';
				$pp .= '</div><!-- /.ct_right -->';
			}

			$pp .= '<br /></div><!-- /.ct_border -->';


			$pp .= '<div class="optionscontent optionscontent_'.$row['id'].'" data-catid="'.$row['id'].'"></div>';


			$contents= $nc_core->sub_class->get_by_subdivision_id($row['id']);
			if(is_array($contents) && count($contents))
			{
				foreach($contents AS $content)
				{
					$content= $nc_core->db->get_results("SELECT * FROM Message{$content[Class_ID]} WHERE Subdivision_ID={$content[Subdivision_ID]} AND Sub_Class_ID={$content[Sub_Class_ID]} ORDER BY Priority", ARRAY_A);
					if(is_array($content) && count($content))
					{
						foreach($content AS $cont)
						{
							$pp .= $cont['TextContent'];
						}
					}
				}
			}

			$pp .= nc_objects_list(258, 208, 'catid='.$row['id']);

			$pp .= '<div style="height:40px;"></div>';
		}
	}

	$pp .= '</div><!-- /.catalog -->
	</div><!-- /.box_catalog -->';

	return $pp;
}

function getMainCategoryColor($mcid=0, $type=0)
{
	$colors= array(
		'p' => array('#ff6600',  '255,102,0',   array(255,102,0)),
		's' => array('#34c0d2',  '52,192,210',  array(52,192,210)),
		'f' => array('#802990',  '128,41,144',  array(128,41,144)),
		'h' => array('#54b53b',  '84,181,59',   array(84,181,59)),
	);
	return (isset($colors[$mcid][$type]) ? $colors[$mcid][$type] : $colors['p'][$type] );
}

function getMainCategory($Subdivision_ID)
{
	$maincategories= array(203=>'p', 201=>'s', 200=>'f', 199=>'h');
	$mcid= getIdLvl(0, $Subdivision_ID, 2);
	return (isset($maincategories[$mcid]) ? array($mcid,$maincategories[$mcid]) : array(203,'p'));
}


function getOptions($catid, $setoptions=false, $basketoptions=false)
{
	global $nc_core;
	$catid= intval($catid);
	$defoptions= array();
	$options= array();
	$markup= 0;
	
	$rr= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Option WHERE parent={$catid} AND enabled='y' ORDER BY ido, IF(`default`='y',0,1), ii", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			if($defoptions[$row['ido']][0]) continue;
			$defoptions[$row['ido']]= array(
				$row['id'],
				(($row['type']!='checkbox' && $row['type']!='size' && $row['type']!='count') || $row['default']=='y'?true:false),
				false,
				$row['type']
			); // array(option_id, учитывать markup, определено, option_type);
			if(($row['type']!='checkbox' && $row['type']!='size' && $row['type']!='count') || $row['default']=='y')
			{
				$markup += floatval($row['markup']);
				$options[$row['ido']]= $row;
			}
		}
	}
	if(is_array($setoptions) && count($setoptions))
	{
		foreach($setoptions AS $ido=>$optid)
		{
			if( ! $defoptions[$ido][0]) return false;
			$defoptions[$ido][0]= $optid;
			$defoptions[$ido][1]= true;
			$defoptions[$ido][2]= true;
		}
	}elseif(is_array($basketoptions) && count($basketoptions)){
		foreach($basketoptions AS $ido=>$opt)
		{
			if( ! $defoptions[$ido][0]) return false;
			$defoptions[$ido][0]= $opt['id'];
			$defoptions[$ido][1]= true;
			$defoptions[$ido][2]= true;
		}
	}else return array(floatval($markup), $options);
	
	$options= array();
	$markup= 0;
	if(is_array($defoptions) && count($defoptions))
	{
		foreach($defoptions AS $ido=>$opt)
		{
			if( ! $opt[2] && $opt[3]!='checkbox' && $opt[3]!='size' && $opt[3]!='count') return false;
			$ido= intval($ido);
			$opt[0]= intval($opt[0]);
			$row= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Option WHERE parent={$catid} AND ido={$ido} AND id={$opt[0]} AND enabled='y' LIMIT 1", ARRAY_A);
			if( ! $row && $opt[3]!='size' && $opt[3]!='count') return false;
			if($opt[2]) $markup += floatval($row[0]['markup']);
			if(($opt[3]!='checkbox' && $opt[3]!='size' && $opt[3]!='count') || $opt[2]) $options[$ido]= $row[0];
		}
	}
	return array(floatval($markup), $options);
}


//--------------------------- CATALOG ------------------------------------------





//--------------------------- SHOP ---------------------------------------------

function shopBasketCheck_Table()
{
	// Вызывать перед тем как изменять инфу по текущему заказу
	global $nc_core;
	$code= getShopOrderStatus_1();
	if($code===false)
	{
		$user= $nc_core->db->escape('s'.session_id());
		$code= 'w'.(date('Y')-2015).date('md');
		$num= 0;
		$row= $nc_core->db->get_results("SELECT COUNT(id) AS cc FROM BN_Shop_Order WHERE code LIKE '{$code}%'", ARRAY_A);
		if(is_array($row) && count($row)) $num= $row[0]['cc'];
		do{
			$num++;
			$row= $nc_core->db->get_results("SELECT id FROM BN_Shop_Order WHERE code='{$code}{$num}' LIMIT 1", ARRAY_A);
		}while(is_array($row) && count($row));
		$code .= $num;
		$nc_core->db->query("INSERT INTO BN_Shop_Order SET status='1', code='{$code}', logs='".date('d.m.Y, H:i')." | Корзина создана', user='{$user}'");
	}
}

function shopBasketCheck_Items()
{
	// Вызывать перед тем как выводить инфу по текущему заказу
	global $nc_core;
	$code= getShopOrderStatus_1();
	$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Basket WHERE code='{$code}' AND enabled='y'", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			$flag= true;
			if($row['type']=='complex')
			{
				$rr2= $nc_core->db->get_results("SELECT id FROM BN_PG_Catalog WHERE id={$row[itemid]} AND enabled='y' LIMIT 1", ARRAY_A);
				if( ! $rr2) $flag= false;
				if($flag)
				{
					$rr2= $nc_core->db->get_results("SELECT id FROM BN_PG_Catalog_Edition WHERE idi={$row[itemid]} AND id='{$row[edid]}' AND enabled='y' LIMIT 1", ARRAY_A);
					if( ! $rr2) $flag= false;
				}
				if($flag)
				{
					$params= unserialize($row['params']);
					if(is_array($params['options']) && count($params['options']))
					{
						$options= getOptions($row['catid'],false,$params['options']);
						if( ! $options) $flag= false;
						else{
							$markup= changeMarkupBySize($options[0], $params['description']);

							$price= getShopBasketItemPrice($row['catid'], $row['itemid'], $row['edid'], $markup, $row['ed']);
							if($row['price']!=$price)
							{
								$params['options']= $options[1];
								$params= serialize($params);
								$params= $nc_core->db->escape($params);
								$nc_core->db->query("UPDATE BN_Shop_Basket SET price='{$price}', params='{$params}' WHERE id={$row[id]} LIMIT 1");
								$flag2= true;
							}
						}
					}
				}
			}

			if($row['type']=='simple' && $row['catid']!=228)
			{
				$row2= $nc_core->db->get_results("SELECT price FROM Message180 WHERE Message_ID={$row[itemid]} AND Checked=1 LIMIT 1",ARRAY_A);
				if(is_array($row2) && count($row2))
				{
					$price= str_replace(',', '.', $row2[0]['price']);
					$price= preg_replace("/[^0-9\.]/", '', $price);
					$nc_core->db->query("UPDATE BN_Shop_Basket SET price='{$price}' WHERE id={$row[id]} LIMIT 1");
				}else $flag= false;
			}

			if( ! $flag)
			{
				$nc_core->db->query("UPDATE BN_Shop_Basket SET enabled='n' WHERE id={$row[id]} LIMIT 1");
				$flag2= true;
			}
		}
		if($flag2) shopBasketCheck_Sum();
	}
}

function shopBasketCheck_Sum()
{
	// Вызывать сразу после изменений по текущему заказу
	global $nc_core;
	$code= getShopOrderStatus_1();

	$deliveryCost= 0;
	$abilityDeliver= 'n';
	$deliveryDays= '0';
	$tariffid= 0;
	$deliveryCalculator= shopDeliveryCalculator();
	if($deliveryCalculator[0]===true)
	{
		$deliveryCost= intval($deliveryCalculator[1]);
		$abilityDeliver= 'y';
		$tariffid= $deliveryCalculator[3];
		$deliveryDays= $deliveryCalculator[2][0].($deliveryCalculator[2][1]!=$deliveryCalculator[2][0]?'-'.$deliveryCalculator[2][1]:'');
	}
	$delivery_qq= "ability_deliver='{$abilityDeliver}', cost_delivery='{$deliveryCost}', delivery_days='{$deliveryDays}', delivery_tariff='{$tariffid}'";

	$sum= 0;
	$rr= $nc_core->db->get_results("SELECT `count`, price FROM BN_Shop_Basket WHERE code='{$code}' AND enabled='y'", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			$sum += $row['price'] * $row['count'];
		}
	}
	$nc_core->db->query("UPDATE BN_Shop_Order SET `sum`='{$sum}', itogo='".($sum+$deliveryCost)."' ,{$delivery_qq} WHERE code='{$code}' LIMIT 1");
}

function getShopOrderStatus_1($fields="code")
{
	global $nc_core;
	$user= $nc_core->db->escape('s'.session_id());
	$fieldsqq= $nc_core->db->escape($fields);
	$result= false;
	$row= $nc_core->db->get_results("SELECT {$fieldsqq} FROM BN_Shop_Order WHERE user='{$user}' AND status='1' ORDER BY id LIMIT 1", ARRAY_A);
	if(is_array($row) && count($row))
	{
		if(strpos($fields,',')===false && strpos($fields,'*')===false) $result= $row[0][trim($fields,"`")];
			else $result= $row[0];
	}
	return $result;
}

function shopHeaderBasket()
{
	global $nc_core;
	$sum= getShopOrderStatus_1("`sum`");
	$sum= intval($sum);
	return ($sum ? '<span>'.Price($sum).'</span> <span class="ruble">руб</span>' : '<span>Корзина</span>');
}

function shopBasketPage_Items()
{
	global $nc_core;

	shopBasketCheck_Items();
	$order= getShopOrderStatus_1("*");

	$sum= intval($order['sum']);

	// $rr= $nc_core->db->get_results("SELECT bb.* FROM BN_Shop_Basket AS bb
		// INNER JOIN BN_PG_Catalog AS cc ON cc.id=bb.itemid
		// WHERE bb.code='{$order[code]}' ORDER BY bb.enabled DESC, bb.itemid, bb.price", ARRAY_A);
	$rr= $nc_core->db->get_results("SELECT bb.* FROM BN_Shop_Basket AS bb
		WHERE bb.code='{$order[code]}' ORDER BY bb.enabled DESC, bb.itemid, bb.price", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp .= '<div class="sbpi_titrow">
			<div class="sbpi_nm sbpi_tit">Наименование</div>
			<div class="sbpi_del sbpi_tit">Удалить</div>
			<div class="sbpi_sum sbpi_tit">Сумма, руб.</div>
			<div class="sbpi_cc sbpi_tit">Кол-во комплектов</div>
			<div class="sbpi_pr sbpi_tit">Цена комплекта</div>
			<div class="sbpi_ed sbpi_tit">В комплекте</div>
		<br /></div>';
		foreach($rr AS $row)
		{
			$category= str_replace("\n", " / ", $row['category']);
			$params= unserialize($row['params']);
			
			$options= '';
			if(is_array($params['params']) && count($params['params']))
			{
				foreach($params['params'] AS $prm)
				{
					$options .= '<div class="sbpinm_mini">&mdash; '.$prm.'</div>';
				}
			}
			if(is_array($params['options']) && count($params['options']))
			{
				foreach($params['options'] AS $opt)
				{
					$options .= '<div class="sbpinm_mini">&mdash; ';
					if($opt['subname']) $options .= $opt['subname'].' ';
					$options .= $opt['name'].' ';
					$options .= '</div>';
				}
			}

			$pp .= '<div class="sbpi_itm '.($row['enabled']!='y'?'sbpi_itm_disabled':'').'" data-id="'.$row['id'].'">';

			$pp .= '<div class="sbpi_nm sbpi_row tiptop" title="'.$params['description'].'">
				<div class="sbpi_nm_img"><a class="imagelightbox" href="'.$params['imagecrop'].'">
					<img src="'.ImgCrop72($params['image'],60,60,false,true).'" />
				</a></div>
				<div class="sbpi_nm_txt">
					<div>'.$category.'</div>
					<div>'.$row['title'].'</div>
					'.$options.'
				</div>
				<br />
			</div>';

			if($row['enabled']!='y') $pp .= '<div class="sbpi_disabled sbpi_row">Товар с данными параметрами больше <nobr>не существует!</nobr><br />Его нельзя заказать.</div>';

			$pp .= '<div class="sbpi_del sbpi_row">'.icon('cross').'</div>';

			if($row['enabled']!='y') $pp .= '<div class="sbpi_space sbpi_row">&nbsp;</div>';

			if($row['enabled']=='y')
			{
				$pp .= '<div class="sbpi_sum sbpi_row">'.Price($row['price']*$row['count']).' <span class="ruble">руб</span><div class="svgloading"></div></div>

					<div class="sbpi_cc sbpi_row"><div><span class="sbpi_cc_pm sbpi_cc_m" data-pm="m">'.icon('circle-minus').'</span> <input type="text" value="'.$row['count'].'" /> <span class="sbpi_cc_pm sbpi_cc_p" data-pm="p">'.icon('circle-plus').'</span></div></div>

					<div class="sbpi_pr sbpi_row">'.Price($row['price']).' <span class="ruble">руб</span></div>';
			}

			$row['ed']= str_replace('м2', 'м<span class="vkvadrate">2</span>', $row['ed']);
			$pp .= '<div class="sbpi_ed sbpi_row">'.$row['ed'].'</div>';

			$pp .= '<br /></div>';
		}

		$pp .= '<div class="sbpi_sumsum">
			<div class="sbpis_clear"><span class="as2">'.icon('cross').'Очистить корзину</span></div>
			<div class="sbpis_sum"><span class="sum font2">'.Price($sum).'</span> <span class="ruble">руб</span><div class="svgloading"></div></div>
			<div class="sbpis_sumtit">Сумма</div>
			<br />
			<div class="svgloading"></div>
		</div>';

	}else return false;
	return $pp;
}

function shopBasketPage_Checkout()
{
	global $nc_core;
	
	shopBasketCheck_Items();
	$order= getShopOrderStatus_1("*");

	$deliveryCost= true;

	$pp .= '<div class="sbpc_errors">';

	if($order['city']!='Ростов-на-Дону' && $order['delivery']=='pvz' && $order['pvz']=='0')
	{
		$deliveryCost= false;
		$pp .= '<div>'.icon('warning').'&nbsp; Выберите способ доставки.</div>';
	}

	$pp .= '<br /></div>';

	if($deliveryCost)
	{
		if($order['ability_deliver']=='y' && $order['cost_delivery']>0)
		{
			$pp .= '<div class="sbpc_sum sbpc_sum2">
				<div class="sbpcs_right"><span class="sum font2">'.Price($order['cost_delivery']).'</span>&nbsp;<span class="ruble">руб</span><div class="svgloading"></div></div>
				<div class="sbpcs_left">Стоимость&nbsp;доставки</div>
				<br />
			</div>';
		}elseif($order['ability_deliver']!='y'){
			$pp .= '<div class="sbpc_info">
				<div class="sbpcs_i_itm">Наши менеджеры рассчитают стоимость доставки вручную!</div>
				<br />
			</div>';
		}
	}

	$pp .= '<div class="sbpc_sum">
		<div class="sbpcs_right"><span class="sum font2">'.Price($order['itogo']).'</span>&nbsp;<span class="ruble">руб.</span><div class="svgloading"></div></div>
		<div class="sbpcs_left">Сумма&nbsp;заказа</div>
		<br />
	</div>';
	
	$pp .= '<button class="shopbasketcheckout font2" type="button" data-code="'.$order['code'].'" data-itogo="'.$order['itogo'].'">Оформить заказ<div class="svgloading"></div></button><br />';
	
	return $pp;
}

function shopBasketPage_Payment()
{
	global $nc_core;

	$order= getShopOrderStatus_1("*");
	
	$pp .= '<div class="sbp_tit sbpdt_tit font2">Оплата</div>';

	$pp .= '<div class="sbpp_paymentitm '.($order['payment']=='fizlico'?'sbpp_paymentitm_a':'').'" data-id="fizlico">
	<div class="sbpp_label sbpp_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
	<div class="sbpp_value">Оплатить как физическое лицо на сайте
		<div>
			<span><img src="assets/images/payment/mir.svg" /></span>
			<span><img src="assets/images/payment/visa.svg" /></span>
			<span><img src="assets/images/payment/mastercard.svg" /></span>
			<span><img src="assets/images/payment/maestro.svg" /></span>
			<span><img src="assets/images/payment/yamoney.svg" /></span>
			<span><img src="assets/images/payment/webmoney.svg" /></span>
			<span><img src="assets/images/payment/qiwi.svg" /></span>
			<span><img src="assets/images/payment/sber.svg" /></span>
			<span><img src="assets/images/payment/alfa.svg" /></span>
			<span><img src="assets/images/payment/beeline.svg" /></span>
			<span><img src="assets/images/payment/megafon.svg" /></span>
			<span><img src="assets/images/payment/mts.svg" /></span>
			<span><img src="assets/images/payment/euroset.svg" /></span>
			<span><img src="assets/images/payment/svyaznoy.svg" /></span>
			<span>и другие</span>
		</div>
	</div><br /></div>';

	$pp .= '<div class="sbpp_paymentitm '.($order['payment']=='urlico'?'sbpp_paymentitm_a':'').'" data-id="urlico">
	<div class="sbpp_label sbpp_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
	<div class="sbpp_value">Заказать счет на юридическое лицо
		<div>Загрузите реквизиты в поле справа &mdash; «Файлы»<img src="assets/images/krivayastrelka.svg" /></div>
	</div><br /></div>';

	$pp .= '<div class="sbpp_paymentitm '.($order['payment']=='nalik'?'sbpp_paymentitm_a':'').'" data-id="nalik">
	<div class="sbpp_label sbpp_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
	<div class="sbpp_value">При получении</div><br /></div>';

	$pp .= '<div class="sbpp_paymentitm '.($order['payment']=='later'?'sbpp_paymentitm_a':'').'" data-id="later">
	<div class="sbpp_label sbpp_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
	<div class="sbpp_value">Определюсь позже</div><br /></div>';
	
	return $pp;
}

function shopBasketPage_Data($onlyfileslist=false)
{
	global $nc_core;

	$order= getShopOrderStatus_1("*");
	
	$tmpfolder= '/assets/tmp/forms/orders/'.$order['code'].'/';
	$diskpath= '/ORDERS/'.$order['code'].'/';
	$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Order_Files WHERE code='{$order[code]}' AND enabled='y'",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp2 .= '<div class="sbpdt_files_list">';
		foreach($rr AS $row)
		{
			$nm= substr($row['name'], strpos($row['name'],'_')+1);
			$pp2 .= '<div class="sbpdtfl_itm" data-id="'.$row['id'].'">
				<div class="sbpdtfli_nm">'.$nm.'</div>
				<div class="sbpdtfli_del">'.icon('cross').'</div>
			</div>';
			
			if($row['location']!='disk')
			{
				$fsz= substr($row['name'], 0, strpos($row['name'],'_'));
				$diskresponse= disk('resources/?path='.urlencode($diskpath.$row['name']));
				$diskresponse_arr= json_decode($diskresponse[1], true);
				if($diskresponse[0]['http_code']==200 && $diskresponse_arr['size']==$fsz)
				{
					$nc_core->db->query("UPDATE BN_Shop_Order_Files SET location='disk' WHERE code='{$order[code]}' AND `name`='{$row[name]}' LIMIT 1");
					unlink($nc_core->DOCUMENT_ROOT.$tmpfolder.$row['name']);
				}
			}else{
				unlink($nc_core->DOCUMENT_ROOT.$tmpfolder.$row['name']);
			}
		}
		$pp2 .= '</div>';
	}
	if($onlyfileslist) return $pp2;

	$pp .= '<div class="sbp_tit sbpdt_tit font2">Контактные данные</div>';

	$pp .= '<form class="shopBasketDataForm">';

	$pp .= '<div class="sbpdt_row"><div class="sbpdt_label">Имя</div>
	<div class="sbpdt_value"><input type="text" name="fio" data-nm="fio" value="'.htmlspecialchars($order['fio']).'" /></div><br /></div>';

	$pp .= '<div class="sbpdt_row"><div class="sbpdt_label">Телефон</div>
	<div class="sbpdt_value"><input class="phonemask" type="text" name="phone" placeholder="+7 (___) ___-____" data-nm="phone" value="'.substr($order['phone'],2).'" /></div><br /></div>';

	$pp .= '<div class="sbpdt_row"><div class="sbpdt_label">E-mail</div>
	<div class="sbpdt_value"><input type="text" name="email" data-nm="email" value="'.htmlspecialchars($order['email']).'" /></div><br /></div>';

	$pp .= '<div class="sbpdt_row sbpdt_delivery_address '.($order['delivery']=='address'?'sbpdt_delivery_address_a':'').'"><div class="sbpdt_label">Адрес доставки</div>
	<div class="sbpdt_value"><textarea class="textareaautosize" name="useraddress" data-nm="useraddress">'.($order['useraddress']).'</textarea></div><br /></div>';

	$pp .= '<div class="sbpdt_row"><div class="sbpdt_label">Сообщение</div>
	<div class="sbpdt_value"><textarea class="textareaautosize" name="message" data-nm="message">'.($order['message']).'</textarea></div><br /></div>';

	$pp .= '</form>';

	$pp .= '<div class="sbpdt_row"><div class="sbpdt_label">Файлы</div>
	<div class="sbpdt_value">
		<div class="sbpdt_files"><span><span class="as1">Выберите файлы</span><br />или перетащите их сюда.<br /><span class="dop">Загрузка начнется сразу.</span></span><input type="file" multiple /></div>';
		$pp .= '<div class="sbpdt_files_progress"></div>';
		$pp .= '<div class="sbpdt_files_ajax">'.$pp2.'</div>';
	$pp .= '</div><br /></div>';

	return $pp;
}

function shopBasketPage_Delivery()
{
	global $nc_core;

	$order= getShopOrderStatus_1("*");
	$city= cityInfo();
	$cityqq= $nc_core->db->escape($city);

	if($city!=$order['city'])
	{
		$nc_core->db->query("UPDATE BN_Shop_Order SET ability_deliver='n', cost_delivery='0', city='{$cityqq}', delivery='pvz', address='', pvz='0' WHERE code='{$order[code]}' LIMIT 1");
		$order= getShopOrderStatus_1("*");
	}

	$pp .= '<div class="sbp_tit font2">Доставка</div>';

	$pp .= '<div class="sbpd_bigrow"><div class="sbpd_label">Город</div>
	<div class="sbpd_value"><span class="city as1">г. '.$city.'</span></div><br /></div>';

	$pp .= '<div class="sbpd_deliveryitm '.($order['delivery']=='address'?'sbpd_deliveryitm_a':'').'" data-id="address">
	<div class="sbpd_label sbpd_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
	<div class="sbpd_value">Курьером по городу</div><br /></div>';

	if($city=='Ростов-на-Дону')
	{
		$pp .= '<div class="sbpd_deliveryitm '.($order['delivery']=='pvz'?'sbpd_deliveryitm_a':'').'" data-id="rnd">
		<div class="sbpd_label sbpd_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
		<div class="sbpd_value">Самовывоз
			<div class="sbpd_v_a">ул. Социалистическая, 103 &mdash; угол Газетного</div>
			<div class="sbpd_v_wt">пн-пт. 9:00 – 18:00</div>
		</div><br /></div>';
	}else{
		$rr= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE City='{$cityqq}' AND enabled='y' ORDER BY CoordY", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$several= (count($rr)>1?true:false);
			if($several)
			{
				$pp .= '<div class="sbpd_label">Самовывоз</div>
				<div class="sbpd_value">&mdash; выберите пункт выдачи заказов<br /><span class="deliverymap as1">на карте</span></div><br />';
			}

			foreach($rr AS $key=>$row)
			{
				$pp .= '<div class="sbpd_deliveryitm '.($order['delivery']=='pvz' && $order['pvz']==$row['id']?'sbpd_deliveryitm_a sbpd_deliveryitm_show':'').' sbpd_deliveryitm_'.$row['id'].' '.(count($rr)>5?'sbpd_deliveryitm_hide':'').'" data-id="'.$row['id'].'">
				<div class="sbpd_label sbpd_label_radio">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').'</div>
				<div class="sbpd_value">'.(count($rr)==1?'Самовывоз':'').'
					<div class="sbpd_v_a">'.$row['Address'].'</div>';
				if($row['WorkTime']) $pp .= '<div class="sbpd_v_wt">'.$row['WorkTime'].'</div>';
				$pp .= '</div><br /></div>';

				if($several)
				{
					$map .= '.add(
						new ymaps.Placemark(['.$row['CoordY'].','.$row['CoordX'].'], {
							address: "'.addslashes($row['Address']).'",
							worktime: "'.addslashes($row['WorkTime']).'",
							pvzchooseid: '.$row['id'].'
						},{
							balloonContentLayout: BalloonContentLayout,
							iconLayout: "default#image", iconImageHref: "assets/images/mappoint.png",
							iconImageSize: [56, 53], iconImageOffset: [-17, -52],
							balloonPanelMaxMapArea: 0
						})
					)';
				}
			}

			if($several)
			{
				$pp .= '<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>';
				$pp .= '<script type="text/javascript">
					var myMap, myMapFlag;
					function initYaMap()
					{
						if(myMapFlag) return;
						myMapFlag= true;
						myMap= new ymaps.Map("YaMap", {
							center: ['.$row['CoordY'].','.$row['CoordX'].'],
							zoom: 16,
							controls: ["zoomControl","searchControl","geolocationControl"]
						},{});

						var closemap= new ymaps.control.Button({
							data: {
								content: "Закрыть карту"
							},
							options: {
								selectOnClick: false,
								maxWidth: [50, 150, 200],
								size: "medium"
							}
						});
						closemap.events.add("click",function(){
							$(".shopbasket_deliverymap").removeClass("open");
							$(".shopbasket_deliverymap").hide();
						});
						myMap.controls.add(closemap, {float:"right"});

						var BalloonContentLayout= ymaps.templateLayoutFactory.createClass(
							"<div style=\"padding:5px 15px;\"><div style=\"font-weight:bold;padding-bottom:5px;font-size:120%;\">{{properties.address}}</div><div style=\"color:#777;padding-bottom:8px;\">{{properties.worktime}}</div><div><button style=\"padding:1px 7px;\" class=\"shopBasketPVZChoose\" data-id=\"{{properties.pvzchooseid}}\">Выбрать</button></div></div>",
							{
								build: function(){
									BalloonContentLayout.superclass.build.call(this);
									$(".shopBasketPVZChoose").bind("click", this.onButtonChooseClick);
								},
								clear: function(){
									$(".shopBasketPVZChoose").unbind("click", this.onButtonChooseClick);
									BalloonContentLayout.superclass.clear.call(this);
								},
								onButtonChooseClick: function(){
									var id= $(this).data("id");
									$(".sbp_delivery .sbpd_deliveryitm_"+id).trigger("click");
								}
							});
						myMap.geoObjects'.$map.';';
				if(count($rr)>=2) $pp .= 'myMap.setBounds(myMap.geoObjects.getBounds(),{ zoomMargin: 50 });';
				$pp .= '}
					</script>';

				$pp .= '<div class="shopbasket_deliverymap"><div class="shbd_black"><div class="shbd_white">
					<div id="YaMap" style="width:100%;height:100%;">&nbsp;</div>
				</div></div></div>';
			}
		}
	}

	return $pp;
}

function getShopBasketItemPrice($catid, $itemid, $editionid, $markup, $editionvalue=false)
{
	global $nc_core;
	$catid= intval($catid);
	$itemid= intval($itemid);
	$editionid= intval($editionid);
	$price= false;
	$row= $nc_core->db->get_results("SELECT ee.edition, ee.price, ee.range FROM BN_PG_Catalog AS cc
		INNER JOIN BN_PG_Catalog_Edition AS ee ON ee.idi=cc.id
			WHERE cc.parent={$catid} AND cc.id={$itemid} AND ee.id={$editionid} AND ee.enabled='y' AND cc.enabled='y' LIMIT 1", ARRAY_A);
	if(is_array($row) && count($row))
	{
		$price= $row[0]['price'];
		if($row[0]['range']=='y')
		{
			$edition= intval(preg_replace("/[^0-9]/", '', $editionvalue));
			$price *= $edition;
		}else{
			$edition= intval(preg_replace("/[^0-9]/", '', $row[0]['edition']));
		}
		$price += ($edition*$markup);
		$price= round($price);
	}
	return $price;
}

// CDEK
function shopDeliveryCalculator()
{
	global $nc_core;
	include_once($nc_core->DOCUMENT_ROOT.'/netcat/modules/scorn_shop/CalculatePriceDeliveryCdek.php');
	$order= getShopOrderStatus_1("*");

	// $ModeDeliveryId= ($order['delivery']=='address'?1:2); //Д-Д //Д-С
	$ModeDeliveryId= ($order['delivery']=='address'?3:4); //С-Д //С-С

	$senderCityId= 438;
	$receiverCityId= $senderCityId;
	$row= $nc_core->db->get_results("SELECT CityCode FROM BN_CDEK_City_Points WHERE City='".$nc_core->db->escape($order['city'])."' LIMIT 1",ARRAY_A);
	if(is_array($row) && count($row)) $receiverCityId= $row[0]['CityCode'];

	if(($ModeDeliveryId==2 || $ModeDeliveryId==4) && $receiverCityId==$senderCityId)
	{
		return array(true, 0, array(0,0), 0);
	}

	$calc= new CalculatePriceDeliveryCdek();
	$calc->setAuth('fb0fecb33d60d1ee825dedb4be66afd0', '25af75308af93f1b22046032c482c63b');
	$calc->setSenderCityId($senderCityId);
	$calc->setReceiverCityId($receiverCityId);

	// $calc->setTariffId(138);
	$calc->setModeDeliveryId($ModeDeliveryId);
	$calc->addTariffPriority(136);
	$calc->addTariffPriority(137);
	$calc->addTariffPriority(138);
	$calc->addTariffPriority(139);
	$calc->addTariffPriority(233);
	$calc->addTariffPriority(234);
	$calc->addTariffPriority(1);
	$calc->addTariffPriority(5);
	$calc->addTariffPriority(10);
	$calc->addTariffPriority(11);
	$calc->addTariffPriority(12);
	$calc->addTariffPriority(62);
	$calc->addTariffPriority(63);

	$rr= $nc_core->db->get_results("SELECT cc.dimensions, cc.weight, bb.`count`, bb.ed, bb.params FROM BN_Shop_Basket AS bb
		INNER JOIN BN_PG_Catalog AS cc ON cc.id=bb.itemid
		WHERE bb.code='{$order['code']}' AND bb.type!='simple' AND bb.enabled='y'",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			if( ! $row['dimensions']) $row['dimensions']= '210x297x50';

			$weight= floatval($row['weight']);
			$weight_flag= ($weight?true:false);
			$options= unserialize($row['params']);
			$options= $options['options'];
			// print_r($options);
			if(is_array($options) && count($options))
			{
				foreach($options AS $opt)
				{
					if( ! $weight_flag && floatval($opt['weight']))
					{
						$weight= $opt['weight'];
						break;
					}
					if($weight_flag)
					{
						if(floatval($opt['weight'])) $weight *= floatval($opt['weight']);
					}
				}
			}
			if( ! floatval($weight)) $weight= 300;

   //  		print_r(array(
   //  			'weight2'=>$weight,
			// ));

			$ed          = intval(preg_replace("/[^0-9]/", '', $row['ed']));
			$dimensions  = explode('x', $row['dimensions']);
			$dimensions0 = ceil(intval($dimensions[0])/10);
			$dimensions1 = ceil(intval($dimensions[1])/10);
			$dimensions2 = intval($dimensions[2]) *($ed/100);
			$dimensions2 = ceil($dimensions2/10);
			$volume      = ($dimensions[0]/1000)*($dimensions[1]/1000);
			$weight      = ceil($volume*floatval($weight)*$ed);
			$weight      = floatval($weight/1000);

   //  		print_r(array(
   //  			'ed1'         => $ed,
   //  			'ed2'         => $ed/100,
   //  			'dimensions'  => $row['dimensions'],
   //  			'dimensions2' => $dimensions2,
   //  			'volume'      => $volume,
   //  			'weight1'     => floatval($weight),
   //  			'weight2'     => $weight,
   //  			'weight3'     => $weight3,
			// ));

			for($kk=1; $kk<=$row['count']; $kk++) $calc->addGoodsItemBySize($weight, $dimensions0,$dimensions1,$dimensions2);
		}
	}

	if($calc->calculate()===true)
	{
		$calcres= $calc->getResult();
		$calcres['result']['price'] *= 1.1;
		return array(true, $calcres['result']['price'], array($calcres['result']['deliveryPeriodMin'],$calcres['result']['deliveryPeriodMax']), $calcres['result']['tariffId']);
	}else{
		$calcerr= $calc->getError();
		return array(false, $calcres['error']['code']);
	}
}
// CDEK

// ЯНДЕКС.ДИСК
function disk($url,$request='GET',$uniqurl=false,$file=false)
{
	$curl= curl_init();
	$curl_httpheader= array(
		'Accept: application/json',
		'Content-Type: application/json',
		'Authorization: OAuth AQAAAAAZ8yArAADLW-bIisEEYks3vjbOIDyMih0',
	);
	$curl_setopt_array= array(
		CURLOPT_CUSTOMREQUEST  => $request,
		CURLOPT_URL            => ($uniqurl?$url:'https://cloud-api.yandex.net/v1/disk/'.$url),
		CURLOPT_HTTPHEADER     => $curl_httpheader,
		CURLOPT_FRESH_CONNECT  => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
	);
	if($request=='PUT' && $file)
	{
		$fo= fopen($file, 'r');
		$curl_setopt_array[CURLOPT_PUT]= true;
		$curl_setopt_array[CURLOPT_INFILE]= $fo;
		$curl_setopt_array[CURLOPT_INFILESIZE]= filesize($file);
	}
	curl_setopt_array($curl,$curl_setopt_array);
	$response= curl_exec($curl);
	$response_header= curl_getinfo($curl);
	curl_close($curl);
	return array($response_header,$response);
}
// ЯНДЕКС.ДИСК
//--------------------------- SHOP ---------------------------------------------








//---------------------------- CITY --------------------------------------------
if(isset($_GET['clearcity'])){ print_r($_SESSION['mycity']); unset($_SESSION['mycity']); print '--'; print_r($_SESSION['mycity']); exit(); }


function changeMarkupBySize($markup, $text)
{
	$A3= 420*297;
	if(preg_match("/([0-9]{2,5})x([0-9]{2,5})/", $text, $matches)===1)
	{
		$size= $matches[1]*$matches[2];
		if($size!=$A3) $markup /= $A3/$size;
	}
	return $markup;
}


function cityList($link=false)
{
	global $nc_core;

	$rr= $nc_core->db->get_results("SELECT id, City, CityAlias FROM BN_CDEK_City_Points WHERE MainMain='y' AND enabled='y' GROUP BY City ORDER BY City", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$bukva2= false;

		$pp .= '<div class="hct_mainmains">';
		$pp .= '<div class="hct_icon">'.icon('star').'</div>';
		foreach($rr AS $row)
		{
			$pp .= '<div class="'.($link?'':'hct_itm').' hct_city '.($row['City']==$_SESSION['mycity']['city']?'hct_city_a':'').'" data-id="'.$row['id'].'">';
			if($link) $pp .= '<a href="/contacts/'.$row['CityAlias'].'/">'.$row['City'].'</a>';
			else $pp .= '<span>'.$row['City'].'</span>';
			$pp .= '</div>';
		}
		$pp .= '<br /></div><!-- /.hct_mainmains -->';
	}

	$rr= $nc_core->db->get_results("SELECT id, FO, Obl, City, CityAlias, MainCity FROM BN_CDEK_City_Points
			WHERE enabled='y' GROUP BY City ORDER BY FO, Obl, MainCity DESC, City", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			if($row['FO'] !== $fo)
			{
				$column['fo'] .= '<div class="hct_fo '.($row['FO']==$_SESSION['mycity']['fo']?'hct_fo_a':'').'" data-id="'.$row['id'].'"><span>'.$row['FO'].'</span></div>';
				if($fo) $column['obl'] .= '</div><!-- /.hct_obl_box -->';
				$column['obl'] .= '<div class="hct_obl_box hct_obl_box_'.$row['id'].' '.($row['FO']==$_SESSION['mycity']['fo']?'hct_obl_box_a':'').'">';
				$fo= $row['FO'];
			}

			if($row['Obl'] !== $obl)
			{
				$column['obl'] .= '<div class="hct_obl '.($row['Obl']==$_SESSION['mycity']['obl']?'hct_obl_a':'').'" data-id="'.$row['id'].'"><span>'.$row['Obl'].'</span></div>';
				if($obl) $column['city'] .= '</div><!-- /.hct_city_box -->';
				$column['city'] .= '<div class="hct_city_box hct_city_box_'.$row['id'].' '.($row['Obl']==$_SESSION['mycity']['obl']?'hct_city_box_a':'').'">';
				$obl= $row['Obl'];
			}

			$column['city'] .= '<div class="'.($link?'':'hct_itm').' hct_city '.($row['MainCity']=='y'?'hct_city_main':'').' '.($row['City']==$_SESSION['mycity']['city']?'hct_city_a':'').'" data-id="'.$row['id'].'">';
			if($link) $column['city'] .= '<a href="/contacts/'.$row['CityAlias'].'/">'.$row['City'].'</a>';
			else $column['city'] .= '<span>'.$row['City'].'</span>';
			$column['city'] .= '</div>';
		}
		$column['city'] .= '</div><!-- /.hct_city_box -->';
		$column['obl'] .= '</div><!-- /.hct_obl_box -->';
	}

	$pp .= '<div class="hct_columns">
		<div class="hct_column hct_column_fo"><div class="hct_column_tit font2">Федеральный округ</div>'.$column['fo'].'</div><!-- /.hct_column_fo -->
		<div class="hct_column hct_column_obl"><div class="hct_column_tit font2">Регион</div>'.$column['obl'].'</div><!-- /.hct_column_obl -->
		<div class="hct_column hct_column_city"><div class="hct_column_tit font2">Город</div>'.$column['city'].'</div><!-- /.hct_column_city -->
		<br />
	</div><!-- /.hct_columns -->';

	return $pp;
}

function cityPVZ($city)
{
	global $nc_core;

	$city= $nc_core->db->escape($city);
	$rr= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE City='{$city}' AND enabled='y' ORDER BY CoordY", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			$pp .= '<div class="cnpg_citypoint">
				<div class="cnpg_address font2">'.$row['Address'].'</div>';
			if(false && $row['Phone'])
			{
				$row['Phone']= str_replace("\n", '<br />', $row['Phone']);
				$pp .= '<div class="cnpg_phone">'.$row['Phone'].'</div>';
			}
			if($row['WorkTime']) $pp .= '<div class="cnpg_grafik">'.$row['WorkTime'].'</div>';
			$pp .= '</div>';

			$map .= '.add(
				new ymaps.Placemark(['.$row['CoordY'].','.$row['CoordX'].'], {
					balloonContentBody: "'.addslashes($row['Address']).'",
					balloonContentFooter: "'.addslashes($row['WorkTime']).'",
					hintContent: "'.addslashes($row['Address']).'"
				},{
					iconLayout: "default#image", iconImageHref: "assets/images/mappoint.png",
					iconImageSize: [56, 53], iconImageOffset: [-17, -52]
				})
			)';
		}

		$pp .= '<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>';
		$pp .= '<script type="text/javascript">
			var myMap;
			ymaps.ready(initYaMap);
			function initYaMap()
			{
				myMap= new ymaps.Map("YaMap", {
					center: ['.$row['CoordY'].','.$row['CoordX'].'],
					zoom: 16,
					controls: ["zoomControl","searchControl","fullscreenControl","geolocationControl"]
				},{});
				myMap.behaviors.disable("scrollZoom");
				myMap.geoObjects'.$map.';';
		if(count($rr)>=2) $pp .= 'myMap.setBounds(myMap.geoObjects.getBounds());';
		$pp .= '}
		</script>';
		$pp .= '<div style="height:10px;">&nbsp;</div>';
		$pp .= '<div id="YaMap" style="height:500px;">&nbsp;</div>';
	}
	return $pp;
}

function cityInfo($info='name', $alias=false)
{
	global $nc_core;

	// if($alias!==false) $alias= 'rostov-na-donu';

	if($info=='name') $info= 'city';
	if($info=='alias') $info= 'city_alias';
	if($info=='code') $info= 'city_code';

	$cityInfo= false;
	if($alias)
	{
		$row= $nc_core->db->get_results("SELECT City AS city, CityAlias AS city_alias, CityCode AS city_code, Obl AS obl, FO AS fo FROM BN_CDEK_City_Points WHERE CityAlias='{$alias}' LIMIT 1", ARRAY_A);
		if(is_array($row) && count($row)) $cityInfo= $row[0];
	}
	if( ! $cityInfo) $cityInfo= $_SESSION['mycity'];
	if(isset($cityInfo[$info])) return $cityInfo[$info];

	if($info=='pointaddress')
	{
		if(cityInfo('name',$alias)=='Ростов-на-Дону') return 'ул. Социалистическая, 103';
		$row= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE CityCode='".cityInfo('code',$alias)."' LIMIT 2", ARRAY_A);
		if(is_array($row) && count($row)==1) return $row[0]['Address'];
		else return 'Адреса пунктов выдачи';
	}
}

function setCity($city, $redirect=false, $confirmed=false)
{
	global $nc_core;
	$city= $nc_core->db->escape(trim(urldecode($city)));
	if($city)
	{
		$row= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE City='{$city}' LIMIT 1", ARRAY_A);
		if(is_array($row) && count($row))
		{
			$row= $row[0];
			// logs(session_id());
			$_SESSION['mycity']['city']       = $row['City'];
			$_SESSION['mycity']['city_code']  = $row['CityCode'];
			$_SESSION['mycity']['city_alias'] = $row['CityAlias'];
			$_SESSION['mycity']['obl']        = $row['Obl'];
			$_SESSION['mycity']['fo']         = $row['FO'];

			$ip= $nc_core->db->escape($_SERVER['REMOTE_ADDR']);
			$rr2= $nc_core->db->get_results("SELECT * FROM BN_City_User WHERE ip='{$ip}' LIMIT 1", ARRAY_A);
			if(is_array($rr2) && count($rr2))
			{
				$nc_core->db->query("UPDATE BN_City_User SET city='{$city}', ".($confirmed?"confirmed='y',":"")." dt=".time()." WHERE ip='{$ip}' LIMIT 1");
			}else{
				$nc_core->db->query("INSERT INTO BN_City_User SET ip='{$ip}', city='{$city}', dt=".time());
			}
			if($redirect)
			{
				header( 'location: /contacts/'.$row['CityAlias'].'/' );
				exit();
			}
			return true;
		}else return false;
	}else return false;
}

function definitionCity_FromIP($ip)
{
	global $nc_core;
	$ip= $nc_core->db->escape(sprintf("%u", ip2long($ip)));
	$row= $nc_core->db->get_results("SELECT ct.`name` AS city, ct.sh, ct.dl FROM BN_CIDR_Optim AS opt
		INNER JOIN BN_CIDR_Cities AS ct ON ct.code=opt.code
			WHERE opt.`from`<={$ip} AND {$ip}<=opt.`to` LIMIT 1", ARRAY_A);
	if(is_array($row) && count($row)) return $row[0]; else return false;
}

function cityConfirmedForm()
{
	global $nc_core;
	$ip= $nc_core->db->escape($_SERVER['REMOTE_ADDR']);
	$row= $nc_core->db->get_results("SELECT id FROM BN_City_User WHERE ip='{$ip}' AND confirmed='n' LIMIT 1", ARRAY_A);
	if(is_array($row) && count($row))
	{
		$pp .= '<div class="cityconfirmedform">
			<div class="ccf_1">Ваш город <b>'.cityInfo('name').'</b> ?</div>
			<div class="ccf_y">Да</div>
			<div class="ccf_n"><span class="as1">Выбрать другой</span></div>
			<br />
		</div>';
	}
	return $pp;
}

function myCity_Init()
{
	global $nc_core;

	if(isset($_SESSION['mycity']))
	{
		$row= $nc_core->db->get_results("SELECT * FROM BN_CDEK_City_Points WHERE City='".$_SESSION['mycity']['city']."' LIMIT 1", ARRAY_A);
		if(is_array($row) && count($row)) return true;
	}

	$ip= $nc_core->db->escape($_SERVER['REMOTE_ADDR']);
	$ip2= $nc_core->db->escape(sprintf("%u", ip2long($ip)));

	$setCity= false;

	$row= $nc_core->db->get_results("SELECT city FROM BN_City_User WHERE ip='{$ip}' LIMIT 1", ARRAY_A);
	if(is_array($row) && count($row))
	{
		$setCity= setCity($row[0]['city']);
	}

	if( ! $setCity)
	{
		$city= definitionCity_FromIP($ip);
		if($city['city'])
		{
			$setCity= setCity($city['city']);
			if( ! $setCity)
			{
				$rr= $nc_core->db->get_results("SELECT City, CoordX, CoordY FROM BN_CDEK_City_Points WHERE enabled='y'", ARRAY_A);
				if(is_array($rr) && count($rr))
				{
					$closestpoint['distance']= 999999;
					foreach($rr AS $row)
					{
						$distance= (($city['dl']-$row['CoordX'])*($city['dl']-$row['CoordX'])) + (($city['sh']-$row['CoordY'])*($city['sh']-$row['CoordY']));
						$distance= sqrt($distance);
						if($distance < $closestpoint['distance'])
						{
							$closestpoint= array(
								'city'      => $row['City'],
								'distance'  => $distance
							);
						}
					}
					$setCity= setCity($closestpoint['city']);
				}
			}
		}
	}

	if( ! $setCity) setCity('Ростов-на-Дону');
}


//---------------------------- CITY ---------------------------------









function OBOI_Favorite()
{
	global $nc_core;

	if( $_SESSION[ 'catalog_user_favorite' ] )
	{
		$qq= "";
		foreach( $_SESSION[ 'catalog_user_favorite' ] AS $fav )
		{
			$fav= $nc_core->db->escape($fav);
			$qq .= ( ! empty( $qq ) ? " OR " : "" ) ."mm.CatArticle='{$fav}'";
		}
		$rr= $nc_core->db->get_results( "SELECT mm.*, sd1.EnglishName AS EnglishName1, sd2.EnglishName AS EnglishName2 FROM Message181 AS mm
			INNER JOIN Subdivision AS sd2 ON sd2.Subdivision_ID=mm.Subdivision_ID
			INNER JOIN Subdivision AS sd1 ON sd1.Subdivision_ID=sd2.Parent_Sub_ID
			WHERE ( {$qq} ) AND mm.Checked=1", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			foreach($rr AS $row)
			{
				$row[ 'CatImage' ]= explode( ':', $row[ 'CatImage' ] );
				$image= 'netcat_files/'. $row[ 'CatImage' ][ 3 ];
				if( ! file_exists( $nc_core->DOCUMENT_ROOT .'/'. $image ) ) continue;
				$image= ImgCrop72( $image, 45, 45, false, true );

				$url= '/f/fcatalog/';
				if( $row[ 'EnglishName1' ] != 'fcatalog' ) $url .= $row[ 'EnglishName1' ] .'/';
				$url .= $row[ 'EnglishName2' ] .'/';

				$catlft_favorite .= '<img class="catitem_'. $row[ 'Message_ID' ] .'" src="'. $image .'" data-itemart="'. $row[ 'CatArticle' ] .'" data-url="'. $url . $row[ 'CatArticle' ] .'/" />';
			}
		}
	}
	if( $catlft_favorite ) $catlft_favorite= '<button class="favorite_clear" type="button">Очистить</button><br />'. $catlft_favorite;
		else $catlft_favorite= '<div class="txt">Добавьте&nbsp;изображения из&nbsp;каталога нажав&nbsp;на&nbsp;'.icon('heart-o').'</div>';
	return $catlft_favorite;
}

//---------------------------- OBOI Left Filter ---------------------
function OBOI_LeftFilter()
{
	global $nc_core;

// ---------- Продублировано в несколько функций --------------------
	$basecategory= '/f/fcatalog';

	if( ! $nc_core->subdivision->scorn_itemid)
	{
		$originalurl= (isset($_POST['filterprms']) ? $_POST['filterprms'] : $nc_core->url->get_parsed_url('path'));
		$url= substr($originalurl, strlen($basecategory)+1);
		$_SESSION['f_catalog_filter_memory']= $url;
	}else{
		$originalurl= $_SESSION['f_catalog_filter_memory'];
		$url= $originalurl;
	}

	$x_pos= strpos($url,'/x/');
	if($x_pos!==false)
	{
		$url_category= substr($url, 0, $x_pos).'/';
		$filterprms= substr($url, $x_pos+3, -1);
	}else{
		$url_category= str_replace('/'.$nc_core->subdivision->scorn_itemid.'/', '',$url);
		$filterprms= false;
	}

	$category= explode('/', $url_category);
	$podcategory= $category[1];
	$category= $category[0];
// ---------- Продублировано в несколько функций --------------------

	if($filterprms)
	{
		$filterprms_arr= explode('/', $filterprms);
		if(is_array($filterprms_arr) && count($filterprms_arr))
		{
			foreach($filterprms_arr AS $row)
			{
				$vals= explode('-', $row);
				$prm= array_shift($vals);
				$selected2[$prm]= str_replace($prm, '', $row);
				$selected2[$prm.'_first']= substr($selected2[$prm], 1,1);
				$selected2[$prm.'_last']= substr($selected2[$prm], -1,1);
				if($vals)
				{
					$tmp_actived= -1;
					foreach($vals AS $val)
					{
						$val= intval($val);
						$selected[$prm][$val]= true;
					}
				}
			}
		}
	}

	$pp .= '<div class="catalog_filter" data-categorypage="'.$basecategory.'/'.$url_category.'"
		data-catalogpage="'.(strpos($nc_core->url->get_parsed_url('path'), $basecategory)===0?'y':'n').'">';

	$pp .= '<div class="lm_tit font2"><span>Избранное</span></div>';
	$pp .= '<div class="catlft_favorite">'.OBOI_Favorite().'</div>';

	$rr= $nc_core->db->get_results("SELECT * FROM Subdivision WHERE Parent_Sub_ID=210 AND Checked=1 ORDER BY LabelColor DESC, Subdivision_Name",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp .= '<div class="lm_tit font2"><span>По теме</span></div><div class="catlft_bl catlft_bord"><div>';
		foreach($rr AS $row)
		{
			$pp .= '<a href="'.$basecategory.'/'.$row['EnglishName'].'/"
				style="'.($row['LabelColor']=='red'?'font-weight:bold;':'').'"
				class="catlft_prmval catlft_prmval__s catlft_itm '.($row['EnglishName']==$category?'ctft_param_val_selected':'').' font2"
				data-prm="s" data-val="'.$row['EnglishName'].'" '.($row['EnglishName']==$category?'data-selected="y"':'').'
				title="'.$row['Subdivision_Name'].'">'.icon('radio_unchecked').'<span>'.$row['Subdivision_Name'].'</span></a>';
		}
		$pp .= '</div></div>';
	}

	$rr= $nc_core->db->get_results("SELECT * FROM Classificator_CatPomesh WHERE Checked=1 ORDER BY CatPomesh_Priority",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp .= '<div class="lm_tit font2"><span>По типу помещения</span></div><div class="catlft_bl catlft_bord"><div>';

		foreach($rr AS $row)
		{
			$link= $basecategory.'/'.($category?$category.'/':'').($podcategory?$podcategory.'/':'');
			$link .= 'x/';
			$link .= 'p';
			//if( strpos( $selected2['p'], '-'.$row[ 'CatPomesh_ID' ] ) === false ) $link .= '-'.$row[ 'CatPomesh_ID' ];
			if($row['CatPomesh_ID']<$selected2['p_first']) $link .= '-'.$row['CatPomesh_ID'];
			$link .= $selected2['p'];
			if($row['CatPomesh_ID']>$selected2['p_last']) $link .= '-'.$row['CatPomesh_ID'];
			$link .= '/';
			if($selected2['o']) $link .= 'o'.$selected2['o'].'/';

			$tmp= ($selected['p'][$row['CatPomesh_ID']]?true:false);
			$pp .= '<a href="'.$link.'" class="catlft_prmval catlft_itm '.($tmp?'ctft_param_val_selected':'').' font2"
				data-prm="p" data-val="'.$row['CatPomesh_ID'].'" '.($tmp?'data-selected="y"':'').'
				title="'.$row['CatPomesh_Name'].'">'.icon('checkbox').'<span>'.$row['CatPomesh_Name'].'</span></a>';
		}
		$pp .= '</div></div>';
	}

	$rr= $nc_core->db->get_results("SELECT * FROM Classificator_CatColors WHERE Checked=1 ORDER BY CatColors_Priority",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp .= '<div class="lm_tit font2"><span>По цвету</span></div><div class="catlft_bl catlft_bord"><div class="catlft_clrs">';
		foreach($rr AS $row)
		{
			$tmp= ($selected['c'][$row['CatColors_ID']]?true:false);
			$pp .= '<div class="catlft_prmval catlft_clr '.($tmp?'ctft_param_val_selected':'').'" style="background-color:#'.$row['Value'].';"
				data-prm="c" data-val="'.$row['CatColors_ID'].'" '.($tmp?'data-selected="y"':'').'>'.icon('check').'</div>';
		}
		$pp .= '<div class="clr">&nbsp;</div></div></div>';
	}

	$rr= $nc_core->db->get_results("SELECT * FROM Classificator_CatItemOrient WHERE Checked=1 ORDER BY CatItemOrient_Priority",ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$pp .= '<div class="lm_tit font2"><span>Ориентация</span></div><div class="catlft_bl catlft_bord"><div>';
		foreach($rr AS $row)
		{
			$link= $basecategory.'/'.($category?$category.'/':'').($podcategory?$podcategory.'/':'');
			$link .= 'x/';
			if($selected2['p']) $link .= 'p'.$selected2['p'].'/';
			$link .= 'o';
			if($row['CatItemOrient_ID']<$selected2['o_first']) $link .= '-'.$row['CatItemOrient_ID'];
			$link .= $selected2['o'];
			if($row['CatItemOrient_ID']>$selected2['o_last']) $link .= '-'.$row['CatItemOrient_ID'];
			$link .= '/';

			$tmp= ($selected['o'][$row['CatItemOrient_ID']]?true:false);
			$pp .= '<a href="'.$link.'" class="catlft_prmval catlft_itm '.($tmp?'ctft_param_val_selected':'').' font2"
				data-prm="o" data-val="'.$row['CatItemOrient_ID'].'" '.($tmp?'data-selected="y"':'').'
				title="'.$row['CatItemOrient_Name'].'">'.icon('checkbox').'<span>'.$row['CatItemOrient_Name'].'</span></a>';
		}
		$pp .= '</div></div>';
	}

	$pp .= '</div>';

	return $pp;
}
//---------------------------- OBOI Left Filter ---------------------

//---------------------------- OBOI CATALOG -------------------------
function OBOI_Catalog()
{
	global $nc_core;

	$items_on_page= 30;

// ---------- Продублировано в несколько функций --------------------
	$basecategory= '/f/fcatalog';

	if( ! $nc_core->subdivision->scorn_itemid)
	{
		$originalurl= (isset($_POST['filterprms']) ? $_POST['filterprms'] : $nc_core->url->get_parsed_url('path'));
		$url= substr($originalurl, strlen($basecategory)+1);
		$_SESSION['f_catalog_filter_memory']= $url;
	}else{
		$originalurl= $_SESSION['f_catalog_filter_memory'];
		$url= $originalurl;
	}

	$x_pos= strpos($url,'/x/');
	if($x_pos!==false)
	{
		$url_category= substr($url, 0, $x_pos).'/';
		$filterprms= substr($url, $x_pos+3, -1);
	}else{
		$url_category= str_replace('/'.$nc_core->subdivision->scorn_itemid.'/', '',$url);
		$filterprms= false;
	}

	$category= explode('/', $url_category);
	$podcategory= $category[1];
	$category= $category[0];
// ---------- Продублировано в несколько функций --------------------


	$qqq= "";
	$filterprms_arr= explode( '/', $filterprms );
	if( $filterprms_arr )
	{
		foreach( $filterprms_arr AS $row )
		{
			$vals= explode( "-", $row );
			$prm= array_shift( $vals );
			if( $prm == 'pg' )
			{
				$pagenum= intval( $vals[ 0 ] );
				continue;
			}
			if( $vals )
			{
				$qq= "";
				foreach( $vals AS $val )
				{
					$val= intval( $val );
					$tmp= 'CatPomesh';
					if( $prm == 'o' ) $tmp= 'CatOrient';
					if( $prm == 'c' ) $tmp= 'CatColors';
					$qq .= ( ! empty( $qq ) ? " OR " : "" ) ."mm.`{$tmp}`".( $prm == 'o' ? "='{$val}'" : "LIKE '%,{$val},%'" );
				}
				if( $qq ) $qqq .= ( ! empty( $qqq ) ? " AND " : "" ) ."( {$qq} )";
			}
		}
	}
	if( $qqq ) $qqq= "AND ( {$qqq} )";

	if( ! $pagenum || $pagenum <= 0 ) $pagenum= 1;

	if( $category )
	{
		$category= $nc_core->db->escape( trim( $category ) );
		$rr= $nc_core->db->get_results( "SELECT Subdivision_ID, Subdivision_Name, EnglishName FROM Subdivision
			WHERE EnglishName='{$category}' AND Checked=1 LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$category_id= $rr[0]['Subdivision_ID'];
			$category_name= $rr[0]['Subdivision_Name'];
			$category_alias= $rr[0]['EnglishName'] .'/';
			$podcategory_alias= $category_alias;
		}
	}
	if( $podcategory )
	{
		$podcategory= $nc_core->db->escape( trim( $podcategory ) );
		$rr= $nc_core->db->get_results( "SELECT Subdivision_ID, Subdivision_Name, EnglishName FROM Subdivision
			WHERE Parent_Sub_ID={$category_id} AND EnglishName='{$podcategory}' AND Checked=1 LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$podcategory_id= $rr[0]['Subdivision_ID'];
			$podcategory_name= $rr[0]['Subdivision_Name'];
			$podcategory_alias= $category_alias . $rr[0]['EnglishName'] .'/';
			//$category_id= $podcategory_id;
		}
	}




	$print .= '<div class="pathway">';
	$print .= '<div class="pw_itm pwi_home"><a href="https://'.$nc_core->url->get_parsed_url('host').'/f/"><svg class="svgicon"><use xlink:href="assets/images/symbol-defs.svg#icon-home"></use></svg></a></div>';
	if( ! $category_id && ! $podcategory_id && ! $nc_core->subdivision->scorn_itemid)
		$print .= '<div class="pw_itm pwi_page">Каталог фотообоев</div>';
			else $print .= '<div class="pw_itm pwi_link"><a href="'.$basecategory.'/">Каталог фотообоев</a></div><div class="pw_itm pwi_tire">&mdash;</div>';
	if( $category_id )
	{
		if( ! $podcategory_id) $print .= '<div class="pw_itm pwi_page">'.$category_name.'</div>';
			else $print .= '<div class="pw_itm pwi_link"><a href="'.$basecategory.'/'.$category_alias.'">'.$category_name.'</a></div><div class="pw_itm pwi_tire">&mdash;</div>';
	}
	if($podcategory_id)
	{
		$print .= '<div class="pw_itm pwi_page">'.$podcategory_name.'</div>';
	}
	$print .= '</div>';

	
	if($category_name) $h1= $category_name;
	if($podcategory_name) $h1= $podcategory_name;
	$SEOPage= SEOPage('h1', $originalurl);
	if($SEOPage) $h1= $SEOPage;
	if( ! $h1) $h1= 'Каталог фотообоев';
	$print .= '<div class="pagetitle catalog_h1"><h1>'. $h1 .'</h1></div>';





	$print .= '<script>
(function($){
	$(document).ready(function(){
		if( $( ".cat_categories" ).length )
		{
			var elem= $( ".cat_categories" );
			var elem2= $( ".cat_categories .cat_categories_scrll" );
			var elemWidth= elem.outerWidth( true ) - 100;
			var itemWidth= $( ".catcat_itm", elem2 ).outerWidth( true );
			var elem2Width= $( ".catcat_itm", elem2 ).length * itemWidth;
			elem2.width( elem2Width );
			elem.mousemove(function(e){
				var left = (e.pageX - elem.offset().left - 100) * (elem2Width-elemWidth) / elemWidth;
				elem.scrollLeft(left);
			});
			$( ".catcat_lft" ).click(function(){
				elem.scrollLeft( elem.scrollLeft() - itemWidth );
			});
			$( ".catcat_rght" ).click(function(){
				elem.scrollLeft( elem.scrollLeft() + itemWidth );
			});
		}
	});
})(jQuery);
	</script>';

	$for_query= "";
	$rr= $nc_core->db->get_results( "SELECT sd.*, mm.CatImage FROM Subdivision AS sd
		LEFT JOIN Sub_Class AS sb ON sb.Subdivision_ID=sd.Subdivision_ID AND sb.Class_ID=181 AND sb.Checked=1
		LEFT JOIN Message181 AS mm ON mm.Subdivision_ID=sd.Subdivision_ID AND mm.Sub_Class_ID=sb.Sub_Class_ID AND mm.Checked=1
			WHERE sd.Parent_Sub_ID={$category_id} AND sd.Checked=1 GROUP BY sd.Subdivision_ID ORDER BY sd.Priority", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$print .= '<div class="cat_categories_wrapper"><div class="cat_categories"><div class="cat_categories_scrll">';
		foreach($rr AS $row)
		{
			$for_query .= " OR mm.Subdivision_ID='".$row[ 'Subdivision_ID' ]."'";

			$CatImage= explode( ':', $row[ 'CatImage' ] );
			$CatImage= ImgCrop72( 'netcat_files/'. $CatImage[ 3 ], 175, 135, true );

			$print .= '<div class="catcat_itm"><a href="'.$basecategory.'/'. $category_alias . $row[ 'EnglishName' ] .'/'.( $filterprms ? 'x/'.$filterprms.'/' : '' ).'">
				<div class="catcat_img"><img src="'. $CatImage .'" /></div>
				<div class="catcat_nm">'. $row[ 'Subdivision_Name' ] .'</div>
			</a></div>';
		}
		$print .= '<div class="clr">&nbsp;</div></div></div>';
		$print .= '<div class="catcat_strlk catcat_lft">&nbsp;</div><div class="catcat_strlk catcat_rght">&nbsp;</div>';
		$print .= '</div>';
	}
	if( $podcategory_id ) $for_query= "AND mm.Subdivision_ID='{$podcategory_id}'";


	$print .= '<div class="scalc_myfile scalc_myfile_floatnone font2">Загрузить свое изображение '.icon('upload').'
		<input id="scalc_myfile" type="file" /></div><div class="clr">&nbsp;</div>';


	if( $category_id && ! $podcategory_id ) $for_query= "AND ( mm.Subdivision_ID='{$category_id}'". $for_query ." )";

	if( $podcategory_id ) $category_id= $podcategory_id;

	$rr= $nc_core->db->get_results( "SELECT COUNT(mm.Subdivision_ID) AS cc FROM Message181 AS mm
		WHERE 1=1 {$for_query} {$qqq} AND mm.Checked=1", ARRAY_A);
	if(is_array($rr) && count($rr)) $pages= ceil( $rr[0]['cc'] / $items_on_page );
	if( $pagenum > $pages ) $pagenum= $pages;

	$rr= $nc_core->db->get_results( "SELECT mm.* FROM Message181 AS mm
		WHERE 1=1 {$for_query} {$qqq} AND mm.Checked=1 ORDER BY mm.Priority LIMIT ".( ($pagenum-1)*$items_on_page ).",{$items_on_page}", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			$CatImage= explode( ':', $row[ 'CatImage' ] );
			$CatImage_mini= ImgCrop72( 'netcat_files/'.$CatImage[3], 175, 135, true);
			//$CatImage_mini= ImgCrop72( 'netcat_files/'.$CatImage[3], 175, 135, true );
			//$CatImage_zoom= ImgCrop72( 'netcat_files/'.$CatImage[3], 999, 999 );
			$CatImage_zoom= 'netcat_files/'.$CatImage[3];

			//$print .= print_r( $nc_core->subdivision->get_by_id( $row[ 'Subdivision_ID' ] ) );
			$itemurl= $nc_core->subdivision->get_by_id( $row[ 'Subdivision_ID' ] );
			$itemurl= $itemurl[ 'Hidden_URL' ];

			$print .= '<div class="catitem catitem_'. $row[ 'Message_ID' ] .'" data-itemid="'. $row[ 'Message_ID' ] .'"
					data-url="'. $itemurl . $row[ 'CatArticle' ] .'/" data-art="'. $row[ 'CatArticle' ] .'">
				<div class="ci_img"><img src="'. $CatImage_mini .'" alt="'. addslashes( $row[ 'CatName' ] ) .'" /></div>
				<div class="ci_art"><a href="'. $itemurl . $row[ 'CatArticle' ] .'/">'. $row[ 'CatArticle' ] .'</a></div>
				<div class="ci_zoom"><a class="imagelightbox" href="'. $CatImage_zoom .'">&nbsp;</a></div>
				<div class="ci_btt">
					<div class="ci_slct font2">Рассчитать</div>
					<div class="ci_fvrt '.($_SESSION[ 'catalog_user_favorite' ][ $row[ 'CatArticle' ] ] ? 'ci_fvrt_2' : '' ).'">'.icon('heart-o','hearto').''.icon('heart','heart').'</div>
				</div>
			</div>';
		}
		$print .= '<div class="clr">&nbsp;</div>';
	}

	if( $pages > 1 )
	{
		$print .= '<div class="catalog_pages">';

		$print .= '<div class="catpg_tchki catpg_first">&nbsp;</div>';

		$prev= $pagenum - 1; if( $prev <= 1 ) $prev= 0;
		if( $prev ) $print .= '<div class="catlft_prmval catpg_strelk catlft_pg catlft_prmval__pg" data-prm="pg" data-tpe="one" data-val="'. $prev .'">«</div>';

		$visible_ot= $pagenum - 5; if( $visible_ot < 1 ) $visible_ot= 1;
		$visible_do= $pagenum + 5; if( $visible_do > $pages ) $visible_do= $pages;

		for( $pp= 1; $pp <= $pages; $pp++ )
		{
			if( $pp >= 3 && $pp < $visible_ot )
			{
				if( ! $first )
				{
					$print .= '<div class="catpg_tchki">...</div>';
					$first= true;
				}
				continue;
			}
			if( $pp <= $pages - 2 && $pp > $visible_do )
			{
				if( ! $second )
				{
					$print .= '<div class="catpg_tchki">...</div>';
					$second= true;
				}
				continue;
			}

			$print .= '<a href="'. $basecategory .'/'. $url_category .'x/pg-'. $pp .'/" class="catlft_prmval catlft_pg catlft_prmval__pg '.( $pagenum == $pp ? 'ctft_param_val_selected' : '' ).' font2" data-prm="pg" data-tpe="one" data-val="'. $pp .'" '.( $pagenum == $pp ? 'data-selected="y"' : '' ).'>'. $pp .'</a>';
		}

		$next= $pagenum + 1; if( $next >= $pages ) $next= 0;
		if( $next ) $print .= '<div class="catlft_prmval catpg_strelk catlft_pg catlft_prmval__pg" data-prm="pg" data-tpe="one" data-val="'. $next .'">»</div>';

		$print .= '<div class="clr">&nbsp;</div></div>';
	}

	$print= '<div class="catalog" data-categorypage="'. $basecategory .'/'. $url_category .'" data-catalogpage="y">'. $print .'</div>';

	$print .= '<script type="text/javascript" src="'.Compress('assets/js/oboi_catalog_itempage.js').'"></script>';


	$print .= '<div style="height:50px;">&nbsp;</div>'.SEOPage('text', $originalurl);


	return $print;
}



function OBOI_Catalog_Item()
{
	global $nc_core;

	$basecategory= '/f/fcatalog';

	$itemid= $nc_core->subdivision->scorn_itemid;
	if( ! $itemid ) $itemid= $_POST['itemart'];
	$itemid= $nc_core->db->escape($itemid);

	if($itemid=='w0000000')
	{
		$uploadeduserfile_flag= true;
		$image0= $_SESSION['oboiusersfiles'];
		if( ! file_exists($nc_core->DOCUMENT_ROOT.'/'.$image0)) return;
		$image= ImgCrop72($image0, 652, 503, true);
	}else{
		$rr= $nc_core->db->get_results("SELECT * FROM Message181 WHERE CatArticle='{$itemid}' AND Checked=1 LIMIT 1",ARRAY_A);
		if( ! is_array($rr) || ! count($rr)) return;
		$item= $rr[0];

		$item['CatImage']= explode(':', $item['CatImage']);
		$image0= 'netcat_files/'.$item['CatImage'][3];
		if( ! file_exists($nc_core->DOCUMENT_ROOT.'/'.$image0)) return;
		$image= ImgCrop72($image0, 652, 503, true);
	}

	$print .= '<div class="sccalc_wrapper">';

	$print .= '<div class="sccalc_center">
			<a class="scalc_myfile_a font2" href="'.$basecategory.'/'.$_SESSION['f_catalog_filter_memory'].'">« Каталог фотообоев</a>
			<div class="scalc_myfile font2">Загрузить свое изображение '.icon('upload').'<input id="scalc_myfile" type="file" /></div>
			<div class="clr">&nbsp;</div>
			<div class="scalc_myfiletxt"></div>';

	$rr= $nc_core->db->get_results("SELECT * FROM Classificator_CatTextures WHERE Checked=1 ORDER BY CatTextures_Priority", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		$print .= '<div class="sccalc_wpbox">';
		$print .= '<div class="sccalc_wptit font2">Текстура</div>';
		foreach($rr AS $row)
		{
			if( ! file_exists( $nc_core->DOCUMENT_ROOT .'/assets/images/wallpapers/wall-'. $row[ 'CatTextures_ID' ] .'.png' ) ) continue;
			$print .= '<div class="sccalcwp_item" data-wallp="'. $row[ 'CatTextures_ID' ] .'" data-wallpimg="wall-'. $row[ 'CatTextures_ID' ] .'.png">
				<div class="sccalcwpi_img" style="background:url(\'assets/images/wallpapers/wall-'. $row[ 'CatTextures_ID' ] .'.png\') center center;">&nbsp;</div>
				<div class="sccalcwpi_name font2">'. $row[ 'CatTextures_Name' ] .'</div>
			</div>';
		}
		$print .= '<br />';
		$print .= '</div>';
	}

	if($itemid!='w0000000') $print .= '<div style="font-size:18px;font-weight:100;padding:20px 0;">Арт. '.$itemid.'</div>';

	$print .= '<div class="sccalc_imgwrpp">
				<div class="sccalc_imgbox">
					<div class="sccalc_img"><img src="'.$image.'"></div>
					<div class="sccalc_wallp">&nbsp;</div>
					<div class="sccalc_selinterior">&nbsp;</div>
					<div class="sccalc_crop">
						<div class="sccalcc_kub" data-setww="325" data-sethh="250" data-settype="ww">
							<div class="sccalcck_tblr sccalcck_ll">&nbsp;</div>
							<div class="sccalcck_tblr sccalcck_rr">&nbsp;</div>
							<div class="sccalcck_tblr sccalcck_tt">&nbsp;</div>
							<div class="sccalcck_tblr sccalcck_bb">&nbsp;</div>
						</div>
					</div>
				</div>
			</div>';


	$print .= '<div class="sccalc_form">';
	$print .= '<form id="scalc_form" action="" method="post">';


	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab"></div>
		<div class="sccalc_fr_inp font2 scalc_101">Укажите размер</div>
		<br /></div>';
	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab font2">Ширина*</div>
		<div class="sccalc_fr_inp"><input id="size-width" class="size-value value-width" type="text" name="width" value="325" /> см</div>
		<br /></div>';
	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab font2">Высота</div>
		<div class="sccalc_fr_inp"><input id="size-height" class="size-value value-height" type="text" name="height" value="250" /> см</div>
		<br /></div>';

	$print .= '<div class="scalc_100"><p><b>*</b> Для печати используются флизелиновые и&nbsp;бумажные обои с&nbsp;виниловым покрытием ведущих европейских производителей. Максимальная ширина одного печатного полотна&nbsp;&mdash; 105&nbsp;см, а&nbsp;высота не&nbsp;ограничена. Если ваше изображение шире, мы&nbsp;делим его на&nbsp;равные части. К&nbsp;примеру, если вам необходима ширина 150&nbsp;см, мы&nbsp;печатаем изображение частями по&nbsp;75&nbsp;см.</p></div>';


	$print .= '<div class="sccalc_interiors_tit">Посмотрите фотообои в интерьере</div>';
	$print .= '<div class="sccalc_interiors_wrapper"><div class="sccalc_interiors">';
	$interiors_folder= 'assets/images/interiors/';
	$print .= '<div class="sccalc_intr"><span>Убрать<br />интерьер</span></div>';
	$ii= 1;
	while(file_exists($nc_core->DOCUMENT_ROOT.'/'.$interiors_folder.'interior-'.$ii.'.png'))
	{
		$print .= '<div class="sccalc_intr" style="background-image:url(\''.ImgCrop72($image0, 150, 115, true).'\');"><img src="'.$interiors_folder.'interior-'.$ii.'.png" /></div>';
		$ii++;
	}
	$print .= '<div class="clr">&nbsp;</div></div></div>';


	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab"></div>
		<div class="sccalc_fr_inp font2 scalc_101">Тип текстуры</div>
		<br /></div>';
	$print .= '<div class="sccalc_f_row"><label>
		<div class="sccalc_fr_lab sccalc_fr_lab2 scalcradio typeprintradio"><input type="radio" checked="checked" name="typeprint" value="1" /></div>
		<div class="sccalc_fr_inp">Премиум&nbsp;&mdash; 1290&nbsp;руб. за&nbsp;кв. м&nbsp;<span>(латексная печать на&nbsp;фактурных обоях)</span></div>
		</label><br /></div>';
	$print .= '<div class="sccalc_f_row"><label>
		<div class="sccalc_fr_lab sccalc_fr_lab2 scalcradio typeprintradio"><input type="radio" name="typeprint" value="2" /></div>
		<div class="sccalc_fr_inp">Стандарт&nbsp;&mdash; 990&nbsp;руб. за&nbsp;кв. м&nbsp;<span>(латексная печать на&nbsp;гладком флизелине)</span></div>
		</label><br /></div>';

	$print .= '<div style="height:30px;"></div>';
	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab"></div>
		<div class="sccalc_fr_inp font2 scalc_101" style="padding-bottom:10px;">Стоимость</div>
		<br /></div>';
	$print .= '<div class="sccalc_f_row sccalc_fr_sum">
		<div class="sccalc_fr_lab"></div>
		<div id="scalc_summ" class="sccalc_fr_inp font2"><span class="price">10 781</span> <span class="ruble">руб.</span></div>
		<br /></div>';

	$print .= '<div style="height:30px;"></div>';
	$print .= '<div class="sccalc_f_row">
		<div class="sccalc_fr_lab"></div>
		<div class="sccalc_fr_inp">
			<div class="sccalc_result"></div>
			<button class="addtoshopbasket font2" type="button">Добавить в корзину</button>
			<br />
			<div class="svgloading"></div>
		</div>
		<br /></div>';
	$print .= '';

	$print .= '<input type="hidden" id="scalc_itemid" name="itemid" value="'.$itemid.'" />
		<input type="hidden" id="scalc_idwallp" name="idwallp" value="" />
		<input type="hidden" id="scalc_userfile" name="designfile" value="'.$image0.'" />
		<input type="hidden" id="scalc_kub_ll" name="kub_ll" value="0" />
		<input type="hidden" id="scalc_kub_tt" name="kub_tt" value="0" />
		<input type="hidden" id="scalc_kub_ww" name="kub_ww" value="0" />
		<input type="hidden" id="scalc_kub_hh" name="kub_hh" value="0" />
	</form>';

	$print .= '</div>';
	$print .= '</div>';
	$print .= '</div>';

	$print .= '<script type="text/javascript" src="'.Compress('assets/js/oboi_catalog_itempage.js').'"></script>';

	return $print;
}
//---------------------------- OBOI CATALOG -------------------------











// FUNCTIONS --------------------------------------------------------


function SEOPage( $type, $url )
{
	global $nc_core;
	$return= false;
	$url= $nc_core->db->escape( $url );
	$seopageinfo= $nc_core->db->get_results( "SELECT * FROM SEO_CatalogPages WHERE params='{$url}' AND enabled='y' LIMIT 1", ARRAY_A);
	if(is_array($seopageinfo) && count($seopageinfo))
	{
		if( $type == 'meta' )
		{
			$return .= "\t";
			$return .= '<title>'. $seopageinfo[0]['title'] .'</title>';
			$return .= "\n\t";
			$return .= '<meta name="description" content="'. $seopageinfo[0]['description'] .'" />';
			$return .= "\n\t";
			$return .= '<meta name="keywords" content="'. $seopageinfo[0]['keywords'] .'" />';
			$return .= "\n";

		}elseif( $type == 'text' ){
			$return .= $seopageinfo[0]['text'];

		}elseif( $type == 'h1' ){
			$return .= $seopageinfo[0]['h1'];
		}
	}
	return $return;
}

function megaForm_Files()
{
	$pp .= '<div class="mfb_files_list">';
	if(is_array($_SESSION['megaform']['files']) && count($_SESSION['megaform']['files']))
	{
		foreach($_SESSION['megaform']['files'] AS $key => $row)
		{
			$pp .= '<div class="sbpdtfl_itm" data-id="'.$key.'">
				<div class="sbpdtfli_nm">'.$row[0].'</div>
				<div class="sbpdtfli_del">'.icon('cross').'</div>
			</div>';
		}
	}
	$pp .= '</div>';
	return $pp;
}

function keyboardLayout($txt)
{
	$arr= array(
		'q'=>'й', 'w'=>'ц', 'e'=>'у', 'r'=>'к', 't'=>'е', 'y'=>'н', 'u'=>'г', 'i'=>'ш', 'o'=>'щ', 'p'=>'з', '['=>'х', ']'=>'ъ',
		'a'=>'ф', 's'=>'ы', 'd'=>'в', 'f'=>'а', 'g'=>'п', 'h'=>'р', 'j'=>'о', 'k'=>'л', 'l'=>'д', ';'=>'ж', "'"=>'э',
		'z'=>'я', 'x'=>'ч', 'c'=>'с', 'v'=>'м', 'b'=>'и', 'n'=>'т', 'm'=>'ь', ','=>'б', '.'=>'ю'
	);
	$txt= strtolower($txt);
	$txt= strtr($txt, $arr);
	return $txt;
}

function generAlias($alias)
{
	$trans= array("а"=>"a", "б"=>"b", "в"=>"v", "г"=>"g", "д"=>"d", "е"=>"e",
		"ё"=>"jo", "ж"=>"zh", "з"=>"z", "и"=>"i", "й"=>"jj", "к"=>"k", "л"=>"l",
		"м"=>"m", "н"=>"n", "о"=>"o", "п"=>"p", "р"=>"r", "с"=>"s", "т"=>"t", "у"=>"u",
		"ф"=>"f", "х"=>"kh", "ц"=>"c", "ч"=>"ch", "ш"=>"sh", "щ"=>"shh", "ы"=>"y",
		"э"=>"eh", "ю"=>"yu", "я"=>"ya", "А"=>"a", "Б"=>"b", "В"=>"v", "Г"=>"g",
		"Д"=>"d", "Е"=>"e", "Ё"=>"jo", "Ж"=>"zh", "З"=>"z", "И"=>"i", "Й"=>"jj",
		"К"=>"k", "Л"=>"l", "М"=>"m", "Н"=>"n", "О"=>"o", "П"=>"p", "Р"=>"r", "С"=>"s",
		"Т"=>"t", "У"=>"u", "Ф"=>"f", "Х"=>"kh", "Ц"=>"c", "Ч"=>"ch", "Ш"=>"sh",
		"Щ"=>"shh", "Ы"=>"y", "Э"=>"eh", "Ю"=>"yu", "Я"=>"ya");
	$alias= strip_tags(strtr($alias, $trans));
	$alias= strtolower($alias);
	$alias= preg_replace("/[^a-z0-9-\.]/", "-", $alias);
	$alias= preg_replace('/([-]){2,}/', '\1', $alias);
	$alias= trim($alias, '-');
	return $alias;
}


function logs($txt)
{
	global $nc_core;
	$txt= $nc_core->db->escape($txt);
	$nc_core->db->query("INSERT INTO BN_Logs SET txt='{$txt}', dth='".date('Y.m.d--H:i:s')."'");
}


function icon($icon, $class='', $file='symbol-defs')
{
	return '<svg class="svgicon '.$class.'"><use xlink:href="assets/images/'.$file.'.svg#icon-'.$icon.'"></use></svg>';
}


function getIdLvl($lvl0, $id, $lvl=false, $fields='', $field=false)
{
	//getIdLvl
	//v1.0
	//15.10.2016
	//-------------------------------------------------------------
	global $nc_core;

	$id= intval($id);
	$deffields= "Subdivision_ID AS id, Parent_Sub_ID AS parent";
	$fields_qq= ($fields?",".$nc_core->db->escape($fields):"");
	$rr= $nc_core->db->get_results("SELECT {$deffields} {$fields_qq} FROM Subdivision WHERE Subdivision_ID={$id} LIMIT 1", ARRAY_A);
	if(is_array($rr) && count($rr)==1) $doc= $rr[0]; else return false;
	$list[]= $doc;

	while($id!=$lvl0 && $doc['parent']!=$lvl0 && $doc['parent']>0)
	{
		$rr= $nc_core->db->get_results("SELECT {$deffields} {$fields_qq} FROM Subdivision WHERE Subdivision_ID={$doc[parent]} LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr)==1) $doc= $rr[0]; else return false;
		$list[]= $doc;
	}
	if($doc['parent']==0)
	{
		$list[]= array('id'=>0);
	}elseif($doc['parent']==$lvl0){
		$rr= $nc_core->db->get_results("SELECT {$deffields} {$fields_qq} FROM Subdivision WHERE Subdivision_ID={$doc[parent]} LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr)==1) $doc= $rr[0]; else return false;
		$list[]= $doc;
	}
	$list[]= false;
	$list= array_reverse($list);
	return ($lvl ? $list[$lvl][($field?$field:'id')] : $list);
}

function Price($price, $round=0)
{
	if(empty($delimiter)) $delimiter= '&thinsp;';
	if(empty($round)) $round= 0;
	$price= str_replace(",", ".", $price);
	$price= preg_replace("/[^0-9\.]/", "", $price);
	$price= round($price, $round);
	if($price<=0 || $price=='') return "&mdash;";
	$tmp= explode(",", $price);
	$itogo_price= '';
	$ii= 0;
	for($kk=strlen($tmp[0])-1; $kk>=0; $kk--)
	{
		$ii++;
		$itogo_price= substr($tmp[0], $kk, 1).$itogo_price;
		if($ii%3==0 && $kk>0)
		{
			$itogo_price= $delimiter.$itogo_price;
		}
	}
	if(strlen($tmp[1])<$round) $tmp[1]= str_pad($tmp[1], $round, '0', STR_PAD_RIGHT);
	if($tmp[1]) $itogo_price .= ','.$tmp[1];
	return $itogo_price;
}

function text_gradient($text, $from, $to)
{
	$letterscc= mb_strlen($text);
	$from= explode(',', $from);
	$to= explode(',', $to);
	$rgb[0]= round(($to[0]-$from[0])/($letterscc-1));
	$rgb[1]= round(($to[1]-$from[1])/($letterscc-1));
	$rgb[2]= round(($to[2]-$from[2])/($letterscc-1));
	for($ii=0; $ii<$letterscc; $ii++)
	{
		$r= $from[0]+($rgb[0]*$ii);
		$g= $from[1]+($rgb[1]*$ii);
		$b= $from[2]+($rgb[2]*$ii);
		if($ii==$letterscc-1)
		{
			$r= $to[0];
			$g= $to[1];
			$b= $to[2];
		}
		$result .= '<span style="color:rgb('.$r.','.$g.','.$b.');">'.mb_substr($text, $ii, 1).'</span>';
	}
	return $result;
}


function ImgRamka10($img, $toimg, $xx=0, $yy=0, $ww=0, $hh=0)
{
	global $nc_core;

	$img= urldecode( trim( $img ) );
	$toimg= urldecode( trim( $toimg ) );
	$xx= ( empty( $xx ) ? 0 : intval( $xx ) );
	$yy= ( empty( $yy ) ? 0 : intval( $yy ) );
	$ww= ( empty( $ww ) ? 0 : intval( $ww ) );
	$hh= ( empty( $hh ) ? 0 : intval( $hh ) );

	$slash= ( substr( $img, 0, 1 ) == '/' ? true : false );
	if( ! $slash && substr( $toimg, 0, 1 ) == '/' ) $toimg= substr( $toimg, 1 );
	$root= rtrim( $nc_core->DOCUMENT_ROOT, "/\\" ) . ( $slash ? '' : '/' );

	if( ! file_exists( $root . $img ) || ! is_file( $root . $img ) ) return false;

	$newimg_path= $root . $toimg;

	if( filesize( $root . $img ) > 1024*1024*10 ) return $img;
//========================================================================================
	if( true )
	{
		$img1_info= @getimagesize( $root . $img );
		if( ! $img1_info[ 1 ] ) return false;

		if( $img1_info[ 2 ] == 1 ) $img1= @imagecreatefromgif( $root . $img );

		elseif( $img1_info[ 2 ] == 2 ) $img1= @imagecreatefromjpeg( $root . $img );

		elseif( $img1_info[ 2 ] == 3 ){
			$img1= @imagecreatefrompng( $root . $img );
			$png= true;
		}

		if( $png )
		{
			@imagealphablending( $img2, true );
			@imagesavealpha( $img2, true );
		}else{
		}

		$white= imagecolorallocate( $img1, 255, 255, 255 );
		$black= imagecolorallocate( $img1, 0, 0, 0 );

		imagerectangle( $img1, $xx, $yy, $xx+$ww, $yy+$hh, $white );
		imagerectangle( $img1, $xx+1, $yy+1, $xx+$ww+1, $yy+$hh+1, $black );

		if( $png ){
			@imagepng( $img1, $newimg_path );
		}elseif( $img1_info[ 2 ] == 1 ){
			@imagegif( $img1, $newimg_path, 100 );
		}elseif( $img1_info[ 2 ] == 2 ){
			@imagejpeg( $img1, $newimg_path, 100 );
		}
		@chmod( $newimg_path, 0777 );
		@imagedestroy( $img1 );
	}
	return true;
}

function ImgCrop72($img, $w=0, $h=0, $backgr=false, $fill=false, $bgcolor='', $wm=false, $fullpath=false, $quality=80, $toimg=false, $r=false)
{
	global $nc_core;
	// v7.2
	// 15.09.2016
	// ImgCrop
	/*
		$img    = assets/images/img.jpg
		$dopimg = assets/images/dopimg.jpg
		$toimg  = assets/images/toimg.jpg

		$w         = (int)156
		$h         = (int)122
		$backgr    = 0/1
		$fill      = 0/1
		$x         = center/left/right
		$y         = center/top/bottom
		$bgcolor   = R,G,B,A / x:y / fill:a;b;c;d|b;c;d
		$wm        = 0/1
		$filter    = a;b;c;d|b;c;d
		$png       = 0/1
		$ellipse   = max / (int)56
		$degstep   = (int)5
		$dopimg_xy = x:y
		$quality   = (int)80
		$fullpath
		$r= 0/1
	*/
	//--------------------------------------------------------------------------------------
	$ipathnotphoto= 'assets/images/nophoto.png';
	$ipathwatermark= 'assets/images/watermark.png';
	//--------------------------------------------------------------------------------------
	//
	//
	//
	//
	//
	//
	//
	//
	//--------------------------------------------------------------------------------------
	$w= intval($w);
	$h= intval($h);
	$backgr= ($backgr===true || $backgr==='true' ? true : false);
	$fill= ($fill===true || $fill==='true' ? true : false);
	$x= ($x=='right' ? $x : 'center');
	$y= ($y=='bottom' ? $y : 'center');
	$bgcolor= (empty($bgcolor) ? '255,255,255,127' : $bgcolor);
	$wm= (empty($wm) ? false : $wm);
	$png= (empty($png) ? false : $png);
	$filter= (empty($filter) ? -1 : $filter);
	$refresh= (empty($r) ? false : true);
	$ellipse= ($ellipse == 'max' ? 'max' : intval($ellipse));
	$quality= intval($quality);
	if($quality === 0) $quality= ($_GET['ww']<=800 ? 60 : 80);
	else $quality= ($quality<0 || $quality>100 ? 80 : $quality);
	$base= ltrim($nc_core->DOCUMENT_ROOT, DIRECTORY_SEPARATOR);
	$img= trim(urldecode($img));
	$slashflag= (strpos($img, DIRECTORY_SEPARATOR)===0 ? true : false);
	if($slashflag) $img= ltrim($img, DIRECTORY_SEPARATOR);
	$baseflag= ($base && strpos($img, $base)===0 ? true : false);
	if($baseflag) $img= ltrim($img, $base);
	$root= $nc_core->DOCUMENT_ROOT.DIRECTORY_SEPARATOR;
	if($dopimg)
	{
		$dopimg= trim(urldecode($dopimg));
		$dopimg= ltrim($dopimg, DIRECTORY_SEPARATOR);
		$dopimg= ltrim($dopimg, $base);
		$dopimg= $root.$dopimg;
	}
	if($toimg)
	{
		$toimg= trim(urldecode($toimg));
		$toimg= ltrim($toimg, DIRECTORY_SEPARATOR);
		// $toimg= ltrim($toimg, $base);
	}
	if( ! file_exists($root.$img) || ! is_file($root.$img))
	{
		$img= $ipathnotphoto;
		if($fill){ $fill= false; $backgr= true; $bgcolor= '1:1'; }
	}
	if( ! file_exists($root.$img) || ! is_file($root.$img)) return false;
	if($wm && ( ! file_exists($root.$ipathwatermark) || ! is_file($root.$ipathwatermark))) return false;
	if( ! $toimg)
	{
		$imgrassh= substr($img, strrpos($img,'.'));
		$newimg= '_th'.md5($img . $w . $h . $backgr . $fill . $x . $y . $bgcolor . $wm . $filter . $ellipse . $dopimg . $quality) . ($png ? '.png' : $imgrassh);
		$newimg_dir= dirname($img) .DIRECTORY_SEPARATOR.'.th'.DIRECTORY_SEPARATOR;
		if( ! file_exists($root.$newimg_dir)) mkdir($root.$newimg_dir, 0777);
		$newimg_path= $root.$newimg_dir.$newimg;
		$newimg_path_return= ($fullpath ? MODX_SITE_URL : ($slashflag?DIRECTORY_SEPARATOR:'').($baseflag?$base:'')) .$newimg_dir .$newimg;
	}else{
		$newimg_path= $root.$toimg;
		$newimg_path_return= ($fullpath ? MODX_SITE_URL : ($slashflag?DIRECTORY_SEPARATOR:'').($baseflag?$base:'')) .$toimg;
	}
	if( ! file_exists($newimg_path) || filectime($root.$img) > filectime($newimg_path)) $refresh= true;
	if(filesize($root.$img) > 1024*1024*10) return $img;
	//--------------------------------------------------------------------------------------
	if( $refresh )
	{
		$img1_info= getimagesize( $root . $img );
		if( ! $img1_info[ 1 ] ) return false;
		$ot= $img1_info[ 0 ] / $img1_info[ 1 ];
		$dstW= ( $w > 0 ? $w : $img1_info[ 0 ] );
		$dstH= ( $h > 0 ? $h : $img1_info[ 1 ] );
		$dstX= 0;
		$dstY= 0;
		$srcW= $img1_info[ 0 ];
		$srcH= $img1_info[ 1 ];
		$srcX= 0;
		$srcY= 0;
		if( $fill )
		{
			$srcW= $img1_info[ 0 ];
			$srcH= round( $img1_info[ 0 ] / ( $dstW / $dstH ) );
			if( $srcH > $img1_info[ 1 ] )
			{
				$srcW= round( $img1_info[ 1 ] / ( $dstH / $dstW ) );
				$srcH= $img1_info[ 1 ];
			}
			if( $x == 'center' ) $srcX= round( ( $img1_info[ 0 ] - $srcW ) / 2 );
			if( $x == 'right' ) $srcX= $img1_info[ 0 ] - $srcW;
			if( $y == 'center' ) $srcY= round( ( $img1_info[ 1 ] - $srcH ) / 2 );
			if( $y == 'bottom' ) $srcY= $img1_info[ 1 ] - $srcH;
		}else{
			if( ( $img1_info[ 0 ] > $w && $w > 0 ) || ( $img1_info[ 1 ] > $h && $h > 0 ) )
			{
				$dstH= round( $dstW / $ot );
				if( $dstH > $h && $h > 0 )
				{
					$dstH= $h;
					$dstW= round( $dstH * $ot );
				}
			}else{
				$dstW= $img1_info[ 0 ];
				$dstH= $img1_info[ 1 ];
			}
			if( $backgr )
			{
				if( $dstW < $w )
				{
					if( $x == 'center' ) $dstX= round( ( $w - $dstW ) / 2 );
					if( $x == 'right' ) $dstX= $w - $dstW;
				}
				if( $dstH < $h )
				{
					if( $y == 'center' ) $dstY= round( ( $h - $dstH ) / 2 );
					if( $y == 'bottom' ) $dstY= $h - $dstH;
				}
			}
		}
		$crW= ( $backgr && $w > 0 ? $w : $dstW );
		$crH= ( $backgr && $h > 0 ? $h : $dstH );
		if( strstr( $bgcolor, "," ) )
		{
			$rgba_arr= explode( ",", $bgcolor );
			for( $kk=0; $kk<=3; $kk++ )
			{
				$rgba_arr[ $kk ]= intval( $rgba_arr[ $kk ] );
				if( $kk <= 2 && ( $rgba_arr[ $kk ] < 0 || $rgba_arr[ $kk ] > 255 ) ) $rgba_arr[ $kk ]= 255;
				if( $kk == 3 && ( $rgba_arr[ $kk ] < 0 || $rgba_arr[ $kk ] > 127 ) ) $rgba_arr[ $kk ]= 127;
			}
			$bgcolor= 'rgba';
		}elseif( strpos( $bgcolor, 'fill:' ) === 0 ){
			$effect= substr( $bgcolor, strpos( $bgcolor, ':' )+1 );
			$bgcolor= 'fill';
		}else{
			$coord_arr= explode( ":", $bgcolor );
			$bgcolor= 'coord';
		}
		//--------------------------------------------------------------------------------------
		if($img1_info[2] == 1) $img1= imagecreatefromgif($root.$img);
		elseif($img1_info[2] == 2) $img1= imagecreatefromjpeg($root.$img);
		elseif($img1_info[2] == 6) $img1= imagecreatefromwbmp($root.$img);
		elseif($img1_info[2] == 3){ $img1= imagecreatefrompng($root.$img); $png= true; }
		if( $bgcolor == 'coord' )
		{
			$col= imagecolorat( $img1, $coord_arr[ 0 ], $coord_arr[ 1 ] );
			$bgcolor= imagecolorsforindex( $img1, $col );
			$rgba_arr[ 0 ]= $bgcolor[ 'red' ];
			$rgba_arr[ 1 ]= $bgcolor[ 'green' ];
			$rgba_arr[ 2 ]= $bgcolor[ 'blue' ];
			$rgba_arr[ 3 ]= $bgcolor[ 'alpha' ];
		}
		$img2= ImageCreateTrueColor( $crW, $crH );
		if( $png )
		{
			imagealphablending( $img2, true );
			imagesavealpha( $img2, true );
			$col= imagecolorallocatealpha( $img2, $rgba_arr[ 0 ], $rgba_arr[ 1 ], $rgba_arr[ 2 ], $rgba_arr[ 3 ] );
		}else{
			$col= imagecolorallocate( $img2, $rgba_arr[ 0 ], $rgba_arr[ 1 ], $rgba_arr[ 2 ] );
		}
		if( $bgcolor == 'fill' )
		{
			imagecopyresampled( $img2, $img1, 0, 0, 0, 0, $crW, $crH, $img1_info[0], $img1_info[1] );
			$effect= explode( '|', $effect );
			if( ! empty( $effect ) )
			{
				foreach( $effect AS $row )
				{
					$tmp= explode( ';', $row );
					if( $tmp[ 0 ] == 2 || $tmp[ 0 ] == 3 || $tmp[ 0 ] == 10 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ] );
					elseif( $tmp[ 0 ] == 4 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ], $tmp[ 2 ], $tmp[ 3 ], $tmp[ 4 ] );
					elseif( $tmp[ 0 ] == 11 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ], $tmp[ 2 ] );
					else imagefilter( $img2, $tmp[ 0 ] );
				}
			}
		}else{
			imagefill( $img2, 0,0, $col );
		}
		imagecopyresampled($img2, $img1, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		if( $wm )
		{
			$wm_info= getimagesize($root.$ipathwatermark);
			$img3= imagecreatefrompng($root.$ipathwatermark);
			$Wcc= ceil($dstW/$wm_info[0]);
			$Hcc= ceil($dstH/$wm_info[1]);
			for($yy=1; $yy<=$Hcc; $yy++)
			{
				for($xx=1; $xx<=$Wcc; $xx++)
				{
					imagecopyresampled($img2, $img3, ($xx-1)*$wm_info[0], ($yy-1)*$wm_info[1], 0, 0, $wm_info[0], $wm_info[1], $wm_info[0], $wm_info[1]);
				}
			}
			imagedestroy($img3);
		}
		$filter= explode( '|', $filter );
		if( ! empty( $filter ) )
		{
			foreach( $filter AS $row )
			{
				$tmp= explode( ';', $row );
				if( $tmp[ 0 ] == 2 || $tmp[ 0 ] == 3 || $tmp[ 0 ] == 10 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ] );
				elseif( $tmp[ 0 ] == 4 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ], $tmp[ 2 ], $tmp[ 3 ], $tmp[ 4 ] );
				elseif( $tmp[ 0 ] == 11 ) imagefilter( $img2, $tmp[ 0 ], $tmp[ 1 ], $tmp[ 2 ] );
				else imagefilter( $img2, $tmp[ 0 ] );
			}
		}
		if( $ellipse )
		{
			$degstep= ( $degstep ? intval( $degstep ) : 5 );
			$w= ( $crW > $crH ? $crH : $crW );
			$cntr= ($w/2);
			$coord= array();
			$opacitycolor= imagecolorallocatealpha( $img2, 255, 255, 255, 127 );
			if( $ellipse == 'max' ) $ellipse_r= $cntr-1; else $ellipse_r= $ellipse;
			for( $part=1; $part<=4; $part++ )
			{
				for( $deg=0; $deg<90; $deg+=$degstep )
				{
					$mydeg= $deg;
					if( $part == 2 || $part == 4 ) $mydeg= 90 - $deg;
					if( ! $coord[ $mydeg ][ 'x' ] ) $coord[ $mydeg ][ 'x' ]= round( $ellipse_r * cos( deg2rad( $mydeg ) ) );
					if( ! $coord[ $mydeg ][ 'y' ] ) $coord[ $mydeg ][ 'y' ]= round( $ellipse_r * sin( deg2rad( $mydeg ) ) );
					$x= $coord[ $mydeg ][ 'x' ];
					$y= $coord[ $mydeg ][ 'y' ];
					if( $part == 4 ){ $y *= -1; }
					if( $part == 3 ){ $x *= -1; $y *= -1; }
					if( $part == 2 ){ $x *= -1; }
					$points[]= $cntr + $x;
					$points[]= $cntr + $y;
				}
			}
			$points[]= $cntr + $ellipse_r; $points[]= $cntr;
			$points[]= $w; $points[]= $cntr;
			$points[]= $w; $points[]= $w;
			$points[]= 0; $points[]= $w;
			$points[]= 0; $points[]= 0;
			$points[]= $w; $points[]= 0;
			$points[]= $w; $points[]= $cntr;
			$png= true;
			imagealphablending( $img2, false );
			imagesavealpha( $img2, true );
			imagefilledpolygon( $img2, $points, count($points)/2, $opacitycolor );
			//$autrum= imagecolorallocate( $img2, 216, 181, 85 );
			//imageellipse( $img2, $cntr, $cntr, $ellipse_r*2, $ellipse_r*2, $autrum );
		}
		if($dopimg)
		{
			if($dopimg_xy) $dopimg_xy= explode(':', $dopimg_xy);
			imagealphablending($img2, true);
			imagesavealpha($img2, true);
			$dopimg_info= getimagesize($dopimg);
			$img3= imagecreatefrompng($dopimg);
			$diX= round(($crW - $dopimg_info[0]) /2) + ($dopimg_xy[0] ? intval($dopimg_xy[0]) : 0);
			$diY= round(($crH - $dopimg_info[1]) /2) + ($dopimg_xy[1] ? intval($dopimg_xy[1]) : 0);
			imagecopyresampled($img2, $img3, $diX, $diY, 0, 0, $dopimg_info[0], $dopimg_info[1], $dopimg_info[0], $dopimg_info[1]);
			imagedestroy($img3);
		}
		//--------------------------------------------------------------------------------------
		if($png) imagepng($img2, $newimg_path);
		elseif($img1_info[2] == 1) imagegif($img2, $newimg_path, $quality);
		elseif($img1_info[2] == 2) imagejpeg($img2, $newimg_path, $quality);
		elseif($img1_info[2] == 6) imagewbmp($img2, $newimg_path);

		chmod($newimg_path, 0755);
		imagedestroy($img1);
		imagedestroy($img2);
	}
	return $newimg_path_return;
}

function Compress($file=false, $files=false, $tofile=false, $compress=true, $print=false, $r=false, $rvars=false)
{
	global $nc_core;

	$version= 'v11';
	//18.06.2016
	//Compress
	/*	&compress=true/false
		&file - компрессит в filename.compress.css один файл
		&files - компрессит в all.compress.css все указанные файлы
		&tofile - файл, в который комперссить все указанные файлы
		&print=false/true - выводить код, а не путь к файлу
		&r=false/true - принудительно пересоздает компресс-файлы
		&rvars=false/true - замена переменных
		[!Compress? &file=`css/styles.css`!]
		[!Compress? &files=`css: styles.css, catalog.css; css2: shop.css; css3/dop.css` &tofile=`css/all.compress.css`!]
	*/
	//============================================================================
	$strtr[ '.css' ]= array(
	);
	$strtr[ '.js' ]= array(
	);
	$pregreplace[ '.css' ][0]= array(
		"/\/\*(.*)\*\//sU" => "",
		"/[\s]{2,}/" => " ",
		"/[\s]*([\(\){\}\[\];:])[\s]*/" => '${1}',
		"/[\s]*([,>])[\s]*/" => '${1}',
		"/([^0-9])0px/" => '${1}0',
		"/;\}/" => '}',
	);
	$pregreplace[ '.css' ][1]= array(
	);
	$pregreplace[ '.js' ][0]= array(
		"/\/\/(.*)$/mU" => "",
		"/\/\*(.*)\*\//sU" => "",
	);
	$pregreplace[ '.js' ][1]= array(
		"/[\s]{2,}/" => " ",
		"/[\s]*([\(\){\}\[\];:])[\s]*/" => '${1}',
		"/[\s]*([<,:>])[\s]*/" => '${1}',
		"/[\s]*([=+!\/-])[\s]*/" => '${1}',
		"/[\s]*([?|\*])[\s]*/" => '${1}',
	);
	//============================================================================
	if( true )
	{
		$slash= ( substr( ( $file ? $file : $files ), 0, 1 ) == "/" ? false : true );
		$root= rtrim( $nc_core->DOCUMENT_ROOT, "/\\" ) . ( $slash ? '/' : '' );
		if( $file )
		{
			$filetype= substr( $file, strrpos( $file, '.' ) );
			$file_to= substr( $file, 0, strrpos( $file, '.' ) ) .'.compress'. $filetype;
			$filesarray[]= $file;
			if( ! file_exists( $root . $file_to ) || filectime( $root . $file ) > filectime( $root . $file_to ) ) $refresh= true;
		}else{
			$filetype= substr( $files, strrpos( $files, '.' ) );
			$file_to= ( $tofile ? $tofile : 'all.compress'.$filetype );
			$tmp1= explode( ';', $files );
			foreach( $tmp1 AS $row1 )
			{
				$tmp2= explode( ':', trim( $row1 ) );
				if( count( $tmp2 ) == 1 )
				{
					$filepath= trim( $row1 );
					$filesarray[]= $filepath;
					if( ! file_exists( $root . $file_to ) || filectime( $root . $filepath ) > filectime( $root . $file_to ) ) $refresh= true;
				}else{
					$tmp3= explode( ',', $tmp2[ 1 ] );
					foreach( $tmp3 AS $row3 )
					{
						$filepath= $tmp2[ 0 ] . trim( $row3 );
						$filesarray[]= $tmp2[ 0 ] . trim( $row3 );
						if( ! file_exists( $root . $file_to ) || filectime( $root . $filepath ) > filectime( $root . $file_to ) ) $refresh= true;
					}
				}
			}
		}
		if( isset( $strtr[ $filetype ] ) ) $strtr_type= $strtr[ $filetype ];
		if( isset( $pregreplace[ $filetype ][0] ) ) $pregreplace_type_0= $pregreplace[ $filetype ][0];
		if( isset( $pregreplace[ $filetype ][1] ) ) $pregreplace_type_1= $pregreplace[ $filetype ][1];
	}
	//============================================================================
	$refresh= ( $refresh || ! empty( $r ) ? true : false );
	if( $refresh && $filesarray )
	{
		$size_before= 0;
		$file_to_handle= fopen( $root . $file_to, 'w' );
		if( $files ) fwrite( $file_to_handle, "/*{$files}*/\n\n" );
		foreach( $filesarray AS $filerow )
		{
			$size_before += filesize( $root . $filerow );
		}
		foreach( $filesarray AS $filerow )
		{
			$filecontent= "";
			$file_handle= fopen( $root . $filerow, 'r' );
			if( $file_handle )
			{
				while( ! feof( $file_handle ) ) $filecontent .= fread( $file_handle, 1024*64 );
				fclose( $file_handle );
				if( $filecontent )
				{
					if( $compress !== 'false' )
					{
						if( $pregreplace_type_0 )
						{
							foreach( $pregreplace_type_0 AS $pattern => $replacement )
								$filecontent= preg_replace( $pattern, $replacement, $filecontent );
						}
						if( $filetype == '.css' ) if( $strtr_type ) $filecontent= strtr( $filecontent, $strtr_type );

						if( $filetype != '.css' )
						{
							$parts= array();
							$kovpos= $curpos= 0;
							$string_flag= false;
							while( true )
							{
								$kov1= ( $string_flag === '2' ? false : strpos( $filecontent, "\"", $curpos+1 ) );
								$kov2= ( $string_flag === '1' ? false : strpos( $filecontent, "'", $curpos+1 ) );
								if( $kov1 === false && $kov2 === false )
								{
									$parts[]= array( substr( $filecontent, $kovpos ).( $string_flag === '1' ? "\"" : ( $string_flag === '2' ? "'" : '' ) ), ( $string_flag ? $string_flag : false ) );
									break;
								}else{
									if( $kov1 === false ) $kov1= $kov2 + 1;
									if( $kov2 === false ) $kov2= $kov1 + 1;
									$curpos= ( $kov1 < $kov2 ? $kov1 : $kov2 );
									$ii= 1; $cc= 0;
									if( $string_flag )
									{
										while( substr( $filecontent, $curpos-$ii, 1 ) == "\\" )
										{
											$ii++; $cc++;
										}
									}
									$vse_eshe_text= ( $string_flag && $cc%2!=0 ? true : false );
									if( ! $string_flag || ( ! $parts[count($parts)-1][1] && ! $vse_eshe_text ) )
									{
										$parts[]= array( substr( $filecontent, $kovpos+( $string_flag ? 1 : 0 ), $curpos-($kovpos+( $string_flag ? 1 : -1 )) ),
													( $string_flag ? $string_flag : false ) );
										$string_flag= ( $string_flag ? false : ( $kov1 < $kov2 ? '1' : '2' ) );
										$kovpos= $curpos;
									}
								}
							}

							if( $rvars === 'true' )
							{
								preg_match_all( "/var [a-zA-Z0-9_]+?/U", $filecontent, $matches );
								if( $matches )
								{
									foreach( $matches[0] AS $row )
									{
										$var= str_replace( 'var ', '', $row );
										$vars[ $var ]= true;
									}
									foreach( $matches[0] AS $row )
									{
										$var= str_replace( 'var ', '', $row );
										do{ $varnum++; }while( $vars[ '_'.$varnum ] );
										$pregreplace_type_1[ "/([^a-zA-Z0-9_])(". $var .")([^a-zA-Z0-9_])/U" ]= '${1}_'.$varnum.'${3}';
									}
								}
							}

							$filecontent= '';
							if( $parts )
							{
								foreach( $parts AS $part )
								{
									if( ! $part[1] )
									{
										if( $pregreplace_type_1 )
										{
											foreach( $pregreplace_type_1 AS $pattern => $replacement )
												$part[0]= preg_replace( $pattern, $replacement, $part[0] );
										}
										if( $strtr_type ) $part[0]= strtr( $part[0], $strtr_type );
									}
									$filecontent .= $part[0];
								}
							}
						}
					}
					fwrite( $file_to_handle, "/*{$filerow}*/\n".$filecontent."\n\n" );
				}
			}
		}
		$size_after= filesize( $root . $file_to );
		//$md5_after= md5_file( $root . $file_to );
		fwrite( $file_to_handle, "/*Compress {$version} - ".round( $size_after * 100 / $size_before )."%".( $md5_after ? " - ".$md5_after : "" )."*/" );
		fclose( $file_to_handle );
	}
	//============================================================================
	if( $print === 'true' )
	{
		$filecontent= '';
		$file_to_handle= fopen( $root . $file_to, 'r' );
		while( ! feof( $file_to_handle ) ) $filecontent .= fread( $file_to_handle, 1024*64 );
		fclose( $file_to_handle );
		return $filecontent;
	}else return $file_to;
}







// function sdfsdfsdf54645645633333333333333()
// {
// global $nc_core;
// 			$handle= fopen( $_SERVER[ 'DOCUMENT_ROOT' ] .'/assets/cities.txt', 'r' );
// 			if( $handle )
// 			{
// 				$content= '';
// 				while( ! feof( $handle ) ) $content .= fread( $handle, 1024*1024 );
// 				fclose( $handle );
// 				if( $content )
// 				{
// 					$content= explode( "\n", $content );
// 					foreach( $content AS $row )
// 					{
// 						$row= explode( "\t", $row );
// 						if( $row[ 0 ] )
// 						{
// 							$nc_core->db->query("INSERT INTO BN_CIDR_Cities SET `code`='{$row[0]}', `name`='{$row[1]}', region='{$row[2]}', okrug='{$row[3]}', sh='{$row[4]}', dl='{$row[5]}'");
// 						}
// 					}
// 				}
// 			}
// }



// function sdfsdfsdf546456456()
// {
// global $nc_core;
// 			$handle= fopen( $_SERVER[ 'DOCUMENT_ROOT' ] .'/assets/cidr_optim_2.txt', 'r' );
// 			if( $handle )
// 			{
// 				$content= '';
// 				while( ! feof( $handle ) ) $content .= fread( $handle, 1024*1024 );
// 				fclose( $handle );
// 				if( $content )
// 				{
// 					$content= explode( "\n", $content );
// 					foreach( $content AS $row )
// 					{
// 						$row= explode( "\t", $row );
// 						if( $row[ 0 ] )
// 						{
// 							$nc_core->db->query("INSERT INTO BN_CIDR_Optim SET `from`='{$row[0]}', `to`='{$row[1]}', ips='{$row[2]}', country='{$row[3]}', code='{$row[4]}'");
// 						}
// 					}
// 				}
// 			}
// }


// function sdfsdfsdf()
// {
//
// 		$handle= fopen( $_SERVER[ 'DOCUMENT_ROOT' ] .'/assets/cidr_optim.txt', 'r' );
// 		if( $handle )
// 		{
// 			//while( ! feof( $handle ) ) $contents .= fread( $handle, 1024*1024*5 );
//
// 			while( $userinfo= fscanf( $handle, "%s\t%s\t%s\t%s\t%s\t%s\t%s\n" ) )
// 			{
// 				if( $userinfo[ 5 ] == 'RU' )
// 				{
// 					$result .= $userinfo[ 0 ] ."\t". $userinfo[ 1 ] ."\t". $userinfo[ 2 ] ." - ". $userinfo[ 4 ] ."\t". $userinfo[ 5 ] ."\t". $userinfo[ 6 ] ."\n";
// 				}
// 			}
// 			fclose( $handle );
// 			$handle= fopen( $_SERVER[ 'DOCUMENT_ROOT' ] .'/assets/cidr_optim_2.txt', 'w' );
// 			if( $handle )
// 			{
// 				fwrite( $handle, $result );
// 				fclose( $handle );
// 			}else{
// 				$print .= 'NO_2';
// 			}
//
// 		}else{
// 			$print .= 'NO_1';
// 		}
// }
