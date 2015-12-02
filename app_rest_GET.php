<?php
	/**
	 * Business logic for GET REST call.
	 * Given a title, return
	 * {"title":"Title","body","Body"}
	 */
	include ("rpc_client.php");
	include ("rpc_server.php");
	
	$file_front = "broker.txt";
	$file_back = "broker2.txt";

	$front_queue_get = "FRONT_REST_GET"; // routing key
	$back_queue_get = "BACK_GET";  // binding key

	$rpcServer = new RpcServer($file_front);
	$rpcClient = new RpcClient($back_queue_get, $file_back);

	$title;

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
	 * Given a $title and $body, return a JSON string.
	 */
	function create_json($title, $body) {
		// send msg in form {"title":"$title","body":"$body"}
	    $data = array(
	        "title"  => $title,
	        "body" => $body
	    );

	    return json_encode($data);
	}

	/**
	 * Function called when message is received from producer.
	 * This function sends another message to the message broker.
	 */
	function foward_message($msg) {

		global $front_queue_get, $back_queue_get, $file_back, $title;

		// this is what will be called when a new message is received from
		// the next consumer.
		$func = function($str) {
            global $front_queue_get, $back_queue_get, $title;

			echo " [x] Received from server in '$back_queue_get' : $str\n";

			$json = "null"; // default no body
			if ($str && $str != "NULL")
				$json = create_json($title, $str);

			echo " [x] Forwarded to client in '$front_queue_get' : $json\n";
			return $json;
		};
		// end of function

		echo " [x] Received from client in '$front_queue_get' : $msg\n";

		// replace an underscores with spaces
		$refined_title = refine_title($msg);
		$title = $refined_title;

		echo " [x] Forwarded to server in '$back_queue_get' : $refined_title\n";

		// $rpcClient = new RpcClient($file_back);
		global $rpcClient;
		$response = $rpcClient->call($back_queue_get, $refined_title, $func);

		return $response;

	}

	$rpcServer->start($front_queue_get, 'foward_message');

?>
