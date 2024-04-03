<?php

require_once '../../vendor/autoload.php';
require_once 'rabbitmq.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class GeneratePDFConsumer extends RabbitMQ{

    public function __construct() {
        parent::__construct();
    }

    private function generate_pdf(){
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(40,10,'Hello World!');

        $filename = 'report-' . microtime() . '.pdf';
        $pdf->Output('F', '../../public/pdf/' . $filename);
    }

    public function run(){

        // create new topic
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('topic_gen_payroll', 'topic', false, false, false);

        list($queue_name, ,) = $this->channel->queue_declare('', false, false, true, false);

        $binding_key = 'gen_payroll.company.151200002';

        $this->channel->queue_bind($queue_name, 'topic_gen_payroll', $binding_key);

        echo " [*] Waiting for logs. To exit press CTRL+C\n";

        // create callback function
        $callback = function ($msg) {
            echo "got request! " . $msg->getRoutingKey() . "\n";

            // get payload body
            $payload = $msg->getBody();
            echo $payload . "\n";

            // decode payload json
            $data = json_decode($payload, true);

            // for testing
            sleep(1);

            // do anything here
            $this->generate_pdf();

            // check if message has correlation_id
            // if no correlation_id, then ack immediately
            try {
                $corr_id = $msg->get('correlation_id');
            } catch (\OutOfBoundsException $e) {
                // if no correlation_id then ack
                // $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            
                echo "request processed! \n";
                return;
            }

            // set response
            $response = json_encode(array(
                "status" => "success",
                "code" => 200,
                "message" => "mantap"
            ));

            echo $msg->get('correlation_id') . "\n";
            echo $msg->get('reply_to') . "\n";

            // set response message
            $res = new AMQPMessage(
                $response,
                array('correlation_id' => $msg->get('correlation_id'))
            );

            // publish response back to sender
            $msg->delivery_info['channel']->basic_publish(
                $res,
                '',
                $msg->get('reply_to')
            );

            echo "response sent! \n";   
        };

        // message consume limit / prefetch
        $this->channel->basic_qos(null, 1, null);

        // consume and process message requests
        $this->channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        // keep runtime alive
        while (count($this->channel->callbacks)) {
            try {
                echo "waiting \n";
                $this->channel->wait();
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Do nothing.
                echo "error 1 \n";
                echo $e->getMessage();
                break;
            } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
                // Do nothing.
                echo "error 2 \n";
                echo $e->getMessage();
                break;
            }
        }

        // close connection
        $this->channel->close();
        $this->connection->close();
    }
}


// run consumer
$consumer = new GeneratePDFConsumer();
$consumer->run();

?>