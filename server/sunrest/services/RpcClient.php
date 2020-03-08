<?php

/**
 * It sends messages to a Rabbit queue and receives the aknowledge
 */

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcClient
{
    
    const EXCHANGE_NAME = 'readings';
    
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id; // Identifies the server
    
    
    public function __construct() {
        
        $host = getenv('RABBITMQ_SERVER');
        $user = getenv('RABBITMQ_USER');
        $pwd = getenv('RABBITMQ_PASS');
        
        // Connection to the Rabbit server (the channel, object AMQPChannel)
        $this->connection = new AMQPStreamConnection (
            $host,
            5672,
            $user,
            $pwd
        );
        $this->channel = $this->connection->channel();
        
        // Define the exchange
        $this->channel->exchange_declare(
            self::EXCHANGE_NAME,    // The name of the exchange
            'direct',               // Type of exchange
            false,                  // passive
            true,                   // durable
            false                   // auto_delete
        );
        
        // The queue to listen for the feedback
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",     // queue (if null the name is randomly generated)
            false,  // passive
            false,  // durable
            true,   // exclusive
            false   // auto_delete
        );
        
        // Access to the feedback queue
        $this->channel->basic_consume(
            $this->callback_queue,  // name of the callback queue previously defined
            '',                     // consumer tag
            false,                  // no_local
            true,                   // no_ack
            false,                  // exclusive
            false,                  // nowait
            array(                  // callback
                $this,
                'onResponse'        // function callback
            )
        );
    }
    
    /**
     * Method to call wnen an answer is available. The response will be in $this->response 
     */
    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }
    
    /**
     * Make a call sending a message and waiting for a feedback
     * @param string $queue The name of the queue where to send the message
     * @param string $m The nessage to send
     * @param integer $timeout The time, in seconds, to wait for the answer
     * @return number
     */
    public function call($queue, $m, $timeout=20)
    {
        $this->response = null;
        $this->corr_id = uniqid();
        
        $msg = new AMQPMessage(
            (string) $m,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
        
        $this->channel->basic_publish(
            $msg,                    // The message object 
            self::EXCHANGE_NAME,     // exchange name
            $queue                   // routing_key (Name of the queue where to send the message)
        );
        
        /*
        // Wait for the feedback
        try {
            $this->channel->wait(
                null,   // allowed_methods
                false,    // non_blocking
                5        // timeout
            );
        } catch (Exception $e) {
            echo "Exception $e in Rpc::channel->wait() call";
        }
        */
        
        // Wait for the answer
        $count = 0;
        while (!$this->response) {
            $this->channel->wait(
                null,   // allowed_methods
                true    // non_blocking
                //10        // timeout
            );
            $count++;
            if ($count > $timeout)
                break;
            sleep(1);
        }
        return $this->response;
    }
}





?>