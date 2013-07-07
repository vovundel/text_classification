<?php

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

class PreperationData {

	private $vector = array();
	private $data = array();
	private $stop = array();

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
				
			$pre_data = array();
			$pre_data["output"] = $class;
				
			$tokens = $this->tokenise($line);
			$tokens = $this->del_stop_words($tokens);
				
			foreach($tokens as $token) {
				$pre_data[$token] = 0;
			}
			array_push($this->data, $pre_data);
		}
		fclose($fh);
	}
	
	public function prn(){
		echo '<b>data:</b><br>';
		foreach($this->data as $data_i) {
			print_r ($data_i); echo '<br>';
		}
		echo '<br>';
	}

	public function get_target($text){
		
		$text = $this->tokenise($text);
		//echo '<b>tokenise: </b>'; print_r($text); echo '<br>';
		$text = $this->del_stop_words($text);
		//echo '<b>del_stop_words: </b>'; print_r($text); echo '<br>';
		$text = $this->post_to_vector($text);
		//echo '<br><b>post_to_vector: </b>'; print_r ($text); echo '<br>';
		return $text;
	}

	public function get_target_file($file, $class, $i_start, $i_stop){
		$rez = array();
	
		$fh = fopen($file, 'r');
		$i = 0;
		if(!in_array($class, $this->classes)){
			echo "Invalid class specified\n";
			return;
		}		
		while($line = fgets($fh)){	
			if($i >= $i_stop){
				break;
			}
			if ($i >= $i_start){
				$tokens = array();
				$tokens = $this->tokenise($line);
				$tokens = $this->del_stop_words($tokens);
				$tokens = $this->post_to_vector($tokens);
				$tokens["output"] = $class;
					
				array_push($rez, $tokens);
			}
			$i++;
		}
		fclose($fh);
		
		return $rez;
	}
	
	private function process_data(){
		$dat = array();
		foreach($this->data as $post) {
			$dat1 = array();
			$dat1["output"] = $post["output"];
			foreach ($this->vector as $word => $value) {
				if(array_key_exists($word, $post)){
					//$dat1[$word] = floatval($value);
					$dat1[$word] = floatval(1);
					//binar term representation 
				}
				else{
					$dat1[$word] = 0;
				}
					
			}
			if (count($dat1) > 2){
				array_push($dat, $dat1);
			}

		}
		return $dat;
	}

	public function create_vector(){
		//dimension reduction
		$count = 999;
		$vec = array();
		foreach($this->data as $post) {
			foreach ($post as $word => $value) {
				if ($word != "output"){
					if(!array_key_exists($word, $vec)) {
						$vec[$word] = 1;
					}else{
						$vec[$word] += 1;
					}
				}
			}
		}
		$vec1 = array();
		$vec3 = array();
		foreach ($vec as $word => $value) {
			if($value >= 3) {
				// 3 - frequency dimension reduction 
				$vec1[$word] = $value;
			}
		}
		arsort($vec1);
		if(count($vec1) > $count) {

			$vec2 = array();
			foreach ($vec1 as $word => $value) {
				if(count($vec2) < $count) {
					$vec2[$word] = $value;
				}
			}
			$vec3 = $vec2;
		}else{
			$vec3 = $vec1;
		}
		
		//normalisation step
		/*
		 $s = 0;
		$vec4 = array();
		foreach ($vec3 as $word => $value) {
		$s += $value;
		}
		foreach ($vec3 as $word => $value) {
		$vec4[$word] = floatval($value/$s);
		}
		*/
		
		return $vec3;
	}

	public function get_data(){
	
		$this->vector = $this->create_vector();
		$this->data = $this->process_data();
	
		//print data
		//$this->prn();
	
		return $this->data;
	}

	public function set_vector($vect){
		$this->vector = $vect;
	}
	
	public function get_vector(){
		$rez = array();
		foreach ($this->vector as $word => $value) {
			array_push($rez, $word);
		}
		
		return $rez;
	}

	public function get_label_name(){
		return 'output';
	}
	
	private function post_to_vector($tokens) {
		$dat = array();

		foreach ($this->vector as $word => $value) {
			if(in_array($value, $tokens)){
				//$dat[$word] = floatval($value);
				$dat[$value] = floatval(1);
				//binar term representation
			}
			else{
				$dat[$value] = 0;
			}
		}
		//echo '<br><b>vector2: </b>'; print_r ($dat); echo '<br>';
		return $dat;
	}

	private function tokenise($document) {
		$document = strtolower($document);
		preg_match_all('/\w+/', $document, $matches);
		return $matches[0];
	}
	private function del_stop_words($list) {
		if (count($this->stop) == 0){
			$this->stop = $this->read_stop_words();
		}

		$rez = array();
		foreach ($list as $wo){
			if (!in_array($wo, $this->stop)) {
				if ( !preg_match('/[0-9]/', $wo) ){
					array_push($rez, $wo);
				}
			}
		}
		return $rez;
	}

	private function read_stop_words()
	{
		$array = array();
		$f=fopen("data/our_stop_words.txt","r");
		if($f){
			while (!feof($f)) {
				$line = fgets($f, 4096);
				if (strlen(trim($line)) > 0 ){
					array_push($array, trim($line));
				}
			}
		}
		fclose($f);
		return $array;
	}

}

