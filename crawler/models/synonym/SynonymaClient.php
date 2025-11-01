<?php

namespace crawler\models\parser;

use Exception;

/*
Name: Synonyma API Client 
Version: 0.1
URL: http://synonyma.ru
*/

class SynonymaClient
{
	private $curl;
	public $url = 'http://synonyma.ru/api/synonymarpc.php';
	
	public $login;
	public $hash;
	
	public function __construct($login, $password)
	{
		$this->login = $login;
		$this->hash = md5($login.$password);
	}
	
	/*
	* Creating a request to the Synonyma API
	* array $params - an array of parameters sent to the server
	*/
	private function request($params)
	{
		$params['login'] = $this->login;
		$params['hash'] = $this->hash;
		
		try{
			if(!$this->curl = curl_init())
				throw new Exception("An error occurred while initializing the Curl library");

			curl_setopt($this->curl, CURLOPT_URL,$this->url);
			curl_setopt($this->curl, CURLOPT_POST,1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION,1); 
			curl_setopt($this->curl, CURLOPT_HEADER,0);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,1);  

			try {
				if(!$data = curl_exec($this->curl))
					throw new Exception("Unable to retrieve data from URL: " . $this->url);
			} 
			catch (Exception $e) {
				echo $e->getMessage();
			}	
			curl_close($this->curl);
			return $data;
		} 
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	/*
	* Decoding the server response. The response is in json format
	* string $json - the response string in json format
	*/
	private function decode($json)
	{
		try {
			if (!$obj = json_decode($json)) 
				throw new Exception("The received data is in an incorrect format.");
			return $obj;
		} 
		catch (Exception $e) {
			echo $e->getMessage();
		}
		return false;
	}
	
	/*
	* Text synonymization
	* string $text - the text that needs to be processed through the synonymizer
	* string $dictionaries - unique dictionary names, separated by commas (,). Default dictionaries must have the prefix "defaults*", for example: defaults*default_ru
	*/
	public function synonymize($text, $dictionaries)
	{
		$params['action'] = 'synonymize';
		$params['dicts'] = $dictionaries;
		$params['text'] = $text;
		
		return $this->decode($this->request($params));
	}
	
	/*
	* Text typograph
	* string $text - the text that needs to be typographed
	*/
	public function typograph($text)
	{
		$params['action'] = 'typograph';
		$params['text'] = $text;
		
		return $this->decode($this->request($params));
	}
	
	/*
	* Text multiplier
	* string $text - template text for multiplication
	* int $count - maximum number of variants
	* string $encoding - result encoding, can be one of: UTF-8, CP1251, KOI8-R, ISO-8859-5
	* bool $twins - true - remove duplicates, false - do not check for duplicates
	* bool $typograph - true - typograph the result, false - leave as is
	*/
	public function breeder($text, $count, $encoding, $twins, $typograph)
	{
		$params['action'] = 'breeder';
		$params['typograph'] = $typograph;
		$params['encoding'] = $encoding;
		$params['twins'] = $twins;
		$params['count'] = $count;

		return $this->decode($this->request($params));
	}
	
	/*
	* Downloading the latest file created by the breeder
	*/
	public function download()
	{
		$params['action'] = 'download';
		return $this->decode($this->request($params));	
	}
	
	/*
	* Returns subscription statistics
	*/
	public function statistic()
	{
		$params['action'] = 'statistic';
		return $this->decode($this->request($params));
	}
	
	/*
	* Returns the list of dictionaries
	*/	
	public function dictionaries()
	{
		$params['action'] = 'dictionaries';
		return $this->decode($this->request($params));
	}
	
	/*
	* Returns subscription information
	*/		
	public function subscription()
	{
		$params['action'] = 'subscription';
		return $this->decode($this->request($params));
	}
	
	/*
	* Prepares a string from the list of dictionaries
	* array $defaults - array of default dictionary names
	* array $custom - array of custom dictionary names
	*/	
	public function prepareDicts($defaults, $custom = [])
	{
		$dictionaries = '';
		
		if(!is_array($defaults)) $defaults = array($defaults);
		foreach($defaults as $k=>$v)
			$defaults[$k] = 'defaults*'.$v;
		
		if(!empty($custom) && is_array($custom)) 
			$dictionaries .= implode(',',$custom);
		
		$dictionaries .= implode(',',$defaults);
		
		return $dictionaries;
	}

} 
