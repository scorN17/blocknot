<?php
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."modules/ui.php");

if( ! $page) $page= 'orders';
require_once($MODULE_FOLDER.'scorn_shop/ui_config.php');
$UI_CONFIG= new ui_config_module_scornshop('admin', $page);
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);
if( ! isset($MODULE_VARS)) $MODULE_VARS= $nc_core->modules->get_module_vars();

$SEIE= $MODULE_VARS['scorn_shop'];
$seie_module_path= 'modules/scorn_shop/';
$seie_module_full= $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_ROOT_PATH . $seie_module_path;



$MainMain_arr= array('Москва', 'Санкт-Петербург', 'Ростов-на-Дону');






if($page=='cdekcity')
{
	if($_POST['act']=='cdekcityfromexcel')
	{
		$xls= $_FILES['excelfile'];
		if($xls['tmp_name']!='')
		{
			if(is_uploaded_file($xls['tmp_name']))
			{
				if($xls['size'] < 1024*1024*50) //Мб
				{
					$folder= $seie_module_full.'files/cdekcity/'.date('Y-m').'/';
					if( ! file_exists($folder)) mkdir($folder, 0777, true);
					$rassh= substr($xls['name'],strrpos($xls['name'],'.'));
					$file= $folder.date('Y-m-d__H-i-s').$rassh;
					if(move_uploaded_file($xls['tmp_name'], $file))
					{
						$SEIE__IMPORT__RESULT= SEIE__CDEKCITY__EXCEL($file);
						header('location: admin.php?page=cdekcity&'.($SEIE__IMPORT__RESULT?'ok':'err'));
						exit();
					}
				}
			}
		}
	}

	if($_POST['act']=='cdekcitypoints')
	{
		$nc_core->db->query("UPDATE BN_CDEK_City_Points SET enabled='n'");

		$xmlpvzlisturl= trim($_POST['xmlpvzlisturl']);

		$curl= curl_init();
		curl_setopt($curl, CURLOPT_URL, $xmlpvzlisturl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		$xmlpvzlist= curl_exec($curl);
		curl_close($curl);

		$xmlpvzlist= simplexml_load_string($xmlpvzlist);

		foreach($xmlpvzlist->Pvz AS $row)
		{
			$CityCode=         $nc_core->db->escape($row['CityCode']);

			$rr= $nc_core->db->get_results("SELECT obl_name, center FROM BN_CDEK_City WHERE uid='{$CityCode}' LIMIT 1", ARRAY_A);
			if( ! $rr) continue;
			$Obl= $nc_core->db->escape($rr[0]['obl_name']);
			$MainCity= ($rr[0]['center']=='y'?'y':'n');

			if( ! $FO[$Obl])
			{
				$rr= $nc_core->db->get_results("SELECT fd FROM BN_CDEK_Federal WHERE obl='{$Obl}' LIMIT 1", ARRAY_A);
				if( ! $rr) continue;
				$FO[$Obl]= $nc_core->db->escape($rr[0]['fd']);
			}

			$Code=             $nc_core->db->escape($row['Code']);
			$Name=             $nc_core->db->escape($row['Name']);
			$WorkTime=         $nc_core->db->escape($row['WorkTime']);
			$Address=          $nc_core->db->escape($row['Address']);
			$Phone=            $nc_core->db->escape($row['Phone']);
			$Note=             $nc_core->db->escape($row['Note']);
			$coordX=           $nc_core->db->escape($row['coordX']);
			$coordY=           $nc_core->db->escape($row['coordY']);
			$Type=             $nc_core->db->escape($row['Type']);
			$ownerCode=        $nc_core->db->escape($row['ownerCode']);
			$WeightMin=        $nc_core->db->escape($row->WeightLimit['WeightMin']);
			$WeightMax=        $nc_core->db->escape($row->WeightLimit['WeightMax']);

			$City=             $nc_core->db->escape($row['City']);
			if(strpos($City,',')!==false)
			{
				$City= explode(',',$City);
				$City= $City[0];
			}

			$MainMain= (in_array($City, $MainMain_arr) ? 'y' : 'n');

			$CityAlias= generAlias($City);

			$rr= $nc_core->db->get_results("SELECT id FROM BN_CDEK_City_Points WHERE Code='{$Code}' LIMIT 1", ARRAY_A);
			if(is_array($rr) && count($rr))
			{
				$nc_core->db->query("UPDATE BN_CDEK_City_Points SET Name='{$Name}', FO='{$FO[$Obl]}', Obl='{$Obl}', CityCode='{$CityCode}',
					City='{$City}', CityAlias='{$CityAlias}', MainCity='{$MainCity}', MainMain='{$MainMain}', WorkTime='{$WorkTime}', Address='{$Address}',
					Phone='{$Phone}', Note='{$Note}', coordX='{$coordX}', coordY='{$coordY}', WeightMin='{$WeightMin}', WeightMax='{$WeightMax}',
					Type='{$Type}', ownerCode='{$ownerCode}', enabled='y' WHERE Code='{$Code}' LIMIT 1");
			}else{
				$nc_core->db->query("INSERT INTO BN_CDEK_City_Points SET Code='{$Code}', Name='{$Name}', FO='{$FO[$Obl]}', Obl='{$Obl}', CityCode='{$CityCode}',
					City='{$City}', CityAlias='{$CityAlias}', MainCity='{$MainCity}', MainMain='{$MainMain}', WorkTime='{$WorkTime}', Address='{$Address}',
					Phone='{$Phone}', Note='{$Note}', coordX='{$coordX}', coordY='{$coordY}', WeightMin='{$WeightMin}', WeightMax='{$WeightMax}',
					Type='{$Type}', ownerCode='{$ownerCode}', enabled='y'");
			}
		}
		header('location: admin.php?page=cdekcity&ok');
		exit();
	}
}


if($page=='catalogimport')
{
	if($_POST['act']=='catalogimport')
	{
		$xls= $_FILES['seie__file'];
		if($xls['tmp_name']!='')
		{
			if(is_uploaded_file($xls['tmp_name']))
			{
				if($xls['size'] < 1024*1024*50) //Мб
				{
					$folder= $seie_module_full.'files/import/'.date('Y-m').'/';
					if( ! file_exists($folder)) mkdir($folder, 0777, true);
					$rassh= substr($xls['name'],strrpos($xls['name'],'.'));
					$file= $folder.date('Y-m-d__H-i-s').$rassh;
					if(move_uploaded_file($xls['tmp_name'], $file))
					{
						$SEIE__IMPORT__RESULT= SEIE__IMPORT($file);
						header('location: admin.php?page=catalogimport&'.($SEIE__IMPORT__RESULT?'ok':'err'));
						exit();
					}
				}
			}
		}
	}
}


$Title1= NETCAT_MODULES;
$Title2= NETCAT_MODULE_SCORNSHOP;
BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/scorn_shop/orders/");



if($page=='catalogimport')
{
	if(isset($_GET['ok'])) print '<div style="color:#46c300;font-size:20px;padding:50px 0px;">Успешно!</div>';
	if(isset($_GET['err'])) print '<div style="color:#cc0000;font-size:20px;padding:50px 0px;">Ошибка!</div>';
?>
<div>
	<h2>Выгрузка каталога из Ексель-файла</h2>
	<form action="admin.php?page=catalogimport" method="post" enctype="multipart/form-data">
		<div><label>Ексель-файл: <input type="file" name="seie__file" /></label></div>
		<input type="hidden" name="act" value="catalogimport" />
		<div style="padding-top:10px;"><label><button type="submit">Выгрузить</button></label></div>
	</form>
</div>
<?php
}






if($page=='cdekcity')
{
	if(isset($_GET['ok'])) print '<div style="color:#46c300;font-size:20px;padding:50px 0px;">Успешно!</div>';
	if(isset($_GET['err'])) print '<div style="color:#cc0000;font-size:20px;padding:50px 0px;">Ошибка!</div>';
?>
<div>
	<h2>CDEK: Выгрузка городов из Ексель</h2>
	<form action="admin.php?page=cdekcity" method="post" enctype="multipart/form-data">
		<div><label>Ексель-файл: <input type="file" name="excelfile" /></label></div>
		<input type="hidden" name="act" value="cdekcityfromexcel" />
		<div style="padding-top:10px;"><label><button type="submit">Обновить</button></label></div>
	</form>
</div>



<div style="margin:30px 0 0;padding:30px 0 0;border-top:1px solid #ddd;">
	<h2>CDEK: Обновление списка ПВЗ</h2>
	<form action="admin.php?page=cdekcity" method="post" enctype="multipart/form-data">
		<div><label>XML: <input style="width:300px;" type="text" name="xmlpvzlisturl" value="http://int.cdek.ru/pvzlist.php" /></label></div>
		<input type="hidden" name="act" value="cdekcitypoints" />
		<div style="padding-top:10px;"><label><button type="submit">Обновить</button></label></div>
	</form>
</div>
<?php
}




EndHtml();
