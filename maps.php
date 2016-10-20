<?php
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
				return $p;
			}

			$tag = substr(substr($line, strlen($search)), 0, strpos(substr($line, strlen($search)), '"'));
			$value = substr(substr($line, strlen($search)), strpos(substr($line, strlen($search)), '">')+2);
			$value = substr($value, 0, strpos($value, "</span>"));
			return array(explode("_", $tag)[0], $value);
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
			$propArray[$key]['dst'] = @current(@json_decode(@file_get_contents("http://www.datasciencetoolkit.org/street2coordinates/".urlencode($address)), true));
		}

		$fp = fopen($filename, 'w+');
		fwrite($fp, serialize($propArray));
		fclose($fp);
	}

	if(is_file("saledates.txt")){
		$salesDatesArray = file("saledates.txt");
	}

	$propArray = unserialize(file_get_contents($filename));
	$i=1;
	$json = array();
	foreach($propArray as $key=>$v){
		if($v['SSStatus']=='ACTIVE'){
			$json[] = array("<a href='detail.php?saledate=".urlencode($saledate)."&key=".$key."' target='key'>".$key."</a>", $v['dst']['latitude'], $v['dst']['longitude'], $i++);
		}
	}

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
                	<select onChange="submit()" name="saledate">
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
      zoom: 10,
      center: new google.maps.LatLng(40.076927, -82.9991),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    var infowindow = new google.maps.InfoWindow();

    var marker, i;

    for (i = 0; i < locations.length; i++) {
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
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
