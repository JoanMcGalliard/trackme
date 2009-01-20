<?php

	require_once("config.php");
	
  $requireddb = urldecode($_GET["db"]);     
  if ( $requireddb == "" || $requireddb < 8 )
  {
    	echo "Result:5";
    	die;
  }	
	
	
	if(!@mysql_connect("$DBIP","$DBUSER","$DBPASS"))
	{
		echo "Result:4";
		die();
	}
	
	mysql_select_db("$DBNAME");
	
	
	
	// Check username and password
	$username = urldecode($_GET["u"]);
	$password = urldecode($_GET["p"]);
	
	$result=mysql_query("Select ID FROM users WHERE username = '$username' and password='$password'");
	if ( $row=mysql_fetch_array($result) )
	{
		$userid=$row['ID'];		// Good, user and password are correct.
	}
	else
	{
		$result=mysql_query("Select 1 FROM users WHERE username = '$username'");
		$nume=mysql_num_rows($result);	
		if ( $nume > 0 )
		{
			echo "Result:1"; // user exists, password incorrect.
			die();
		}		
		
		// User not specified
		if ( $username == "" || $password == "" )
		{
			echo "Result:3";
			die();
		}
		
		mysql_query("Insert into users (username,password) values('$username','$password')");			

		$result=mysql_query("Select ID FROM users WHERE username = '$username' and password='$password'");
		if ( $row=mysql_fetch_array($result) )
		{
			$userid=$row['ID'];	// User created correctly.	
		}
		else
		{		
			echo "Result:2"; // Unable to find user that was just created.
			die();		
		}
		
	}	
	
	
	
	$tripname = urldecode($_GET["tn"]);	
	$action = $_GET["a"];
		
	if($action=="delete")
	{	
		if ( $tripname == "<None>" )			
			$sql = "DELETE FROM positions WHERE FK_Trips_ID is null ";
		else if ( $tripname != "" )
			$sql = "DELETE FROM positions WHERE (FK_Trips_ID IN (SELECT ID FROM trips where name='$tripname' AND FK_Users_ID='$userid')) ";
		else
			$sql = "DELETE FROM positions WHERE 1=1 ";
					
		$sql.= " and FK_Users_ID = '$userid' ";

		$datefrom = urldecode($_GET["df"]);
		$dateto = urldecode($_GET["dt"]);
		
		if ( $datefrom != "" )
			$sql.=" and DateOccurred>='$datefrom' ";
		if ( $dateto != "" )
			$sql.=" and DateOccurred<='$dateto' ";			
						
		mysql_query($sql);
		
		echo "Result:0";
		die();		
	} 	
	
	
	if ( $action=="deletetrip" )
	{		
		 if ( $tripname == "" )
		 {
			echo "Result:6"; // trip not specified
		 	die();
		 }
		 		 
		 $tripid = "";
		 $result=mysql_query("Select ID FROM trips WHERE FK_Users_ID = '$userid' and name='$tripname'");
		 if ( $row=mysql_fetch_array($result) )
		 {
			 $tripid=$row['ID'];		
			 
			 mysql_query("delete from positions where fk_trips_id='$tripid'");
			 mysql_query("delete from trips where id='$tripid'");			 			 
			 
			 echo "Result:0";
			 die();			 
		 }
		 else
		 {
		 	  echo "Result:7"; // trip not found.
				die();					
		 }	 		
	}
	
	if ( $action=="addtrip" )
	{				
		 if ( $tripname == "" )
		 {
			echo "Result:6"; // trip not specified
		 	die();
		 }
		 	 		 
		 mysql_query("Insert into trips (name,fk_users_id) values ('$tripname','$userid')");		 	 
		 echo "Result:0";
  	 die();			 	
	}	
	
	if ( $action=="renametrip" )
	{				
		 if ( $tripname == "" )
		 {
			echo "Result:6"; // trip not specified
		 	die();
		 }
		 
		 $newname = $_GET["newname"];
		 
		 if ( $newname == "" )
		 {
			echo "Result:7"; // new name not specified
		 	die();
		 }
		 		 
		 mysql_query("Update trips set name='$newname' where name='$tripname' AND FK_Users_ID = '$userid'");		 	 
		 echo "Result:0";
  	 die();			 	
	}	
	
	if($action=="findclosestbuddy")
	{	
		$result=mysql_query("Select latitude,longitude FROM positions WHERE fk_users_id='$userid' order by dateoccurred desc limit 0,1");
		
		if ( $row=mysql_fetch_array($result) )
		{	
			$sql = "SELECT(  DEGREES(     ACOS(        SIN(RADIANS( latitude )) * SIN(RADIANS(".$row['latitude'].")) +";
			$sql.= "COS(RADIANS( latitude )) * COS(RADIANS(".$row['latitude'].")) * COS(RADIANS( longitude - ".$row['longitude'].")) ) * 60 * 1.1515 ";
			$sql.= ")  ) AS distance,dateoccurred,fk_users_id FROM positions WHERE FK_Users_ID <> '$userid' order by distance asc limit 0,1";
						
			$result=mysql_query($sql);	
			
			if ( $row=mysql_fetch_array($result) )
			{
				echo "Result:0|".$row['distance']."|".$row['dateoccurred']."|".$row['fk_users_id'];
			}						
			else
				echo "Result:7"; // No positions from other users found
			

		}
		else
			echo "Result:6"; // No positions for selected user

		die();		
	} 
	
	

	if( $action=="gettriplist")
	{

		$triplist = "";
		//$result = mysql_query("select name,min(dateoccurred) as startdate,max(dateoccurred) As enddate from trips where FK_USERS_ID='$userid' order by name");					
		$result = mysql_query("SELECT A1.name, (select min( A2.dateoccurred ) from positions A2 where A2.FK_TRIPS_ID=A1.ID) AS startdate  from trips A1 where A1.FK_USERS_ID='$userid' order by name");					
		
		while( $row=mysql_fetch_array($result) )
		{
			$triplist.=$row['name']."|".$row['startdate']."\n";			
		}

		$triplist = substr($triplist, 0, -1);		  
		echo "Result:0|$triplist";
		die();
	}
	
	if( $action=="geticonlist")
	{

		$iconlist = "";
		$result = mysql_query("select name from icons order by name");					
		while( $row=mysql_fetch_array($result) )
		{
			$iconlist.=$row['name']."|";
		}

		$iconlist = substr($iconlist, 0, -1);		  
		echo "Result:0|$iconlist";
		die();
	}
		
	


	if($action=="upload")
	{				
		$tripid = 'null';
		if ( $tripname != "" )
		{			
			$result=mysql_query("Select ID FROM trips WHERE FK_Users_ID = '$userid' and name='$tripname'");
			if ( $row=mysql_fetch_array($result) )
			{
				$tripid=$row['ID'];		
			}
			else // Trip doesn't exist. Let's create it.
			{
				mysql_query("Insert into trips (FK_Users_ID,Name) values('$userid','$tripname')");				
				
				$result=mysql_query("Select ID FROM trips WHERE FK_Users_ID = '$userid' and name='$tripname'");
				if ( $row=mysql_fetch_array($result) )
				{
					$tripid=$row['ID'];							
				}
				
				if ( $tripid == 'null' )
				{
					echo "Result:6"; // Unable to create trip.
					die();					
				}				
			}
		}
	
		$lat = $_GET["lat"];
		$long = $_GET["long"];
		$dateoccurred = urldecode($_GET["do"]);		
		$altitude = urldecode($_GET["alt"]);
		$angle = urldecode($_GET["ang"]);
		$speed = urldecode($_GET["sp"]);		
		$iconname = urldecode($_GET["iconname"]);
		$comments = urldecode($_GET["comments"]);		
		$imageurl = urldecode($_GET["imageurl"]);		
		$cellid = urldecode($_GET["cid"]);		
		$signalstrength = urldecode($_GET["ss"]);		
		$signalstrengthmax = urldecode($_GET["ssmax"]);		
		$signalstrengthmin = urldecode($_GET["ssmin"]);		
	  $batterystatus = urldecode($_GET["bs"]);	
	  $uploadss = urldecode($_GET["upss"]);	
	
		
		$iconid='null';		
		if ($iconname != "" ) 
		{
				$result=mysql_query("Select ID FROM icons WHERE name = '$iconname'");
				if ( $row=mysql_fetch_array($result) )
					$iconid=$row['ID'];							
		}
		

		$sql = "Insert into positions (FK_Users_ID,FK_Trips_ID,latitude,longitude,dateoccurred,fk_icons_id,speed,altitude,comments,imageurl,angle,signalstrength,signalstrengthmax,signalstrengthmin,batterystatus) values('$userid',$tripid,'$lat','$long','$dateoccurred',$iconid,";
			
		if ($speed == "" ) 
			$sql.="null,";
		else
			$sql.="'".$speed."',";					
			
	  if ($altitude == "" ) 
			$sql.="null,";
		else
			$sql.="'".$altitude."',";										
			
		if ($comments == "" ) 
			$sql.="null,";
		else
			$sql.="'".$comments."',";		

		if ($imageurl == "" ) 
			$sql.="null,";
		else
			$sql.="'".$imageurl."',";					
			
		if ($angle == "" ) 
			$sql.="null,";
		else
			$sql.="'".$angle."',";		
			
		if ($uploadss == 1 )
		{
			if ($signalstrength == "" ) 
				$sql.="null,";
			else
				$sql.=$signalstrength.",";								
				
			if ($signalstrengthmax == "" ) 
				$sql.="null,";
			else
				$sql.=$signalstrengthmax.",";								
				
			if ($signalstrengthmin == "" ) 
				$sql.="null,";
			else
				$sql.=$signalstrengthmin.",";														
		}
		else
			$sql.="null,null,null,";
			
		if ($batterystatus == "" ) 
			$sql.="null";
		else
			$sql.=$batterystatus;																	

			
			
		$sql.=")";
		

		$result = mysql_query($sql);	
		if (!$result) 
		{
			echo "Result:7|".mysql_error();		
			die();		
		}
		
		$upcellext = urldecode($_GET["upcellext"]);				
		if ($upcellext == 1 && $cellid != "" )
		{
			$sql = "Insert into cellids(cellid,latitude,longitude,signalstrength,signalstrengthmax,signalstrengthmin) values ('$cellid','$lat','$long',";
			
			if ($signalstrength == "" ) 
				$sql.="null,";
			else
				$sql.=$signalstrength.",";								
			
			if ($signalstrengthmax == "" ) 
				$sql.="null,";
			else
				$sql.=$signalstrengthmax.",";								
			
			if ($signalstrengthmin == "" ) 
				$sql.="null";
			else
				$sql.=$signalstrengthmin;							
				
			$sql.=")";			
					
			mysql_query($sql);
		}

		
		echo "Result:0";		
		die();		
	}
	
	if ($action == "sendemail" )
	{
		$to = $_GET["to"];
		$body = $_GET["body"];
		$subject = $_GET["subject"];
		
		if ( $subject == "" )
			$subject = "Notification alert";
		
		mail($to,$subject, $body, "From: TrackMe Alert System\nX-Mailer: PHP/");		
		
		echo "Result:0";		
		die();		
	}
	
	if($action=="updateimageurl")
	{		
		$imageurl = urldecode($_GET["imageurl"]);
		$id = urldecode($_GET["id"]);		
		
		$iconid='null';		
		$result=mysql_query("Select ID FROM icons WHERE name = 'Camera'");
		if ( $row=mysql_fetch_array($result) )
					$iconid=$row['ID'];							
		
	  mysql_query("update positions set imageurl='$imageurl',fk_icons_id=$iconid where id=$id");					
		
		echo "Result:0";			  
	  die();
	}
	
	if($action=="findclosestpositionbytime")
	{	
		$date = urldecode($_GET["date"]);
		
		if ( $date == "" )
		 {
			echo "Result:6"; // date not specified
		 	die();
		 }
		 
		$sql = "SELECT ID,dateoccurred FROM positions ";
		$sql.= "WHERE dateoccurred = (SELECT MIN(dateoccurred) ";
		$sql.= "FROM positions WHERE ABS(TIMESTAMPDIFF(SECOND,'$date',dateoccurred))= ";
		$sql.= "(SELECT MIN(ABS(TIMESTAMPDIFF(SECOND,'$date',dateoccurred))) ";
		$sql.= "FROM positions WHERE FK_USERS_ID='$userid') AND FK_USERS_ID='$userid') ";
		$sql.= "AND FK_USERS_ID='$userid'";
	
		$result=mysql_query($sql);	
		
		if ( $row=mysql_fetch_array($result) )
		{
			echo "Result:0|".$row['ID']."|".$row['dateoccurred'];
		}						
		else
			echo "Result:7"; // No positions from user found

		
		die();		
	} 	
	
	if($action=="findclosestpositionbyposition")
	{	
		
		$lat = $_GET["lat"];
		$long = $_GET["long"];
		
		if ( $lat == "" || $long== "" )
		 {
			echo "Result:6"; // position not specified
		 	die();
		 }
		 
		
		$sql = "SELECT(  DEGREES(     ACOS(        SIN(RADIANS( latitude )) * SIN(RADIANS(".$lat.")) +";
		$sql.= "COS(RADIANS( latitude )) * COS(RADIANS(".$lat.")) * COS(RADIANS( longitude - ".$long.")) ) * 60 * 1.1515 ";
		$sql.= ")  ) AS distance,ID, dateoccurred FROM positions WHERE FK_Users_ID = '$userid' order by distance asc limit 0,1";
					
		$result=mysql_query($sql);	
		
		if ( $row=mysql_fetch_array($result) )
		{
			echo "Result:0|".$row['ID']."|".$row['dateoccurred']."|".$row['distance'];
		}						
		else
			echo "Result:7"; // No positions from user found
			
		

		die();		
	} 
	
	if ($action=="gettripinfo")
	{
		if ( $tripname == "" )
		{
			echo "Result:6"; // trip not specified
			die();
		}
		
		$tripid = "";
		$result=mysql_query("Select ID FROM trips WHERE FK_Users_ID = '$userid' and name='$tripname'");
		if ( $row=mysql_fetch_array($result) )
		{
			 $tripid=$row['ID'];					 		
		}
		else
		{
		 	  echo "Result:7"; // trip not found.
				die();					
		}		
		
    // Total distance
    $oldlat = "";
    $oldlong = "";
    $miles = 0;
    $result = mysql_query("select latitude,longitude from positions where fk_trips_id='$tripid' order by dateoccurred");
    while( $row=mysql_fetch_array($result) )
    {
    	if ( $oldlat != "" && $oldlong != "" )
    	{
	    	$theta = $row['longitude'] - $oldlong;
	      $dist = sin(deg2rad($row['latitude'])) * sin(deg2rad($oldlat)) +  cos(deg2rad($row['latitude'])) * cos(deg2rad($oldlat)) * cos(deg2rad($theta));
	      $dist = acos($dist);
	      $dist = rad2deg($dist);	      
				if (is_nan($dist)) $dist = 0;
	      
	      $miles = $miles + $dist * 60 * 1.1515;
	    }
	    
	    $oldlat = $row['latitude'];
	    $oldlong = $row['longitude'];
	  
    }
    		
		// Start date and End date
		$result = mysql_query("SELECT TIMEDIFF(max(dateoccurred),min(dateoccurred)) as totaltime, min(dateoccurred) as startdate,max(dateoccurred) As enddate, count(*) AS totalpositions, max(speed) as maxspeed, avg(speed) as avgspeed, max(altitude) as maxaltitude, avg(altitude) as avgaltitude, min(altitude) as minaltitude FROM positions WHERE fk_trips_id='$tripid'");
		if ( $row=mysql_fetch_array($result) )
		{		 
			 echo "Result:0|$miles|".$row['startdate']."|".$row['enddate']."|".$row['totaltime']."|".$row['totalpositions']."|".$row['maxspeed']."|".$row['avgspeed']."|".$row['maxaltitude']."|".$row['avgaltitude']."|".$row['minaltitude'];		
		}
		
  	
		die();
	}
	
	if ($action=="gettriphighlights")
	{
		if ( $tripname == "" )
		{
			echo "Result:6"; // trip not specified
			die();
		}
		
		$tripid = "";
		$result=mysql_query("Select ID FROM trips WHERE FK_Users_ID = '$userid' and name='$tripname'");
		if ( $row=mysql_fetch_array($result) )
		{
			 $tripid=$row['ID'];					 		
		}
		else
		{
		 	  echo "Result:7"; // trip not found.
				die();					
		}		
		  
		$output = ""; 		
		$result = mysql_query("SELECT Latitude,Longitude,ImageURL,Comments,A2.URL IconURL FROM `positions` A1 left join icons A2 on A1.FK_Icons_ID=A2.ID WHERE fk_trips_id='$tripid' AND (ImageURL is not null or Comments is not null or FK_Icons_ID is not null)");
		while( $row=mysql_fetch_array($result) )
    {
    	$output.=$row['Latitude']."|".$row['Longitude']."|".$row['ImageURL']."|".$row['Comments']."|".$row['IconURL']."\n";
    }
  	echo "Result:0|$output";		
		die();
	}

?>

