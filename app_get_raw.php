<?php
	/**
	 * Business logic for get raw body request.
	 */
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "front_get_raw"; // routing key
	$back_queue_get = "back_get";  // binding key

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

			echo " [x] Received from server in '$back_queue_get' : $str\n";

			echo " [x] Forwarded to client in '$front_queue_get' : $str\n";	// no formatting done
			return $str;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// replace an underscores with spaces
		$refined_title = refine_title($msg);

		echo " [x] Forwarded to server in '$back_queue_get' : $refined_title\n";

		$rpcClient = new RpcClient($file_back);
		$response = $rpcClient->call($back_queue_get, $refined_title, $func);

		return $response;

	}

	$rpcServer = new RpcServer($file_front);

	$rpcServer->start($front_queue_get, 'foward_message');

?>