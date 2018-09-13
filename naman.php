<?php
$n=5;
$m = 2*$n-2;
//echo("naman");
for($i=0;$i<=$n-1;$i++)
{
	//echo "naman";
	for($j=$m;$j>=0;$j--)
	{
	   echo " ";

	}
    $m=$m-2;
    // echo "$m";
 	for($k=0;$k<=$i;$k++)
	{
	  echo " *";
	}
	header('Content-type: text/plain');
	echo "\n";
}