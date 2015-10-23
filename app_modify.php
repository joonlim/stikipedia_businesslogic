<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker_front.txt";
	$file_back = "broker_back.txt";

	$front_queue_get = "front_modify"; // routing key
	$back_queue_get = "back_modify";  // binding key

	$raw_get = "back_get";
	/**
	 * Replace underscores with spaces and uppercase the first letter of every
	 * word.
	 */
	function refine_title($title) {

		// replace underscores with spaces
		$refined_title = RegExUtilities::replace_underscores($title);

        // Uppercase first letter of every word in the title.
        $refined_title = ucwords($refined_title);

		return $refined_title;
	}

	/**
	 * Function called when message is received from producer.
	 * This function sends another message to the message broker.
	 */
	function foward_message($msg) {

		global $front_queue_get, $back_queue_get, $file_back;

		// this is what will be called when a new message is received from
		// the next consumer.
		$func = function($str) {
            global $front_queue_get, $back_queue_get;

			echo " [x] Received from server '$back_queue_get' : $str\n";

			echo " [x] Forwarded to client '$front_queue_get' : $str\n";	// no formatting done

			return $str;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// msg will be in the form { "title" : "Title", "body" : "New body..." }
		$data = json_decode($msg, true);
		$title = $data['title'];

		echo " [x] Forwarded to server in '$raw_get' : $title\n";

		// Check if this title has a body
		$rpcClient = new RpcClient($file_back);
		$exist_status = intval($rpcClient->call($raw_get, $msg, ""));

		echo " [x] Received from server in '$raw_get' : $exist_status\n";

		/*
		 * $exist_status can be 0, 1, or 2
		 *
		 * 0: no article with this title exists
		 * 1: article with this title exists and it has a body
		 * 2: article with this title exists but it does not have a body
		 */

		if ($exist_status === 0) {
			// no article with this title exists, we must CREATE a new record

		}
		else if ($exist_status == 1) {
			// article with this title exists and it has a body, we must UPDATE
		}
		else { 
			// article with this title exists but it does not have a body,
			// CREATE
		}


		echo " [x] Forwarded to server '$back_queue_get' : $refined_title\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $refined_title, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>