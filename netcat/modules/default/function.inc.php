<?php

function YaKa($info)
{
	$infos= array(
		'shopid' => '78611',
		'scid' => '543940',
		'shoppassword' => '3f_6mZqVRTMv9_uOvXji',
	);
	return $infos[$info];
}




function _AJAX()
{
	global $nc_core;

	$action= $_GET['a'];





	if($_POST['action']=='checkOrder' || $_POST['action']=='paymentAviso')
	{
		$postaction= $_POST['action'];

		$md5= strtoupper($_POST['md5']);
		$mymd5= strtoupper(md5("checkOrder;100.00;{$_POST[orderSumCurrencyPaycash]};{$_POST[orderSumBankPaycash]};".YaKa('shopid').";".YaKa('scid').";{$_POST[invoiceId]};{$_POST[customerNumber]};".YaKa('shoppassword')));

		header('Content-Type: application/xml');
		if($mymd5==$md5) $responsecode= '0'; else $responsecode= '1';
		$date= new DateTime();
		$date= $date->format("Y-m-d")."T".$date->format("H:i:s").".000".$date->format("P");
		print '<?xml version="1.0" encoding="UTF-8"?><'.$postaction.'Response performedDatetime="'.$date.'" code="'.$responsecode.'" invoiceId="'.$_POST['invoiceId'].'" shopId="'.YaKa('shopid').'"/>';

		$params= serialize($_POST);
		$params= $nc_core->db->escape($params);
		$orderSumAmount= $nc_core->db->escape($_POST['orderSumAmount']);
		$nc_core->db->query("INSERT INTO BN_YaKa_Logs SET action='{$postaction}', sum='{$orderSumAmount}', mysum='', md5='{$md5}', mymd5='{$mymd5}', dt=".time().", params='{$params}'");

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


	if($action=='shopAddToBasket')
	{
		$catid= intval($_GET['catid']);
		$edition= intval($_GET['edition']);
		$options= $_POST['option'];

		if($catid && $edition)
		{
			$ratio= getOptions($catid, $options);
			if($ratio===1) $ratio= getDefaultOptionRatio($catid);

			$row= $nc_core->db->get_results("SELECT cc.id AS ccid, ee.id AS eeid, ee.edition, cc.name, ee.price FROM BN_PG_Catalog AS cc
				INNER JOIN BN_PG_Catalog_Edition AS ee ON ee.idi=cc.id
					WHERE cc.parent={$catid} AND ee.id={$edition} AND ee.enabled='y' AND cc.enabled='y' LIMIT 1", ARRAY_A);
			if(is_array($row) && count($row))
			{
				$row= $row[0];
				$row['price'] *= $ratio;
				$row['price']= round($row['price']);

				$uniq= $row['name'] . $row['edition'] . $row['price'];

				$user= $nc_core->db->escape('s'.session_id());
				$row['name']= $nc_core->db->escape($row['name']);
				$row['edition']= $nc_core->db->escape($row['edition']);

				$getOptions= getOptions($catid, $options, 'info');
				if(is_array($getOptions) && count($getOptions))
				{
					foreach($getOptions AS $row2)
					{
						$options_info[]= array(
							'type'        => $row2['type'],
							'name'        => $row2['name'],
							'subname'     => $row2['subname'],
							'ratio'       => $row2['ratio']
						);
						$uniq .= $row2['type'] . $row2['name'] . $row2['subname'] . $row2['ratio'];
					}
				}
				$params['options']= $options_info;
				$params= serialize($params);
				$params= $nc_core->db->escape($params);

				$uniq= md5($uniq);
				$rr= $nc_core->db->get_results("SELECT id FROM BN_Shop_Basket WHERE user='{$user}' AND itemid={$row[ccid]} AND uniq='{$uniq}' LIMIT 1", ARRAY_A);
				if(is_array($rr) && count($rr))
				{
					$nc_core->db->query("UPDATE BN_Shop_Basket SET count=count+1 WHERE id={$rr[0][id]} LIMIT 1");
				}else{
					$nc_core->db->query("INSERT INTO BN_Shop_Basket SET user='{$user}', itemid={$row[ccid]}, title='{$row[name]}', count=1,
						ed='{$row[edition]}', ratio='{$ratio}', price='{$row[price]}', params='{$params}', dt=".time().", uniq='{$uniq}'");
				}
			}
		}
	}

	if($action=='catalogSetOption')
	{
		$catid= intval($_POST['catid']);
		$editions_large= ($_POST['editions_large']=='y'?true:false);
		$options= $_POST['option'];
		$ratio= getOptions($catid, $options);
		print _CATALOG($catid, true, $ratio, $editions_large);
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
	//orderNumber - string
	//cps_email - string
	//cps_phone - string - 79110000000
	$pp .= '<form method="POST" action="https://demomoney.yandex.ru/eshop.xml">
		<input name="shopId" value="78611" type="hidden">
		<input name="scid" value="543940" type="hidden">
		<input name="customerNumber" value="Покупатель N1"><!-- Идентификатор вашего покупателя -->
		<input name="sum" value="10.00"><!-- Сумма покупки (руб.) -->
		<input type="submit" value="Оплатить">
	</form>';

	return $pp;
}
//--------------------------- PAYMENT ------------------------------------------








//--------------------------- CATALOG ------------------------------------------

function _CATALOG($id, $onlytable=false, $ratio=1, $editions_large=false)
{
	global $nc_core;

	$id= intval($id);
	// $mainCategory= getMainCategory($id);
	// $parentList= getIdLvl($mainCategory[0], $id);

	if($id==189)
	{
		$subBigCategories= $nc_core->db->get_results("SELECT Subdivision_ID AS id, Subdivision_Name AS name FROM Subdivision WHERE Parent_Sub_ID={$id} AND Checked=1 ORDER BY Priority", ARRAY_A);
		if(is_array($subBigCategories) && count($subBigCategories))
		{
			foreach($subBigCategories AS $row)
			{
				$subinfo= $nc_core->subdivision->get_by_id($row['id']);

				$subCategories= $nc_core->db->get_results("SELECT Subdivision_Name AS name FROM Subdivision WHERE Parent_Sub_ID={$row[id]} AND Checked=1 ORDER BY Priority", ARRAY_A);

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

	if(!$onlytable) $subCategories= $nc_core->db->get_results("SELECT Subdivision_ID AS id, Subdivision_Name AS name FROM Subdivision WHERE Parent_Sub_ID={$id} AND Checked=1 ORDER BY Priority", ARRAY_A);
	if(empty($subCategories)) $subCategories[]= array('id'=>$id, 'name'=>false);
	if(is_array($subCategories) && count($subCategories))
	{
		foreach($subCategories AS $key=>$row)
		{
			if($ratio===1) $ratio= getDefaultOptionRatio($row['id']);

			$subinfo= $nc_core->subdivision->get_by_id($row['id']);

			$tablepp= '';
			$editions_first= false;
			$editions_large_flag= false;
			$items= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog WHERE parent={$row[id]} AND enabled='y' ORDER BY ii", ARRAY_A);
			if(is_array($items) && count($items))
			{
				foreach($items AS $item)
				{
					$editions= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Edition WHERE idi={$item[id]} AND enabled='y' ORDER BY ii", ARRAY_A);
					if( ! $editions_first)
					{
						$editions_first= $editions;
						if(count($editions)>10) $editions_large_flag= true;
					}
					$item['name']= str_replace('м2','м<span class="vkvadrate">2</span>',$item['name']);
					$tablepp .= '<tr><td class="ctt_name">'.($item['description']?'<span class="tiptop" title="'.$item['description'].'">'.$item['name'].'</span>':$item['name']).'</td>';
					foreach($editions AS $keyedition=>$edition)
					{
						if($editions_large_flag)
						{
							if( ! $editions_large && $keyedition>=7) break;
							elseif($editions_large && $keyedition<7) continue;
						}
						if($ratio<1 || $ratio>1) $edition['price'] *= $ratio;
						$tablepp .= '<td class="ctt_itm" data-edition="'.$edition['id'].'"><span class="price">'.Price($edition['price']).'</span> <span class="ruble">руб</span><span class="check">'.icon('check').'</span></td>';
					}
					$tablepp .= '</tr>';
				}
			}

			if(!$onlytable)
			{
				$options= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Option WHERE parent={$row[id]} AND enabled='y' ORDER BY ido, ii", ARRAY_A);

				$pp .= '<div class="ct_border ct_border_'.$row['id'].'" data-id="'.$row['id'].'">
					<div class="ct_left" style="'.(empty($options)?'float:none;width:auto;':'').'">
						<div class="ct_hd">';

				if($row['name']) $pp .= '<div class="ct_tit ct_tit_'.($key+1).' tiptop" title="'.$subinfo['categoryDescription'].'">'.$row['name'].'</div>';
				if($subinfo['AlterTitle'])
				{
					if( ! $row['name']) $pp .= '<div class="ct_tit ct_tit_'.($key+1).' tiptop" title="'.$subinfo['categoryDescription'].'">'.$subinfo['AlterTitle'].'</div>';
					else $pp .= '<div class="ct_subtit">'.$subinfo['AlterTitle'].'</div>';
				}

				if($subinfo['productionTime']) $pp .= '<div class="ct_txt">Срок изготовления '.icon('update').' <span class="tiptop" title="После оплаты и утверждения макета">'.$subinfo['productionTime'].' *</span></div>';
				$pp .= '<br />
						</div>

						<div class="ct_c">';
			}

			$pp .= '<div class="ct_loading">&nbsp;</div>
						<table class="ct_t">
							<tr class="ctt_tit">
								<td><span>'.$subinfo['nameCatalogTableCol'].'</span></td>';
			foreach($editions_first AS $keyedition=>$edition)
			{
				if($editions_large_flag)
				{
					if( ! $editions_large && $keyedition>=7) break;
					elseif($editions_large && $keyedition<7) continue;
				}
				$edition['edition']= str_replace('шт.','<span>шт.</span>',$edition['edition']);
				$edition['edition']= str_replace('м2','<span>м<span class="vkvadrate">2</span></span>',$edition['edition']);
				$pp .= '<td>'.$edition['edition'].'</td>';
			}
			$pp .= '</tr>';
			$pp .= $tablepp;
			$pp .= '</table>';

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

						$pp .= '<div class="ctc_opt">';
						if($option['type']=='select') $pp .= '<select class="ido'.$option['ido'].'" name="option['.$option['ido'].']" data-ido="'.$option['ido'].'">';
						if($option['type']=='radio') $pp .= '<input type="hidden" name="option['.$option['ido'].']" value="'.$option['id'].'" />';

						$ido= $option['ido'];
						$option_type= $option['type'];
					}

					if($option['type']=='select')
					{
						$pp .= '<option '.($row['id']==217 && !$firstoptionval ?'class="notfirst"':'').' '.($option['default']=='y'?'selected="selected"':'').' value="'.$option['id'].'">'.$option['name'].'</option>';

					}elseif($option['type']=='checkbox'){
						$pp .= '<label><input type="checkbox" '.($option['default']=='y'?'checked="checked"':'').' name="option['.$option['ido'].']" value="'.$option['id'].'" /> '.$option['name'].'</label>';

					}elseif($option['type']=='radio'){
						if($option['subname']!=$subname)
						{
							if($subname)
							{
								$pp .= '</div>';
							}
							$pp .= '<div class="ctco_point '.(!$subname?'ctco_point_a':'').'">
								<div class="ctco_p_butt">'.icon('radio_checked','radio_checked').''.icon('radio_unchecked','radio_unchecked').' '.$option['subname'].'</div>';
							$subname= $option['subname'];
						}
						$option['name']= str_replace('м2','м<span class="vkvadrate">2</span>',$option['name']);
						$pp .= '<div class="ctco_p_itm" data-val="'.$option['id'].'" data-default="'.$option['default'].'">'.$option['name'].'</div>';
					}
				}
				if($option_type=='select') $pp .= '</select>';
				if($option_type=='radio') $pp .= '</div>';
				$pp .= '</div>';

				$subinfo['catalogOptionTxt']= str_replace('м2','м<span class="vkvadrate">2</span>',$subinfo['catalogOptionTxt']);
				$pp .= '<div class="ctc_opttxt">'.$subinfo['catalogOptionTxt'].'</div>';

				$pp .= '<input type="hidden" name="catid" value="'.$row['id'].'" />';
				$pp .= '</form>
					</div><!-- /.ct_c -->
				</div><!-- /.ct_right -->';
			}

			$pp .= '<br /></div><!-- /.ct_border -->';


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

			$pp .= '<div style="height:40px;"></div>';
		}
	}

	$pp .= '</div><!-- /.catalog -->
	</div><!-- /.box_catalog -->';

	return $pp;
}
//--------------------------- CATALOG ------------------------------------------

function shopBasketItemExists($itemid)
{

}

function shopBasketItemsList()
{
	global $nc_core;

	$rr= $nc_core->db->get_results("SELECT * FROM BN_Shop_Basket WHERE user='".$nc_core->db->escape('s'.session_id())."' ORDER BY dt", ARRAY_A);
	if(is_array($rr) && count($rr)) return $rr;
	return false;
}

function shopHeaderBasket()
{
	$items= shopBasketItemsList();
	$sum= 0;
	if($items) foreach($items AS $row) $sum += round($row['count'] * $row['price']);
	return ($sum ? '<span>'.Price($sum).'</span> <span class="ruble">руб</span>' : '<span>Корзина</span>');
}

function getDefaultOptionRatio($id)
{
	global $nc_core;

	$ratio= 1;
	$rr= $nc_core->db->get_results("SELECT ratio FROM BN_PG_Catalog_Option WHERE parent={$id} AND `default`='y' AND enabled='y' ORDER BY ido", ARRAY_A);
	if(is_array($rr) && count($rr))
	{
		foreach($rr AS $row)
		{
			$ratio *= $row['ratio'];
		}
	}
	return $ratio;
}

function getOptions($id, $options, $result='ratio')
{
	global $nc_core;

	$ratio= 1.0;
	if($id)
	{
		if(is_array($options) && count($options))
		{
			$qq= "";
			foreach($options AS $key=>$row)
			{
				$key= intval($key);
				$row= intval($row);
				if($key>=0 && $row)
				{
					$qq .= (!empty($qq)?" OR ":"")."(ido={$key} AND id={$row})";
				}
			}
			if($qq)
			{
				$options_rr= $nc_core->db->get_results("SELECT * FROM BN_PG_Catalog_Option WHERE parent={$id} AND ({$qq}) AND enabled='y' ORDER BY ido", ARRAY_A);
				if(is_array($options_rr))
				{
					if(count($options_rr)==count($options))
					{
						if($result=='info') return $options_rr;

						foreach($options_rr AS $row)
						{
							$ratio *= floatval($row['ratio']);
						}
					}
				}
			}
		}
	}
	return $ratio;
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











//---------------------------- CITY --------------------------------------------
if(isset($_GET['clearcity'])){ print_r($_SESSION['mycity']); unset($_SESSION['mycity']); print '--'; print_r($_SESSION['mycity']); exit(); }


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
			var myMap2;
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
			$_SESSION['mycity']['city']= $row['City'];
			$_SESSION['mycity']['city_code']= $row['CityCode'];
			$_SESSION['mycity']['city_alias']= $row['CityAlias'];
			$_SESSION['mycity']['obl']= $row['Obl'];
			$_SESSION['mycity']['fo']= $row['FO'];

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


//---------------------------- CITY --------------------------------------------


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
        "Щ"=>"shh", "Ы"=>"y", "Э"=>"eh", "Ю"=>"yu", "Я"=>"ya", " "=>"-", "."=>"-",
        ","=>"-", "_"=>"-", "+"=>"-", ":"=>"-", ";"=>"-", "!"=>"-", "?"=>"-");
	$alias= strip_tags(strtr($alias, $trans));
	$alias= preg_replace("/[^a-z0-9-]/", "", $alias);
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

function Price($price)
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
	if(strlen($tmp[1])<$round) $tmp[1]= str_pad($tmp[1], $round, '0', STR_PAD_LEFT);
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

function ImgCrop72($img, $w=0, $h=0, $backgr=false, $fill=false, $bgcolor='', $wm=false, $fullpath=false, $r=false)
{
	global $nc_core;
	// v7.2
	// 15.09.2016
	// ImgCrop
	/*
		$img= assets/images/img.jpg
		$dopimg= assets/images/dopimg.jpg
		$toimg= assets/images/toimg.jpg

		$w= (int)156
		$h= (int)122
		$backgr= 0/1
		$fill= 0/1
		$x= center/left/right
		$y= center/top/bottom
		$bgcolor= R,G,B,A / x:y / fill:a;b;c;d|b;c;d
		$wm= 0/1
		$filter= a;b;c;d|b;c;d
		$png= 0/1
		$ellipse= max / (int)56
		$degstep= (int)5
		$dopimg_xy= x:y
		$quality= (int)80
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
	$quality= 100;
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
		$toimg= ltrim($toimg, $base);
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
	if( ! file_exists($newimg_path) || filemtime($root.$img) > filemtime($newimg_path)) $refresh= true;
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
			if( ! file_exists( $root . $file_to ) || filemtime( $root . $file ) > filemtime( $root . $file_to ) ) $refresh= true;
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
					if( ! file_exists( $root . $file_to ) || filemtime( $root . $filepath ) > filemtime( $root . $file_to ) ) $refresh= true;
				}else{
					$tmp3= explode( ',', $tmp2[ 1 ] );
					foreach( $tmp3 AS $row3 )
					{
						$filepath= $tmp2[ 0 ] . trim( $row3 );
						$filesarray[]= $tmp2[ 0 ] . trim( $row3 );
						if( ! file_exists( $root . $file_to ) || filemtime( $root . $filepath ) > filemtime( $root . $file_to ) ) $refresh= true;
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
