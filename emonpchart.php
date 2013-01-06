<?php

include("pChart2.1.3/class/pData.class.php");
include("pChart2.1.3/class/pDraw.class.php");
include("pChart2.1.3/class/pImage.class.php"); 

define("XSIZE", 900);
define("YSIZE", 450);

$now = time();
$then = $now - (1*24*60*60);
$hours=($now-$then)/(60*60);
$points=250;
$delta=($now-$then)/$points;

$db = mysql_connect("localhost", "***USER****", "****PASS******");  
if ( $db == "" ) { echo " DB Connection error...\r\n"; exit(); }  
      
mysql_select_db("****DATABASE*****",$db);  


$Requete = "
select (CONVERT((`time`/" . $delta . "),UNSIGNED)*" . $delta . ") as hourval,
AVG(feed_18) as `feed_18`,AVG(feed_76) as `feed_76`,AVG(feed_77) as `feed_77`,AVG(feed_10) as `feed_10`, AVG(feed_70) as `feed_70`, AVG(feed_71) as `feed_71` from (
select
    history.time,
    SUM(case when ID = 'feed_18' then data end) as 'feed_18',    
    SUM(case when ID = 'feed_76' then data end) as 'feed_76',    
    SUM(case when ID = 'feed_77' then data end) as 'feed_77',            
    SUM(case when ID = 'feed_10' then data end) as 'feed_10',    
    SUM(case when ID = 'feed_70' then data end) as 'feed_70',
    SUM(case when ID = 'feed_71' then data end) as 'feed_71'
FROM(
SELECT *,'feed_18' as ID FROM `feed_18`
UNION ALL
SELECT *,'feed_76' as ID FROM `feed_76`
UNION ALL
SELECT *,'feed_77' as ID FROM `feed_77`
UNION ALL
SELECT *,'feed_10' as ID FROM `feed_10`
UNION ALL
SELECT *,'feed_70' as ID FROM `feed_70`
UNION ALL
SELECT *,'feed_71' as ID FROM `feed_71` 
)
AS history WHERE `time` between " . $then ." and " . $now ."
group by time

) as summary

group by hourval
";

//echo $Requete;
//die();

$result = mysql_query($Requete,$db);  
$datapoints=0;

for ( $counter = 0; $counter <= $points; $counter++) {

//force time as we may not have data for the particular period
  $timestamp[]   = $then+$counter*$delta;
  
if(!$row = mysql_fetch_array($result)) break;
 
 $datapoints++;
  /* Push the results of the query in an array */
//  $timestamp[]   = $row["hourval"];

  
  if( $row["hourval"] = $then+$counter*$delta ) {

  if(is_null($row["feed_18"])) { $danitemp[] = VOID; }
     else { $danitemp[] =$row["feed_18"]; }

  if(is_null($row["feed_76"])) { $livingtemp[] = VOID; }
     else { $livingtemp[] =$row["feed_76"]; }

  if(is_null($row["feed_77"])) { $bedroomtemp[] = VOID; }
     else { $bedroomtemp[] =$row["feed_77"]; }


  if(is_null($row["feed_10"])) { $hppower[] = VOID; }
     else { $hppower[] =$row["feed_10"]; }


  if(is_null($row["feed_71"])) { $temperature[] = VOID; }
     else { $temperature[] =$row["feed_71"]; }


  if(is_null($row["feed_70"])) { $humidity[] = VOID; }
     else { $humidity[] =$row["feed_70"]; }
     
     } else {

     $danitemp[] = VOID; 
     $livingtemp[] = VOID; 
     $bedroomtemp[] = VOID; 
     $hppower[] = VOID; 
     $temperature[] = VOID; 
     $humidity[] = VOID; 
     
     }
 

}

$myData = new pData();
 
/* Save the data in the pData array */
$myData->addPoints($timestamp,"Timestamp");
$myData->addPoints($temperature,"Temperature");
$myData->addPoints($humidity,"Humidity");
$myData->addPoints($hppower,"Heat Pump power");

$myData->addPoints($danitemp,"Kids room temp");
$myData->addPoints($livingtemp,"Living room temp");
$myData->addPoints($bedroomtemp,"Bedroom temp");

 $myData->setAbscissa("Timestamp");
 $myData->setXAxisDisplay(AXIS_FORMAT_TIME,"d M y  H:i");
 $myData->setXAxisName("Time");

 $myData->setSerieOnAxis("Temperature", 0);
 $myData->setSerieOnAxis("Kids room temp", 0);
 $myData->setSerieOnAxis("Living room temp", 0);
 $myData->setSerieOnAxis("Bedroom temp", 0);


 $myData->setAxisName(0,"Temperature");
 $myData->setAxisUnit(0,"째C");
 $myData->setAxisPosition(0,AXIS_POSITION_RIGHT);

 /* Second Y axis will be dedicated to humidity */