class DecisionTree {

	private function count_in_arr($vals, $tar){
		$rez = 0;
		foreach ($vals as $val) {
			if ( $val == $tar ){
				$rez = $rez + 1;
			}
		}
		return $rez;
	}
	private function unique($vals){
		$rez = array();
		foreach ($vals as $val){
			if (!in_array($val, $rez)){
				array_push($rez, $val);
			}
		}
		return $rez;
	}

	private function get_examples($data, $att, $value){
		$rez = array();
		if (count($data) == 0){
			return $rez;
		}else{
			foreach ($data as $record) {
				if ($record[$att] == $value){
					array_push($rez, $record);					
				}
			}
		}
		return $rez;
	}
		
	private function get_values($data, $att){
		$rez = array();
		foreach ($data as $record) {
			array_push($rez, $record[$att]);
		}
		return $this->unique($rez);
	}

	private function majority_value($vals){
		$highest_freq = 0;
		$most_freq = array();
		
		$vals_u = $this->unique($vals);
		
		foreach ($vals_u as $val){
			if ($this->count_in_arr($vals, $val) > $highest_freq){					
				$most_freq = $val;
				$highest_freq = $this->count_in_arr($vals, $val);
			}	
		}
		return $most_freq;
	}
	
	private function choose_attribute($data, $attrs, $labelArrt){
		$best_gain = -100;
		$best_attr = '';
		
		foreach ($attrs as $att) {
			$gain = $this->get_gain($data, $att, $labelArrt);
			if ( ($gain >= $best_gain) and ($att != $labelArrt) ){
				$best_gain = $gain;
				$best_attr = $att;
			}
		}
		return $best_attr;
	}
	private function get_entropy($data, $labelArrt){
		$val_freq = array();
		$data_entropy = 0;
		
		# Calculate the frequency of each of the values in the target attr
		foreach ($data as $record) {
			if ( in_array($record[$labelArrt], $val_freq) ){
				$val_freq[$record[$labelArrt]] += 1;
			}else{
				$val_freq[$record[$labelArrt]] = 1;
			}
		}
		//echo '<b>_entropy: </b>'; print_r ($val_freq); echo '<br>';
		
		# Calculate the entropy of the data for the target attribute
		foreach ($val_freq as $word => $value) {
			$cur_val = $value/count($data);
			//echo '<b>_entropy: </b>'; print_r ($cur_val); echo '<br>';
			$data_entropy += - $cur_val * log( $cur_val, 2);	
		}
		//echo '<b>data_entropy: </b>'; print_r ($data_entropy); echo '<br>';
		return $data_entropy;
	}
	    		
