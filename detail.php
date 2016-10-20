<?php
	$saledate = $_GET['saledate'];//"10/21/2016";
	$filename = 'propArray'.urlencode($saledate).date("Ymd").'.txt';

	if(!is_file($filename)){
		exit;
	}

	$propArray = unserialize(file_get_contents($filename));
	$v = $propArray[$_GET['key']];
	$address = $v['AddrNbr'].' '.$v['PropHalfInd'].' '.$v['AddrStrDir'].' '.$v['AddrStrName'].', '.$v['AddrCity'].', '.$v['AddrState'].', '.$v['AddrZip'];

?>
<table>
	<tr>
<td valign="top">
<a href="https://www.google.com/maps/place/<?php echo urlencode($address);?>" target="maps"><?php echo $address;?></a> <a href="<?php echo $v['zillow']['links']['homedetails'];?>" target="zillow">Zillow</a>
<iframe
  width="600"
  height="450"
  frameborder="0" style="border:0"
  src="https://www.google.com/maps/embed/v1/streetview?key=AIzaSyAc-3XDYg1KF7ihmtbL5ZeFtX9nt0-I_wE&location=<?php echo $propArray[$_GET['key']]['dst']['latitude'];?>,<?php echo $propArray[$_GET['key']]['dst']['longitude'];?>&heading=210&pitch=10&fov=35" allowfullscreen>
</iframe>
</td>

<td><pre>
<?php
	var_dump($propArray[$_GET['key']]);

?></pre>
</td></tr></table>