<?php
	class Commands{
		function Help($NSStoringenBot,$config,$data){
			$message = file_get_contents("texts/help.txt");
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function Unknown($NSStoringenBot,$config,$data){
			$message = "Commando `".$data["cmd"]."`niet gevonden.";
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function Ping($NSStoringenBot,$config,$data){
			$message = "Pong";
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function Check($NSStoringenBot,$config,$data){
			if(!$data["Parameters"][0]){
				$message = "Geef een stationsnaam op!";
			}else{
				$ch = curl_init(); // create a new cURL resource
				// set URL and other appropriate options
				$url = $config["NS"]["HTTP_BASE_URL"]."/ns-api-storingen?station=".$data["Parameters"][0]; // Set the URL to send the request to
				curl_setopt($ch, CURLOPT_URL, $url); // the the URL to cURL to send the request to
				curl_setopt($ch, CURLOPT_HEADER, 0); // I have no clue what this does
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Prevent cURL from echoing the results to the main script
				curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization:Basic ' . base64_encode($config["NS"]["USERNAME"].":".$config["NS"]["PASSWORD"]))); // Authorization header (I have no clue why they use Base64 for encoding...)
				$result = curl_exec($ch); // Execute the cURL thingy!
				curl_close($ch); // close cURL resource, and free up system resources

				$NSStoringenBot->writeLog($config, $result . "\r\n");

				$result = $NSStoringenBot->xml2array($result); // Turn the incoming XML in a usable array

				$ongepland = "";

				if(count($result["Storingen"]["Ongepland"]) > 0){
					$ongepland = "Ik heb de volgende ongeplande storingen kunnen vinden rondom station `" . ucfirst($data["Parameters"][0]) . "`:\r\n";
					print_r($result["Storingen"]["Ongepland"]);
					foreach($result["Storingen"]["Ongepland"]["Storing"] as $storing){
						$ongepland .= "- ".$storing["Traject"] . " (".$storing["Reden"].")\r\n" . $storing["Bericht"] . "\r\n\r\n";
					}
				}else{
					$ongepland = "Ik heb geen ongeplande storingen kunnen vinden rondom station `" . ucfirst($data["Parameters"][0]) . "`\r\n";
				}

				$gepland = "";

				if(count($result["Storingen"]["Gepland"]) > 0){
						$gepland = "Ik heb de volgende geplande storingen kunnen vinden rondom station `" . ucfirst($data["Parameters"][0]) . "`\r\n";
					foreach($result["Storingen"]["Gepland"]["Storing"] as $storing){
						print_r($storing);
						$gepland .= "- ".$storing["Traject"] . " (".$storing["Reden"].")\r\n" . $storing["Bericht"] . "\r\n\r\n";
					}
				}else{
					$gepland = "Ik heb geen geplande storingen kunnen vinden rondom station `" . ucfirst($data["Parameters"][0]) . "`\r\n";
				}

				$message = $ongepland . "\r\n\r\n" . $gepland;
			}

			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function Start($NSStoringenBot,$config,$data,$conn){
			$sql = "SELECT * FROM `Users` WHERE `Username`='" . mysqli_real_escape_string($conn,$data["Username"]) . "' OR `ChatID`='" . mysqli_real_escape_string($conn,$data["Recipient"]) . "';";
			$result = $conn->query($sql);
			if(!$result->num_rows > 0){
				$sql = "INSERT INTO `Users` (`ID`, `Username`, `ChatID`, `Subscribed`,`Uses`) VALUES (NULL, '". mysqli_real_escape_string($conn,$data["Username"]) ."', '". mysqli_real_escape_string($conn,$data["Recipient"]) ."', '0','1');";
				$conn->query($sql);
			}
			$message = file_get_contents("texts/start.txt");
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function About($NSStoringenBot,$config,$data){
			$message = file_get_contents("texts/about.txt");
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
		function Sub($NSStoringenBot,$config,$data,$conn){

			$sql = "SELECT * FROM `Users` WHERE `Username`='" . mysqli_real_escape_string($conn,$data["Username"]) . "' OR `ChatID`='" . mysqli_real_escape_string($conn,$data["Recipient"]) . "';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$row = $result->fetch_assoc();
				if($row["Subscribed"] == 1){
					$message = "Je bent al ingeschreven voor de storings melder!\r\nAls je je wilt uitschrijven voor de storingsmelder, stuur dan het commando `/unsub`.";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}else{
					$sql = "UPDATE `Users` SET `Subscribed`='1' WHERE `ID`='".mysqli_real_escape_string($conn,$row["ID"])."';";
					if($conn->query($sql) === TRUE){
						$message = "Je bent met success ingeschreven voor de storing melder!\r\nJe kan je elk moment uitschrijven met het commando `/unsub`.\r\nEr word grofweg elke 5 minuten gekeken of er een storing is.";
						$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
					}else{
						$message = "Er is iets mis gegaan. Probeer het later opnieuw!";
						$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
					}
				}
			}else{
				$sql = "INSERT INTO `Users` (`ID`, `Username`, `ChatID`, `Subscribed`,`Uses`) VALUES (NULL, '". mysqli_real_escape_string($conn,$data["Username"]) ."', '". mysqli_real_escape_string($conn,$data["Recipient"]) ."', '1','1');";
				if($conn->query($sql) === TRUE){
					$message = "Je bent met success ingeschreven voor de storing melder!\r\nJe kan je elk moment uitschrijven met het commando `/unsub`.";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}else{
					$message = "Er is iets mis gegaan. Probeer het later opnieuw!";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}
			}
		}
		function Unsub($NSStoringenBot,$config,$data){
			$conn = $NSStoringenBot->MySQLInit($config["Bot"]["MYSQL_HOST"],$config["Bot"]["MYSQL_USERNAME"],$config["Bot"]["MYSQL_PASSWORD"],$config["Bot"]["MYSQL_DATABASE"]);
			$sql = "SELECT * FROM `Users` WHERE `Username`='" . mysqli_real_escape_string($conn,$data["Username"]) . "' OR `ChatID`='" . mysqli_real_escape_string($conn,$data["Recipient"]) . "';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$row = $result->fetch_assoc();
				if($row["Subscribed"] == 0){
					$message = "Je bent al uitgeschreven voor de storings melder!\r\nAls je je wilt inschrijven voor de storingsmelder, stuur dan het commando `/sub`.";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}else{
					$sql = "UPDATE `Users` SET `Subscribed`='0' WHERE `ID`='".mysqli_real_escape_string($conn,$row["ID"])."';";
					if($conn->query($sql) === TRUE){
						$message = "Je bent met success uitgeschreven voor de storing melder!\r\nJe kan je elk moment weer inschrijven met het commando `/sub`.";
						$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
					}else{
						$message = "Er is iets mis gegaan. Probeer het later opnieuw!";
						$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
					}
				}
			}else{
				$sql = "INSERT INTO `Users` (`ID`, `Username`, `ChatID`, `Subscribed`) VALUES (NULL, '". mysqli_real_escape_string($conn,$data["Username"]) ."', '". mysqli_real_escape_string($conn,$data["Recipient"]) ."', '0');";
				if($conn->query($sql) === TRUE){
					$message = "Je bent met success uitgeschreven voor de storing melder!\r\nJe kan je elk moment weer inschrijven met het commando `/sub`.";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}else{
					$message = "Er is iets mis gegaan. Probeer het later opnieuw!";
					$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
				}
			}
		}
		function Voorwaarden($NSStoringenBot,$config,$data){
			$message = file_get_contents("texts/voorwaarden.txt");
			$NSStoringenBot->sendMessage($config,$data["Recipient"],$message);
		}
	}
