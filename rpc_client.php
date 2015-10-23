<?php
	/**
	 * This API is used to create a connection between a RPC client and a
	 * RabbitMQ message broker using PHP.
	 *
	 * Important:
	 * RabbitMQ must be set up in the producer, consumers, and brokers.
	 * 
	 * Usage: 
	 * 1. Create an instance of RpcClient in the producer.
	 * The constructor takes file (default: "broker_ip.txt") which should 
	 * contain the message broker's IP on the first line.
	 * 2. Call RpcClient::call(), which takes a routing key, a message to send,
	 * and a function to call when a message is returned by the consumer.
	 * 3. The routing key must match the binding key of the consumer for the
	 * message to be recognized.
	 * 4. The message must be a string (preferably JSON format).
	 * 5. The function should take a string as its only parameter since it will
	 * be handling the returning message.
	 */
	
	// include libraries
	require_once __DIR__ . '/vendor/autoload.php';
	use PhpAmqpLib\Connection\AMQPStreamConnection;
	use PhpAmqpLib\Message\AMQPMessage;

	/**
	 * A class used to send messages to a message broker.
	 */
	class RpcClient {

		private $connection;
	    private $channel;
	    private $callback_queue;
	    private $response;
	    private $corr_id;
        private $broker_ip;
        
        public function getBrokerIP() {
            return $this->broker_ip;
        }

	    private function createAMQPStreamConnection($file) {

			// get the broker ip from a file "broker_ip.txt"
			$myfile = fopen($file, "r") or die("Unable to open file!");
			$filestring = fread($myfile,filesize($file));

			$lines = explode("\n", $filestring);
			$broker_ip = $lines[0];
            $this->broker_ip = $broker_ip;
			// create a connection to the server
			$connection = new AMQPStreamConnection($broker_ip, 5672, 'guest', 'guest');
			return $connection;
		}

	    public function __construct($file = "broker_ip.txt") {
	    	// create a connection to the server
	        $this->connection = $this->createAMQPStreamConnection($file);
	        $this->channel = $this->connection->channel();

			// declare a queue to send to
			// this creates the queue if it does not exist
	        list($this->callback_queue, ,) = $this->channel->queue_declare(
	            "", false, false, true, false);

	        $this->channel->basic_consume(
	            $this->callback_queue, '', false, false, false, false,
	            array($this, 'on_response'));
	    }

	    public function on_response($rep) {
	        if($rep->get('correlation_id') == $this->corr_id) {
	            $this->response = $rep->body;
	        }
	    }

	    /**
	     * Send a message to a queue using a routing key and wait for a 
	     * and call a function using the response.
	     * 
	     * @param  $routing_key queue name
	     * @param  $str 		String to send as a message.        
	     * @param  $func        A function that is called after we get a response.
	     *                      The function must take the response string as its parameter.
	     * @return the response
	     */
	    public function call($routing_key, $str, $func) {
	        $this->response = null;
	        $this->corr_id = uniqid();

	        // message to send to exchange
	        $msg = new AMQPMessage(
	            $str,
	            array('correlation_id' => $this->corr_id,
	                  'reply_to' => $this->callback_queue)
	            );

	        // basic publish
	        $this->channel->basic_publish($msg, '', $routing_key);

	        while(!$this->response) {
	            $this->channel->wait();
	        }

			// close channel and connection
			$this->channel->close();
			$this->connection->close();

			// if $func is empty, just return the response from the broker.
			if (empty($func))
				return $this->response;
			// func() must take the response string as its parameter.
			// return whatever func() returns.
			else
				return (string) $func($this->response);

	    }
	}

?>