	private function get_gain($data, $att, $labelArrt){	
		$rez = 0;
		$val_freq = array();
		$subset_entropy = 0;
		
		// Calculate the frequency of each of the values in the target attribute
		foreach ($data as $record) {
			if ( in_array($record[$att], $val_freq) ){
				$val_freq[$record[$att]] += 1;
			}else{
				$val_freq[$record[$att]] = 1;
			}
		}
		//echo '<b>_gain: </b>'; print_r ($val_freq); echo '<br>';

		// Calculate the sum of the entropy for each subset of records weighted
		// by their probability of occuring in the training set.
		$sum_freq_values = 0;
		foreach ($val_freq as $word => $value) {
			$sum_freq_values += $value;
		}		
		foreach ($val_freq as $word => $value) {
			
			$val_prob = $value / $sum_freq_values;
			//echo '<b>val_prob: </b>'; print_r ($val_prob); echo '<br>';
			
			$data_subset = array();
			foreach ($data as $record) {
				if ($record[$att] == $word){
					array_push($data_subset, $record);
				}
			}
			//echo '<b>subset_len: </b>'; print_r (count($data_subset)); echo '<br>';
			$subset_entropy += $val_prob * $this->get_entropy($data_subset, $labelArrt);
		}		
		//echo '<b>subset_entropy: </b>'; print_r ($subset_entropy); echo '<br>';
		$rez = ($this->get_entropy($data, $labelArrt) - $subset_entropy);
		
		//echo '<b>get_entropy: </b>'; print_r ($this->get_entropy($data, $labelArrt)); echo '<br>';
		//echo '<b>gain: </b>'; print_r ($rez); echo '<br>';
		
		return $rez;
	}

	public function create_tree($data, $attrs, $labelArrt){
		$vals = array();
		foreach ($data as $record) {
				array_push($vals, $record[$labelArrt]);
		}
		
		if ( (count($data) == 0) or (count($attrs) - 1 <= 0 ) ){
			# data is empty OR attrs without class is empty
			$max = $this->majority_value($vals);
			return $max;
		} elseif ($this->count_in_arr($vals, $vals[0]) == count($vals)){
			# all data have the same class
			return $vals[0];
		} else {
			# data have different classes
			# Choose the next best attribute to best classify our data
			
			$best = $this->choose_attribute($data, $attrs, $labelArrt);
			//echo '<b>best: </b>'; print_r ($best); echo '<br>';
			
			$tree = array();
			$tree[$best] = array(); 
			
			$curr_values = $this->get_values($data, $best);
			
			foreach ($curr_values as $val) {
				
				$attr_list = array();
				foreach ($attrs as $attr) {
					if( $attr != $best){
						array_push($attr_list, $attr);
					}
				}
				$subtree = $this->create_tree($this->get_examples($data, $best, $val), $attr_list, $labelArrt);
						
				// Add the new subtree to the empty dictionary object in our new
				// tree/node we just created.
				$tree[$best][$val] = $subtree;
				
				//echo '<b>tree: </b>'; print_r ($tree); echo '<br>';
			}					
		}
		return $tree;
	}
	
	private function get_classification($record, $tree){
		if ( is_string($tree) ){
		//if ( !is_array($tree) ){
			return $tree;
		} elseif ( is_array($tree) ){
			$attr = array_keys($tree)[0];
			$t = $tree[$attr][$record[$attr]];
			return $this->get_classification($record, $t);
		} else{
			//TODO if there are no items in tree than example is posivite 
			//echo '<b>current tree: </b>'; print_r ($tree); echo '<br>';
			return 1;
		}
	}	
	
	public function classify($tree, $data){
		$classification = array();
		foreach ($data as $record) {
			array_push($classification, $this->get_classification($record, $tree));
		}
		return $classification;
	}
	
	public function accuracy($arr1, $arr2){
		$matching_count = 0;
		$rez = array();
		if (count($arr1) == count($arr2)){
			for ($i = 0; $i < count($arr1); ++$i) {
				if ($arr1[$i] == $arr2[$i]){
					$matching_count += 1;
				}
				if(array_key_exists($arr2[$i], $rez) ){
					$rez[$arr2[$i]] += 1;
				} else {
					$rez[$arr2[$i]] = 1;
				}
			}				
		}
		print_r ($rez);
		return ($matching_count / count($arr1)) * 100;
	}		
	    		
	public function print_tree($tree){
		echo '<b>tree: </b>';
		echo '<pre>';
		var_dump($tree);
		echo '</pre><br>';		
	}
}

function save_arr_into_file($arr, $file){
	$string = serialize($arr);
	$fh = fopen($file, 'w');
	fwrite($fh, $string);
	fclose($fh);
	return 'ok';
}

function read_arr_into_file($file){
	$str = file_get_contents($file);
	$arr = unserialize($str);
	return $arr;
}


