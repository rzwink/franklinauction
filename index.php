<?php
	//zillow X1-ZWz19it3cfwydn_7x5fi
	if(isset($_GET['saledate'])){
		$saledate = $_GET['saledate'];
	}else{
		$saledate = "10/28/2016";
	}

	$filename = 'propArray'.urlencode($saledate).date("Ymd").'.txt';

	if(!is_file($filename)){
		function getTagValue($line){
			$line = trim($line);
			$search = '<span id="c_printsearchresults_gvResults_lbl';
			$p = strpos($line, $search);
			if($p === false){
				// ^<td class="ReportFONT" style="width:5%;">(.*)<\/td><td class="ReportFONT" style="width:5%;">(.*)<\/td><td class="ReportFONT" style="width:5%;">(.*)<\/td>$
				
				$re = '/<td class="ReportFONT" width="5%">([^a-z]+)<\/td><td class="ReportFONT" width="5%">([^a-z]+)<\/td><td class="ReportFONT" width="5%">([^a-z]+)<\/td>/';
//				echo "searching" . $line . "for".$re;
				
				$found = preg_match_all($re, $line, $matches, PREG_PATTERN_ORDER);
				
				if($found > 0){
					// Print the entire match result
					//print_r($matches);		Opening Bid	Deposit
					return array("auction", array("Appraised"=>current($matches[1]), "Opening Bid"=>current($matches[2]), "Deposit"=>current($matches[3])));
				}else{
					return false;
				}
			}else{
				$tag = substr(substr($line, strlen($search)), 0, strpos(substr($line, strlen($search)), '"'));
				$value = substr(substr($line, strlen($search)), strpos(substr($line, strlen($search)), '">')+2);
				$value = substr($value, 0, strpos($value, "</span>"));	
				return array(explode("_", $tag)[0], $value);
			}
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

//		foreach($propArray as $key=>$v){
//			$address = $v['AddrNbr'].' '.$v['PropHalfInd'].' '.$v['AddrStrDir'].' '.$v['AddrStrName'].', '.$v['AddrCity'].', '.$v['AddrState'].', '.$v['AddrZip'];
//			$propArray[$key]['dst'] = @current(@json_decode(@file_get_contents("http://www.datasciencetoolkit.org/street2coordinates/".urlencode($address)), true));
//		}

		foreach($propArray as $key=>$v){
			$url = "http://www.zillow.com/webservice/GetDeepSearchResults.htm?zws-id=X1-ZWz19it3cfwydn_7x5fi&address=".urlencode($v['AddrNbr'].' '.$v['PropHalfInd'].' '.$v['AddrStrDir'].' '.$v['AddrStrName'])."&citystatezip=".urlencode($v['AddrCity'].', '.$v['AddrState'].', '.$v['AddrZip']);
			$xml = simplexml_load_string(@file_get_contents($url), "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$array = json_decode($json,TRUE);		
			
	
				$propArray[$key]['zillow'] = @$array['response']['results']['result'];

				$before = (int)str_replace(array("$", ","), "", ($propArray[$key]['auction']['Appraised']));
				$after = @(int)current($propArray[$key]['zillow']['zestimate']);

				$propArray[$key]['calczillowdiff']=@number_format((($after - $before) / $before) * 100, 1);
			
		}


		//1168%20glenn%20ave&citystatezip=Columbus,%20Ohio%2043212

		$fp = fopen($filename, 'w+');
		fwrite($fp, serialize($propArray));
		fclose($fp);
	}

	if(is_file("saledates.txt")){
		$salesDatesArray = file("saledates.txt");
	}

	$propArray = unserialize(file_get_contents($filename));
	$i=0;
	$json = array();
	foreach($propArray as $key=>$v){
		if($v['SSStatus']=='ACTIVE' && isset($v['zillow']['address'])){
		
			      $z = "AY ".@$v['zillow']['taxAssessmentYear']."</br>";  // => string '2015' (length=4)
			      $z .= "TA ".number_format(@$v['zillow']['taxAssessment'])."</br>";  // => string '76400.0' (length=7)
			      $z .= "YB ".@$v['zillow']['yearBuilt']."</br>";  // => string '1973' (length=4)
			      $z .= "Lot SqFt ".@$v['zillow']['lotSizeSqFt']."</br>";  // => string '6970' (length=4)
			      $z .= "SqFt ".@$v['zillow']['finishedSqFt']."</br>";  // => string '1288' (length=4)
			      $z .= "Bath ".@$v['zillow']['bathrooms']."</br>";  // => string '3.0' (length=3)
			      $z .= "Bed ".@$v['zillow']['bedrooms']."</br>";  // => string '3' (length=1)
			      $z .= "Last Sold ".@$v['zillow']['lastSoldDate']."</br>";  // => string '04/26/1993' (length=10)
			      $z .= "Last Price ".number_format(@$v['zillow']['lastSoldPrice'])."</br>";  // => string '44000' (length=5)
			$label = "";
			
			if($i++ < 9){
				$label = (string)$i;
				
			}else{
				$label = "";
				
			}
			
			
			$json[] = array($z."<a href='detail.php?saledate=".urlencode($saledate)."&key=".$key."' target='key'>".$key."</a></br>", $v['zillow']['address']['latitude'], $v['zillow']['address']['longitude'], $label);
		}
	}


	// Comparison function
	function cmp($a, $b) {
	    if(!isset($a['calczillowdiff'])||!isset($b['calczillowdiff'])){
	    	return -1;
	    }
	    
	    if ($a['calczillowdiff'] == $b['calczillowdiff']) {
		return 0;
	    }
	    return ($a['calczillowdiff'] < $b['calczillowdiff']) ? 1 : -1;
	}

	uasort($propArray, 'cmp');
	//var_dump($propArray);


?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Franklin County Sheriff Property Auction</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
    body {
        padding-top: 70px;
        /* Required padding for .navbar-fixed-top. Remove if using .navbar-static-top. Change if height of navigation changes. */
    }
    </style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAuFkDKW7V04eUMTSqh6MizbymnwqFuNXk"
          type="text/javascript"></script>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Real Estate Auction</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#">About</a>
                    </li>
                    <li>
                        <a href="#">Services</a>
                    </li>
                    <li>
                        <a href="#">Contact</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">

        <div class="row">
            <div class="col-lg-12 text-center">
                <h1>Franklin County, Ohio Sheriff - Real Estate Auction</h1>
                <p><form method="get">
                	Auction Date: <select onChange="submit()" name="saledate">
                		<?php
                			$selected = "";
                			foreach($salesDatesArray as $date){
                				$date = trim($date);
                				if($date == $saledate){
                					$selected = "SELECTED";
                				}else{
                					$selected = "";
                				}
                				echo "<option $selected>".$date."</option>";
                			}

                		?>
                	</select>
                </form></p>
                <p class="lead">  <div id="map" style="width: 1000px; height: 800px;"></div>

  <script type="text/javascript">
    var locations = <?php echo json_encode($json);?>;

    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 11,
      center: new google.maps.LatLng(40.000000, -83.0000),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    var infowindow = new google.maps.InfoWindow();

    var marker, i;

    for (i = 0; i < locations.length; i++) {
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
        label: locations[i][3],
        map: map
      });

      google.maps.event.addListener(marker, 'click', (function(marker, i) {
        return function() {
          infowindow.setContent(locations[i][0]);
          infowindow.open(map, marker);
        }
      })(marker, i));
    }
  </script></p>
<table>
<?php

	foreach($propArray as $key=>$v){
		echo "<tr><td><a href='detail.php?saledate=".urlencode($saledate)."&key=".$key."' target='key'>".$key."</a></td></tr>";
	}
?>
</table>
            </div>
        </div>
        <!-- /.row -->

    </div>
    <!-- /.container -->

    <!-- jQuery Version 1.11.1 -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

</body>

</html>
