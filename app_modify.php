<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("data_manager.php");
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "front_modify"; // routing key
	$back_queue_get = "back_modify";  // binding key

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

		return ucwords(strtolower($string));
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

		// msg will be in the form { "title" : "Title", "body" : "New body..." }
		$data = json_decode($msg, true);

		$body = $data['body'];

		if (trim($body) == "")
			return '{"status" : "FAILED", "reason" : "An article\'s body cannot be empty."}';

		$title = $data['title'];
		if (trim($title) == "")
			return '{"status" : "FAILED", "reason" : "Title cannot be empty."}';
		$refined_title = refine_title($title);


		// create JSON to send
	    $msg = array(
	        "title"  => $refined_title,
	        "body" => $body
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