<?php
	$saledate = "11/4/2016";
	$filename = 'propArray'.urlencode($saledate).'.txt';



	function getTagValue($line){
		$line = trim($line);
		$search = '<span id="c_printsearchresults_gvResults_lbl';
		$p = strpos($line, $search);
		if($p === false){
			return $p;
		}

		$tag = substr(substr($line, strlen($search)), 0, strpos(substr($line, strlen($search)), '"'));
		$value = substr(substr($line, strlen($search)), strpos(substr($line, strlen($search)), '">')+2);
		$value = substr($value, 0, strpos($value, "</span>"));
		return array(explode("_", $tag)[0], $value);
	}


	if(is_file($filename)){
		$propArray = unserialize(file_get_contents($filename));




		var_dump($propArray);exit;
	}

	$url = "https://sheriff.franklincountyohio.gov/search/real-estate/printresults.aspx?q=searchType%3dSaleDate%26searchString%3d".htmlspecialchars($saledate)."%26foreclosureType%3d%26sortType%3ddefendant%26saleDateFrom%3d%26saleDateTo%3d";
	$f = file($url) or die("can't get the file");

	$caseNum = "";

	$propArray = array();
	foreach( $f as $line){

		$prop = getTagValue($line);

		if($prop!==false){
			if($prop[0]=="CaseNum"){
				$caseNum = $prop[1];
			}
			$propArray[$caseNum][$prop[0]]=$prop[1];
		}
	}

	foreach($propArray as $key=>$v){
		$address = $v['AddrNbr'].' '.$v['PropHalfInd'].' '.$v['AddrStrDir'].' '.$v['AddrStrName'].', '.$v['AddrCity'].', '.$v['AddrState'].', '.$v['AddrZip'];
		$propArray[$key]['dst'] = current(json_decode(file_get_contents("http://www.datasciencetoolkit.org/street2coordinates/".urlencode($address)), true));
	}

	$fp = fopen($filename, 'w+');
	fwrite($fp, serialize($propArray));
	fclose($fp);

?>