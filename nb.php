<?php

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

class NB_YES_NO {
        private $index = array();
        private $classes = array('1','0');
        private $classTokCounts = array('1' => 0, '0' => 0);
        private $tokCount = 0;
        private $classDocCounts = array('1' => 0, '0' => 0);
        private $docCount = 0;
        private $prior = array('1' => 0.5, '0' => 0.5);
                                        
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
                       
                        $this->docCount++;
                        $this->classDocCounts[$class]++;
                        $tokens = $this->tokenise($line);
                        foreach($tokens as $token) {
                                if(!isset($this->index[$token][$class])) {
                                        $this->index[$token][$class] = 0;
                                }
                                $this->index[$token][$class]++;
                                $this->classTokCounts[$class]++;
                                $this->tokCount++;
                        }
                }
                fclose($fh);
        }
                       
        public function classify($document) {
                $this->prior['yes'] = $this->classDocCounts['yes'] / $this->docCount;
                $this->prior['no'] = $this->classDocCounts['no'] / $this->docCount;
                
                $tokens = $this->tokenise($document);
                $classScores = array();

                foreach($this->classes as $class) {
                        $classScores[$class] = 1;
                        foreach($tokens as $token) {
                                $count = isset($this->index[$token][$class]) ?
                                        $this->index[$token][$class] : 0;

                                $classScores[$class] *= ($count + 1) /
                                        ($this->classTokCounts[$class] + $this->tokCount);
                        }
                        $classScores[$class] = $this->prior[$class] * $classScores[$class];
                }
               
                arsort($classScores);
                //return $classScores;
                return key($classScores);
                
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

//TODO
function start_test($file_pos, $file_neg, $count_train, $count_test){
	echo '<b>'. strtoupper('NB:') . '</b><br>';

	$time1 = microtime_float();

	echo 'count_train_examples_in_class: ' . $count_train .
	'; count_test_examples_in_class: ' . $count_test . '<br>';

	$op_lab1 = new NB_YES_NO();
	$op_lab1->addToIndex($file_pos, '1', $count_train);
	$op_lab1->addToIndex($file_neg, '0', $count_train);

	// test 1
	echo '<br>' . strtoupper('<b>test 1: one example</b>') . '<br>';
	$test = "i feel like my life now and my life in school are from different worlds .";
	$class01 = $op_lab1->classify($test);
	echo '<b>test on one example, result: </b>'; print_r ($class01); echo '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 1: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//test 2
	echo '<br>' . strtoupper('<b>test 2: positive set</b>') . '<br>';
	$testData2 = preparation_file($file_pos, '1', $count_train, $count_train + $count_test);
	$lab1 = 0; $lab2 = 0;
	foreach ($testData2 as $record) {
		$class01 = $op_lab1->classify($record);
		if($class01 == '1'){
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
		$class01 = $op_lab1->classify($record);
		if($class01 == '1'){
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
// http://localhost/test/nb_yes_no.php?count=300


?>