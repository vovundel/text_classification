<?php
// Rusakov Vladimir

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

class KNN {
	
	private $posts = array();
	private $k = 5;
	private $classes = array('1','0');
	
	public function addToIndex($file, $class, $limit = 0) {
		$fh = fopen($file, 'r');
		$i = 0;
		if(!in_array($class, $this->classes)) {
			echo "Invalid class specified\n";
			return;
		}
		while($line = fgets($fh)) {
			if($limit > 0 && $i > $limit) {
				break;
			}
			$i++;
			
			if(!isset($this->posts[$class])) {
				$this->posts[$class] = array();
			}
			array_push($this->posts[$class],$line);
		}
		fclose($fh);
	}
	 
	public function classify($document) {
		
		$doc_vector = $this->getVector($document);
		$sims = array();
		
		foreach($this->classes as $class) {
			foreach($this->posts[$class] as $post) {
				$curr_sim = $this->cosineSim($doc_vector, $this->getVector($post));
				
				array_push($sims, array($curr_sim, $class) );
			}
		}
		arsort($sims); //sort
		
		$proc = 0;
		$j = 0;
		foreach($sims as $curr_sim) {
			if($j > $this->k) {
				break;
			}
			$j++;
			if( $curr_sim[1] == $this->classes[0] ){
				$proc++;
			}
		}
	
		//TODO
		if ($proc > 3){
			return '1';
		} else {
			return '0';
		}
		//return $proc * 20;
	}
	
	private function getVector($document) {
		$doc_vector = array();
		$tokens = $this->tokenise($document);
		foreach($tokens as $token) {
			if(!in_array($token, array_keys($doc_vector))) {
				$doc_vector[$token] = 0;
			}
			$doc_vector[$token]++;
		}
		return $doc_vector;
	}

	private function cosineSim($docA, $docB) {
		$result = 0;
		foreach($docA as $key => $weight) {
			$result += $weight * $docB[$key];
		}
		return $result;
	}
	
	private function tokenise($document) {
		$document = strtolower($document);
		preg_match_all('/\w+/', $document, $matches);
		return $matches[0];
	}
}

function preparation_file($file, $class, $i_start, $i_stop){
	$classes = array('1','0');
		
	$rez = array();
	$fh = fopen($file, 'r');
	$i = 0;
	
	if(!in_array($class, $classes)){
			echo "Invalid class specified\n";
			return;
	}
	
	while($line = fgets($fh)){
		if($i >= $i_stop){
			break;
		}
		if ($i >= $i_start){
			array_push($rez, $line);
		}
		$i++;
	}
	fclose($fh);
	return $rez;
}


/*
$knn1 = new KNN();
$knn1->addToIndex('data/lab01.txt', 'yes', 20);
$knn1->addToIndex('data/lab11.txt', 'no', 20);

$knnrez = $knn1->classify('if your cousin ( woman ) married a man who an older sister ... then you will call older sister ? is it will be still sister in law ?');

echo 'rez: ' . $knnrez;
*/

function start_test($file_pos, $file_neg, $count_train, $count_test){

	echo '<b>'. strtoupper('KNN:') . '</b><br>';

	$time1 = microtime_float();
	echo 'count_train_examples_in_class: ' . $count_train .
	'; count_test_examples_in_class: ' . $count_test . '<br>';

	$knn1 = new KNN();
	$knn1->addToIndex($file_pos, '1', $count_train);
	$knn1->addToIndex($file_neg, '0', $count_train);
	
	$time2 = microtime_float() - $time1;
	echo '<b>time for preparation: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	// test 1
	echo '<br>' . strtoupper('<b>test 1: one example</b>') . '<br>';
	$test = "i feel like my life now and my life in school are from different worlds .";
	$knnrez = $knn1->classify($test);
	echo '<b>test on one example, result: </b>' . $knnrez . '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 1: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//test 2
	echo '<br>' . strtoupper('<b>test 2: positive set</b>') . '<br>';
	$testData2 = preparation_file($file_pos, '1', $count_train, $count_train + $count_test);
	$lab1 = 0; $lab2 = 0;
	foreach ($testData2 as $record) {
		$knnrez = $knn1->classify($record);
		if($knnrez == '1'){
			$lab1++;
		}else{
			$lab2++;
		}
	}
	echo '(1:' . $lab1 . ', 0:' . $lab2 . ') ' . ' <b>acc: </b>' . ( $lab1 /$count_test ) * 100 . '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 2: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//test 3
	echo '<br>' . strtoupper('<b>test 3: negative set</b>') . '<br>';
	$testData3 = preparation_file($file_neg, '0', $count_train, $count_train + $count_test);
	$lab1 = 0; $lab2 = 0;
	foreach ($testData3 as $record) {
		$knnrez = $knn1->classify($record);
		if($knnrez == '1'){
			$lab1++;
		}else{
			$lab2++;
		}
	}
	echo '(1:' . $lab1 . ', 0:' . $lab2 . ') ' . ' <b>acc: </b>' . ( $lab2 / $count_test ) * 100 . '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 3: </b>' . $time2 . ' (sec)<br>';
}

if( isset($_GET['count']) ) {
	$count = intval( $_GET['count'] );
}else{
	$count = 20;
}

start_test('data/php_lab01.txt', 'data/php_lab11.txt', $count, $count);

?>