<?php



function SEIE__CDEKCITY__EXCEL($file)
{
	global $nc_core, $SEIE;

	$nc_core->db->query("UPDATE BN_CDEK_Federal SET enabled='n'");
	$nc_core->db->query("UPDATE BN_CDEK_City SET enabled='n'");

	@include_once('PHPExcel/PHPExcel/IOFactory.php');
	$inputFileType= PHPExcel_IOFactory::identify($file);
	$objReader= PHPExcel_IOFactory::createReader($inputFileType);
	$objPHPExcel= $objReader->load($file);
	$data= $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

	foreach($data AS $row=>$cols)
	{
		if($row===1) continue;
		if($cols['D']=='null') continue;

		$cols['A']= intval($cols['A']);
		$cols['B']= $nc_core->db->escape($cols['B']);
		$cols['C']= $nc_core->db->escape($cols['C']);
		$cols['D']= $nc_core->db->escape($cols['D']);
		$cols['G']= $nc_core->db->escape($cols['G']);
		$cols['H']= $nc_core->db->escape($cols['H']);
		$cols['E']= ($cols['E']=='*' ? 'y' : 'n');
		if($cols['F']=='no limit') $cols['F']= 99999.99; else $cols['F']= floatval($cols['F']);

		if( ! $obls[$cols['D']])
		{
			$obls[$cols['D']]= true;
			$rr= $nc_core->db->get_results("SELECT id FROM BN_CDEK_Federal WHERE obl='{$cols[D]}' LIMIT 1", ARRAY_A);
			if( ! $rr) $nc_core->db->query("INSERT INTO BN_CDEK_Federal SET obl='{$cols[D]}', fd='~ без округа ~', enabled='y'");
		}

		$rr= $nc_core->db->get_results("SELECT id FROM BN_CDEK_City WHERE uid={$cols[A]} LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr))
		{
			$nc_core->db->query("UPDATE BN_CDEK_City SET full_name='{$cols[B]}', city_name='{$cols[C]}', obl_name='{$cols[D]}', center='{$cols[E]}', nal_limit='{$cols[F]}', eng_name='{$cols[G]}', post_code='{$cols[H]}', enabled='y' WHERE uid={$cols[A]} LIMIT 1");
		}else{
			$nc_core->db->query("INSERT INTO BN_CDEK_City SET uid={$cols[A]}, full_name='{$cols[B]}', city_name='{$cols[C]}', obl_name='{$cols[D]}', center='{$cols[E]}', nal_limit='{$cols[F]}', eng_name='{$cols[G]}', post_code='{$cols[H]}', enabled='y'");
		}
	}

	return true;
}













function SEIE__IMPORT($file)
{
	global $SEIE;

	@include_once('PHPExcel/PHPExcel/IOFactory.php');
	$inputFileType= PHPExcel_IOFactory::identify($file);
	$objReader= PHPExcel_IOFactory::createReader($inputFileType);
	$objPHPExcel= $objReader->load($file);

	//getSheetCount
	//sheetExists
	//removeSheetByIndex
	//setActiveSheetIndex
	$sheets= $objPHPExcel->getSheetCount();
	if($sheets)
	{
		$ii= 0;
		for($sheetii=0; $sheetii<$sheets; $sheetii++)
		{
			if( ! $objPHPExcel->setActiveSheetIndex($sheetii)) continue;
			$data= $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
			$categoryName= $objPHPExcel->getActiveSheet()->getTitle();
			if($data)
			{
				$datatype= false;
				$rownull= 0;
				foreach($data AS $row => $colarray)
				{
					if( ! $colarray['A'] && ! $colarray['B']) $rownull++;
					if($rownull>=15) break;

					if($colarray['A']=='Таблица')
					{
						$rownull= 0;
						if($options!==false) saveOptions($subCategoryID, $options, $optionTit, $optionsText);

						$subCategory= $productionTime= $edition= $items= $options= $optionsText= false;
						$datatype= 'table';
						$subBigCategory= $colarray['B'];
						continue;
					}

					if($colarray['A']=='Опции')
					{
						$rownull= 0;
						$options= $optionType= false;
						$datatype= 'option';
						$optionTit= $colarray['B'];
						continue;
					}

					if($datatype=='option')
					{
						if($colarray['A'])
						{
							if($colarray['A']=='Список' || $colarray['A']=='Галочка' || $colarray['A']=='Точка' || $colarray['A']=='Размер' || $colarray['A']=='Количество')
							{
								if($colarray['A']=='Список') $optionType= 'select';
								if($colarray['A']=='Галочка') $optionType= 'checkbox';
								if($colarray['A']=='Точка') $optionType= 'radio';
								if($colarray['A']=='Размер') $optionType= 'size';
								if($colarray['A']=='Количество') $optionType= 'count';
								$options[]= array($optionType, array());
							}else{
								$optionType= false;
								$optionsText[]= $colarray['A'];
							}
						}
						if($optionType && $colarray['B'])
							$options[count($options)-1][1][]= array($colarray['B'], $colarray['C'], $colarray['D'], $colarray['E'], $colarray['G']);
					}

					if($datatype=='table')
					{
						if($subCategory===false)
						{
							$subCategory= $colarray['A'];
							$productionTime= $colarray['B'];

						}else{
							if($edition===false)
							{
								$tableTit= false;
								foreach($colarray AS $col => $cell)
								{
									if($cell)
									{
										if( ! $tableTit ) $tableTit= $cell;
										else $edition[]= $cell;
									}elseif($tableTit) break;
								}
							}else{
								if($colarray['B'])
								{
									$items[]= array($colarray['B'], $colarray['A'], array());
									$tmp= false;
									foreach($colarray AS $col => $cell)
									{
										if($cell)
										{
											if($col=='C') $tmp= true;
											if($tmp) $items[count($items)-1][2][]= $cell;
										}elseif($tmp) break;
									}
								}else{
									$datatype= false;
									$subCategoryID= saveTable($IDS, $categoryName, $subBigCategory, $subCategory, $productionTime, $tableTit, $edition, $items);
								}
							}
						}
					}
				}
				if($options!==false) saveOptions($subCategoryID, $options, $optionTit, $optionsText);
			}
		}
	}



	return true;
}


function saveTable(&$IDS, $categoryName, $subBigCategory, $subCategory, $productionTime, $tableTit, $edition, $items)
{
	global $nc_core, $SEIE;

	$categoryName= $nc_core->db->escape($categoryName);
	$subBigCategory= $nc_core->db->escape($subBigCategory);
	$subCategory= $nc_core->db->escape($subCategory);
	$productionTime= $nc_core->db->escape($productionTime);
	$tableTit= $nc_core->db->escape($tableTit);

	$subCategoryID= $SEIE['POLYGRAPHY_CATALOG_ID'];
	$lvl= 1;

	if( ! $IDS[$subCategoryID][$categoryName])
	{
		$rr= $nc_core->db->get_results("SELECT Subdivision_ID AS id FROM Subdivision WHERE Subdivision_Name='{$categoryName}' AND Parent_Sub_ID={$subCategoryID} LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr)==1)
		{
			$IDS[$subCategoryID][$categoryName]= $rr[0]['id'];
		}
	}
	if($IDS[$subCategoryID][$categoryName]){ $subCategoryID= $IDS[$subCategoryID][$categoryName]; $lvl= 2; }

	if($subBigCategory)
	{
		if( ! $IDS[$subCategoryID][$subBigCategory])
		{
			$rr= $nc_core->db->get_results("SELECT Subdivision_ID AS id FROM Subdivision WHERE Subdivision_Name='{$subBigCategory}' AND Parent_Sub_ID={$subCategoryID} LIMIT 1", ARRAY_A);
			if(is_array($rr) && count($rr)==1)
			{
				$IDS[$subCategoryID][$subBigCategory]= $rr[0]['id'];
			}
		}
		if($IDS[$subCategoryID][$subBigCategory]){ $subCategoryID= $IDS[$subCategoryID][$subBigCategory]; $lvl= 3; }
	}

	if($subCategory)
	{
		if( ! $IDS[$subCategoryID][$subCategory])
		{
			$rr= $nc_core->db->get_results("SELECT Subdivision_ID AS id FROM Subdivision WHERE Subdivision_Name='{$subCategory}' AND Parent_Sub_ID={$subCategoryID} LIMIT 1", ARRAY_A);
			if(is_array($rr) && count($rr)==1)
			{
				$IDS[$subCategoryID][$subCategory]= $rr[0]['id'];
			}
		}
		if($IDS[$subCategoryID][$subCategory]){ $subCategoryID= $IDS[$subCategoryID][$subCategory]; $lvl= 4; }
	}

	if( ! $subCategoryID) return false;

	$nc_core->db->query("UPDATE Subdivision SET productionTime='".$nc_core->db->escape($productionTime)."',
		nameCatalogTableCol='".$nc_core->db->escape($tableTit)."' ".($lvl==2?",AlterTitle='".$nc_core->db->escape($subCategory)."'":"")."
			WHERE Subdivision_ID={$subCategoryID} LIMIT 1");

	$nc_core->db->query("UPDATE BN_PG_Catalog SET enabled='n' WHERE parent={$subCategoryID}");

	foreach($items AS $key => $row)
	{
		$itemID= false;
		$rr= $nc_core->db->get_results("SELECT id FROM BN_PG_Catalog WHERE parent={$subCategoryID} AND `name`='".$nc_core->db->escape($row[0])."' LIMIT 1", ARRAY_A);
		if(is_array($rr) && count($rr)==1)
		{
			$itemID= $rr[0]['id'];
			$nc_core->db->query("UPDATE BN_PG_Catalog SET description='".$nc_core->db->escape($row[1])."', ii={$key}, enabled='y' WHERE id={$itemID} LIMIT 1");
		}else{
			$nc_core->db->query("INSERT INTO BN_PG_Catalog SET parent={$subCategoryID}, `name`='".$nc_core->db->escape($row[0])."', description='".$nc_core->db->escape($row[1])."', ii={$key}, enabled='y'");
			$itemID= $nc_core->db->insert_id;
		}
		if( ! $itemID) continue;
		$nc_core->db->query("UPDATE BN_PG_Catalog_Edition SET enabled='n' WHERE idi={$itemID}");
		foreach($row[2] AS $key2 => $row2)
		{
			if( ! $edition[$key2] || ! $row2) continue;

			$row2= $nc_core->db->escape(trim($row2));
			$row2= str_replace(',','.',$row2);

			$range= 'n';
			if(strpos($edition[$key2], '-')!==false) $range= 'y';
			if(preg_match("/^[^0-9]/", $edition[$key2])) $range= 'y';

			$rr= $nc_core->db->get_results("SELECT id FROM BN_PG_Catalog_Edition WHERE idi={$itemID} AND edition='".$nc_core->db->escape($edition[$key2])."' LIMIT 1", ARRAY_A);
			if(is_array($rr) && count($rr)==1)
			{
				$editionID= $rr[0]['id'];
				$nc_core->db->query("UPDATE BN_PG_Catalog_Edition SET price='{$row2}',
					`range`='{$range}', ii={$key2}, enabled='y' WHERE id={$editionID} LIMIT 1");
			}else{
				$nc_core->db->query("INSERT INTO BN_PG_Catalog_Edition SET idi={$itemID}, edition='".$nc_core->db->escape($edition[$key2])."',
					price='{$row2}', `range`='{$range}', ii={$key2}, enabled='y'");
			}
		}
	}

	return $subCategoryID;
}


function saveOptions($subCategoryID, $options, $optionTit, $optionsText)
{
	global $nc_core, $SEIE;

	if($optionTit) $nc_core->db->query("UPDATE Subdivision SET optionTitle='".$nc_core->db->escape($optionTit)."' WHERE Subdivision_ID={$subCategoryID} LIMIT 1");

	if(is_array($options) && count($options))
	{
		if($optionsText!==false)
		{
			$qq= "";
			foreach($optionsText AS $row) $qq .= $row."\n\n";
			$nc_core->db->query("UPDATE Subdivision SET catalogOptionTxt='".$nc_core->db->escape($qq)."' WHERE Subdivision_ID={$subCategoryID} LIMIT 1");
		}else $nc_core->db->query("UPDATE Subdivision SET catalogOptionTxt='' WHERE Subdivision_ID={$subCategoryID} LIMIT 1");

		$nc_core->db->query("UPDATE BN_PG_Catalog_Option SET enabled='n' WHERE parent={$subCategoryID}");
		foreach($options AS $key => $row)
		{
			if(is_array($row[1]) && count($row[1]))
			{
				foreach($row[1] AS $key2 => $row2)
				{
					if($row[0]=='radio')
					{
						$subname= $nc_core->db->escape($row2[0]);
						$name= $nc_core->db->escape($row2[1]);
						$markup= $nc_core->db->escape(trim($row2[2]));
						$markup= str_replace(',','.',$markup);
					}else{
						$subname= '';
						$name= $nc_core->db->escape($row2[0]);
						$markup= $nc_core->db->escape(trim($row2[1]));
						$markup= str_replace(',','.',$markup);
					}
					$default= ($row2[3]=='default'?'y':'n');
					$description= $nc_core->db->escape($row2[4]);
					$rr= $nc_core->db->get_results("SELECT id FROM BN_PG_Catalog_Option WHERE parent={$subCategoryID} AND ido='".($key+1)."' AND `name`='{$name}' AND subname='{$subname}' LIMIT 1", ARRAY_A);
					if(is_array($rr) && count($rr)==1)
					{
						$optionValID= $rr[0]['id'];
						$nc_core->db->query("UPDATE BN_PG_Catalog_Option SET type='{$row[0]}', markup='{$markup}', `default`='{$default}', ii={$key2}, description='{$description}', enabled='y' WHERE id={$optionValID} LIMIT 1");
					}else{
						$nc_core->db->query("INSERT INTO BN_PG_Catalog_Option SET parent={$subCategoryID}, ido='".($key+1)."', type='{$row[0]}', `name`='{$name}', subname='{$subname}', markup='{$markup}', `default`='{$default}', ii={$key2}, description='{$description}', enabled='y'");
					}
				}
			}
		}
	}
}