$myData->setSerieOnAxis("Humidity", 1);
$myData->setAxisName(1,"Humidity");
$myData->setAxisUnit(1,"%");
$myData->setAxisPosition(1,AXIS_POSITION_RIGHT);
  
$myData->setSerieOnAxis("Heat Pump power", 2);
$myData->setAxisName(2,"Power");
$myData->setAxisUnit(2,"W");

 
$myPicture = new pImage(XSIZE,YSIZE,$myData); 
$myPicture->setGraphArea(60,60,XSIZE-90,YSIZE-50);
$myPicture->setFontProperties(array("FontName"=>"pChart2.1.3/fonts/pf_arma_five.ttf","FontSize"=>6));


$scaleSettings = array("DrawYLines"=>array(0),"TickAlpha"=>50, "GridAlpha"=>50, "LabelRotation"=>30,"XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"LabelSkip"=>10);
$myPicture->drawScale($scaleSettings );


$myPicture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>30));

 /* Write the chart legend */
 $myPicture->drawLegend(XSIZE/3,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

  /* Write the chart title */ 
 $myPicture->setFontProperties(array("FontName"=>"pChart2.1.3/fonts/Forgotte.ttf","FontSize"=>11));
 $myPicture->drawText(30,35,"Data graph for " . round($hours,2) ." hours",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMLEFT));

/* Restore font and set no shadow */
 $myPicture->setFontProperties(array("FontName"=>"pChart2.1.3/fonts/pf_arma_five.ttf","FontSize"=>6));
 $myPicture->setShadow(FALSE);

/* Put up stats below title */
 $Titlestats = "Datapoints: " . $datapoints . ". Graph generated on: " . date("r");
 $myPicture->drawText(XSIZE/3,40,$Titlestats,array("FontSize"=>6,"Align"=>TEXT_ALIGN_BOTTOMLEFT));


/* Turn on Antialiasing */
$myPicture->Antialias = TRUE;

$myData->setSerieDrawable("Power",TRUE);  
$myData->setSerieDrawable("Temperature",FALSE); 
$myData->setSerieDrawable("Humidity",FALSE); 

$myData->setSerieDrawable("Kids room temp",FALSE); 
$myData->setSerieDrawable("Living room temp",FALSE); 
$myData->setSerieDrawable("Bedroom temp",FALSE); 

$myPicture->drawAreaChart(array("ForceTransparency"=>30,"DisplayValues"=>FALSE,"DisplayColor"=>DISPLAY_AUTO)); 




$myData->setSerieDrawable("Temperature",TRUE); 
$myData->setSerieDrawable("Humidity",TRUE); 

$myData->setSerieDrawable("Kids room temp",TRUE); 
$myData->setSerieDrawable("Living room temp",TRUE); 
$myData->setSerieDrawable("Bedroom temp",TRUE); 

$myData->setSerieDrawable("Power",FALSE); 
 


$myPicture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>30));

$myPicture->drawSplineChart(array("BreakVoid"=>0, "BreakR"=>234, "BreakG"=>55, "BreakB"=>26));
// $myPicture->drawSplineChart();

 $myPicture->setShadow(FALSE);


$BoundsSettings = array("MaxDisplayR"=>255,"MaxDisplayG"=>255,"MaxDisplayB"=>255, "MinDisplayR"=>223,"MinDisplayG"=>224,"MinDisplayB"=>227,"DisplayColor"=>DISPLAY_AUTO);
$myPicture->writeBounds(BOUND_BOTH,$BoundsSettings);

$avgcons=round($myData->getSerieAverage("Heat Pump power"),0);
$myPicture->drawThreshold($avgcons,array("AxisID"=>2,"WriteCaption"=>TRUE,"Caption"=>"Average HP Consumption: ".$avgcons ."W, Total: ".round(($avgcons * $hours)/1000,2)."Kwh (". round(($avgcons * $hours)*0.18/1000,2)."lv)","Alpha"=>70,"Ticks"=>2,"R"=>0,"G"=>0,"B"=>255)); 


$myPicture->Stroke();


?>
