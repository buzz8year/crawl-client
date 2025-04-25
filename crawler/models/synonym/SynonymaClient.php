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
	
	public function __construct($login, $password){
		$this->login = $login;
		$this->hash = md5($login.$password);
	}
	
	/*
	* Создание запроса к Synonyma API	
	* array $params - массив параметров, передаваемых к серверу
	*/
	private function request($params){
		$params['login'] = $this->login;
		$params['hash'] = $this->hash;
		
		try {
			if(!$this->curl = curl_init()){
				throw new Exception("Произошла ошибка при инициализации библиотеки Curl");
			}
			curl_setopt($this->curl, CURLOPT_URL,$this->url);
			curl_setopt($this->curl, CURLOPT_POST,1);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION,1); 
			curl_setopt($this->curl, CURLOPT_HEADER,0);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER,1);  
			try {
				if(!$data = curl_exec($this->curl)){
					throw new Exception("Не могу получить данные по url: ".$this->url);
				}
			} catch (Exception $e) {
				echo $e->getMessage();
			}	
			curl_close($this->curl);
			return $data;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	/*
	* Декодирование ответа от сервера. Ответ приходит в формате json	
	* string $json - строка ответа в формате json
	*/
	private function decode($json){
		try {
			if(!$obj = json_decode($json))
				throw new Exception("Полученные данные имеют не верный формат.");
			return $obj;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return false;
	}
	
	/*
	* Синонимизирвание текста	
	* string $text - текст, котоырй необходимо пропустить через синонимайзер
	* string $dictionaries - уникальные название словарей, перечисленные через запятую (,). Дефолтные словари должны иметь приставку "defaults*", например: defaults*default_ru
	*/
	public function synonymize($text, $dictionaries){
		$params['action'] = 'synonymize';
		$params['text'] = $text;
		$params['dicts'] = $dictionaries;
		
		return $this->decode($this->request($params));
	}
	
	/*
	* Типограф текста
	* string $text - текст, котоырй необходимо типографить
	*/
	public function typograph($text){
		$params['action'] = 'typograph';
		$params['text'] = $text;
		
		return $this->decode($this->request($params));
	}
	
	/*
	* Размножитель текста
	* string $text - шаблон текста для размножения
	* int $count - максимальное количество вариантов 
	* string $encoding - кодировка результата, может быть одной из: UTF-8, СЗ1251, KOI8-R, ISO-8859-5
	* bool $twins - true - удаление дубликатов, false - дубликаты не проверяются
	* bool $typograph - true - типографить результат, false - оставить как есть
	*/
	public function breeder($text, $count, $encoding, $twins, $typograph){
		$params['action'] = 'breeder';
		$params['count'] = $count;
		$params['encoding'] = $encoding;
		$params['twins'] = $twins;
		$params['typograph'] = $typograph;
				
		return $this->decode($this->request($params));
	}
	
	/*
	* Скачивание последнего файла, созданного размножителем
	*/
	public function download(){
		$params['action'] = 'download';
		return $this->decode($this->request($params));	
	}
	
	/*
	* Функция возвращает статистику по абонементу
	*/
	public function statistic(){
		$params['action'] = 'statistic';
		return $this->decode($this->request($params));
	}
	
	/*
	* Функция возвращает список словарей
	*/	
	public function dictionaries(){
		$params['action'] = 'dictionaries';
		
		return $this->decode($this->request($params));
	}
	
	/*
	* Функция возвращает информацию по абонементу
	*/		
	public function subscription(){
		$params['action'] = 'subscription';
		return $this->decode($this->request($params));
	}
	
	/*
	* Функция готовит строку из списка словарей
	* array $defaults - массив названий дефолтных словарей
	* array $custom - массив названий пользовательских словарей
	*/	
	public function prepareDicts($defaults, $custom=array()){
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

?>