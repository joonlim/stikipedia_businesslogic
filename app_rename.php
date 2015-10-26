<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "front_rename"; // routing key
	$back_queue_get = "back_rename";  // binding key

	/**
	 * Replace underscores with spaces and uppercase the first letter of every
	 * word.
	 */
	function refine_title($title) {

		// replace underscores with spaces
		$refined_title = RegExUtilities::replace_underscores($title);

        // Uppercase first letter of every word in the title.
        $refined_title = ucwords(strtolower($refined_title));

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

		// create JSON to send
	    $msg = array(
	        "old_title"  => $old_title,
	        "new_title" => $new_title
	    );

	    $msg = json_encode($msg);

		echo " [x] Forwarded to server '$back_queue_get' : $msg\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $msg, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>