function start_test($file_pos, $file_neg, $count_train, $count_test){
	$name_file_tree = 'tree.txt';
	$name_file_vector = 'vector.txt';
	
	echo '<b>'. strtoupper('Classification Tree:') . '</b><br>part 1<br>';
	
	$time1 = microtime_float();
	
	$count_train_examples_in_class = $count_train;
	$count_test_examples_in_class = $count_test;
	
	echo 'count_train_examples_in_class: ' . $count_train_examples_in_class . 
			'; count_test_examples_in_class: ' . $count_test_examples_in_class . '<br>';
	
	$trainData = new PreperationData();
	$trainData->addToIndex($file_pos, '1', $count_train_examples_in_class);
	$trainData->addToIndex($file_neg, '0', $count_train_examples_in_class);
	
	$data = $trainData->get_data();
	$vector = $trainData->get_vector();
	echo '<b>length vector: </b>'. count($vector) . '<br>';
	
	$targetLabel = $trainData->get_label_name();
	
	$decTree = new DecisionTree();
	$tree = $decTree->create_tree($data, $vector, $targetLabel);
	
	$time2 = microtime_float() - $time1;
	echo '<b>time for tree creation: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//$decTree->print_tree($tree);
	
	//save tree and vector;
	save_arr_into_file($tree , $name_file_tree);
	save_arr_into_file($vector , $name_file_vector);
	
	echo '<br>part 2<br>';
	$trainData2 = new PreperationData();
	$decTree2 = new DecisionTree();
	
	$tree_new = read_arr_into_file($name_file_tree);
	$vector_new = read_arr_into_file($name_file_vector);
	//echo '<b>new length vector: </b>'. count($vector_new) . '<br>';
	
	$trainData2->set_vector($vector_new);
	$targetLabel2 = $trainData2->get_label_name();
	
	$time2 = microtime_float() - $time1;
	echo '<b>time for save and read from files: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	/*
	// test 0
	echo strtoupper('<b>test on training set </b>');
	$testClassify = $decTree2->classify($tree_new, $data);
	$givenTestClassify = array();
	foreach ($data as $record) {
		array_push($givenTestClassify, $record[$targetLabel2]);
	}
	echo '<b>acc: </b>' . $decTree2->accuracy($givenTestClassify, $testClassify) . '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 0: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	*/
	
	// test 1
	echo '<br>' . strtoupper('<b>test 1: one example</b>') . '<br>';
	$test = "i feel like my life now and my life in school are from different worlds .";
	$testData = $trainData2->get_target($test);
	//echo '<b>target: </b>'; print_r ($testData); echo '<br>';
	$testClassify = $decTree2->classify($tree_new, array($testData));
	echo '<b>test on one example, result: </b>'; print_r ($testClassify[0]); echo '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 1: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//test 2
	echo '<br>' . strtoupper('<b>test 2: positive set</b>') . '<br>';
	$testData2 = $trainData2->get_target_file($file_pos, '1', 
			$count_train_examples_in_class, $count_train_examples_in_class + $count_test_examples_in_class);
	$testClassify2 = $decTree2->classify($tree_new, $testData2);
	
	$givenTestClassify2 = array();
	foreach ($testData2 as $record) {
		array_push($givenTestClassify2, $record[$targetLabel2]);
	}
	echo '<b>acc: </b>' . $decTree2->accuracy($givenTestClassify2, $testClassify2) . '<br>';
	$time2 = microtime_float() - $time1;
	echo '<b>time for test 2: </b>' . $time2 . ' (sec)<br>';
	$time1 = microtime_float();
	
	//test 3
	echo '<br>' . strtoupper('<b>test 3: negative set</b>') . '<br>';
	$testData3 = $trainData2->get_target_file($file_neg, '0', 
			$count_train_examples_in_class, $count_train_examples_in_class + $count_test_examples_in_class);
	$testClassify3 = $decTree2->classify($tree_new, $testData3);
	
	$givenTestClassify3 = array();
	foreach ($testData3 as $record) {
		array_push($givenTestClassify3, $record[$targetLabel2]);
	}
	echo '<b>acc: </b>' . $decTree2->accuracy($givenTestClassify3, $testClassify3) . '<br>';
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