<?php
    require("SensitiveData/config.php");
    require("class.php");
		require("commands.php");

		$NSStoringenBot = new NSStoringenBot;
		$Commands = new Commands;

		// Start a MySQL connection
		$conn = $NSStoringenBot->MySQLInit($config["Bot"]["MYSQL_HOST"],$config["Bot"]["MYSQL_USERNAME"],$config["Bot"]["MYSQL_PASSWORD"],$config["Bot"]["MYSQL_DATABASE"]);

		$raw_input = file_get_contents('php://input'); // takes the raw input.
		$input = json_decode($raw_input,1); // Takes the raw input and put it in a usable little array.




		// put the commands, parameters etc. in a neat little array
		$data = array(
										"Recipient" => $input["message"]["chat"]["id"],
										"Username" => $input["message"]["chat"]["username"],
										"cmd" => strtok($input["message"]["text"], " "),
										"Parameters" => explode(" ", substr(strstr($input["message"]["text"]," "), 1)),
										"Input" => $input["message"]["text"]
									);

		// Insert a log entry
		$NSStoringenBot->writeLog($conn,$data);

		// Check which command to run
		switch (strtolower($data["cmd"])) {
			case "/start":
				$Commands->Start($NSStoringenBot,$config,$data,$conn);
				break;
			case "/check":
				$Commands->Check($NSStoringenBot,$config,$data);
				break;
    	case "/help":
        $Commands->Help($NSStoringenBot,$config,$data);
        break;
			case "/about":
	      $Commands->About($NSStoringenBot,$config,$data);
	      break;
			case "/sub":
				$Commands->Sub($NSStoringenBot,$config,$data,$conn);
				break;
			case "/unsub":
				$Commands->Unsub($NSStoringenBot,$config,$data,$conn);
				break;
			case "/ping":
	      $Commands->Ping($NSStoringenBot,$config,$data);
	      break;
			case "/voorwaarden":
		    $Commands->Voorwaarden($NSStoringenBot,$config,$data);
		    break;
			default:
				$Commands->Unknown($NSStoringenBot,$config,$data);
				break;
		}
