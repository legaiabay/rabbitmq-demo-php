<?php
	include_once("sender.php");

	$repeat = 10;
	for($i=0; $i<$repeat; $i++){
		
		// test : send and wait for callback/response
		$genPdf = new GeneratePDF();
		$response = $genPdf->generate_pdf(1);

		// test : just send
		// $response = $genPdf->generate_pdf(1, true);

		var_dump($response);
		echo "<br>";
	}
?>