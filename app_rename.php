<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "FRONT_RENAME"; // routing key
	$back_queue_get = "BACK_RENAME";  // binding key

	$rpcServer = new RpcServer($file_front);
	$rpcClient = new RpcClient($file_back);

	/**
	 * Replace underscores with spaces and uppercase the first letter of every
	 * word.
	 */
	function refine_title($string) {

		// replace underscores with spaces
		$string = preg_replace("(_)", " ", $string);

		// replace '%20' with space
		$string = preg_replace("(%20)", " ", $string);

		// replace multiple spaces with single space
		$string = preg_replace("([ ]{2,})", " ", $string);

		return trim(ucwords(strtolower($string)));
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

            // Receive and forward status

			echo " [x] Received from server '$back_queue_get' : $str\n";

			echo " [x] Forwarded to client '$front_queue_get' : $str\n";	// no formatting done

			return $str;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// msg will be in the form { "old_title" : "Title", "new_title" : "New Title" }
		$data = json_decode($msg, true);

		$old_title = $data['old_title'];
		if(trim($old_title) == "")
			return '{"status" : FAILED", "reason" : "Old title cannot be empty."}';
		$old_title = refine_title($old_title);

		$new_title = $data['new_title'];
		if(trim($new_title) == "")
			return '{"status" : FAILED", "reason" : "New title cannot be empty."}';
		$new_title = refine_title($new_title);

		if($new_title == $old_title)
			return '{"status" : FAILED", "reason" : "Titles are the same. No renaming to be done."}';

		// create JSON to send
	    $msg = array(
	        "old_title"  => $old_title,
	        "new_title" => $new_title
	    );

	    $msg = json_encode($msg);

		echo " [x] Forwarded to server '$back_queue_get' : $msg\n";

		// $rpcClient = new RpcClient($file_back);
		global $rpcClient;
		$response = $rpcClient->call($back_queue_get, $msg, $func);

		return $response;

	}

	$rpcServer->start($front_queue_get, 'foward_message');

?>