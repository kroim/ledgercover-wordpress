<?php

if (! (defined('ABSPATH') || defined('MCDATAPATH')) ) exit;
if (!class_exists('BVWPRequest')) :
	
class BVWPRequest {
  private $fileNames;
  private $files;
  private $headers;
  private $host;
  private $ip;
  private $method;
  private $path;
  private $queryString;
  private $timestamp;
  private $uri;
	private $body;
	private $cookies;
	private $respcode;
	private $status;
	private $rulesInfo;
	private $reqInfo;

	#status
 	const ALLOWED  = 1;
	const BLOCKED  = 2;
	const BYPASSED = 3;

	#category
	const BLACKLISTED = 1;
	const WHITELISTED = 2;
	const NORMAL      = 3;

	public function __construct($ip) {
		$fileNames = array();
		$headers = array();
		$host = '';
		$method = '';
		$path = '';
		$this->ip = $ip;
		$this->rulesInfo = array();
		$this->reqInfo = array();
		$this->setRespCode(0);
		$this->setCategory(BVWPRequest::NORMAL);
		$this->setStatus(BVWpRequest::ALLOWED);
		$this->setTimestamp(time());
		$this->setQueryString($_GET);
		$this->setCookies($_COOKIE);
		$this->setBody($_POST);
		$this->setFiles($_FILES);
		if (!empty($_FILES)) {
			foreach ($_FILES as $input => $file) {
				$fileNames[$input] = $file['name'];
			}
		}
		$this->setFileNames($fileNames);
		if (is_array($_SERVER)) {
			foreach ($_SERVER as $key => $value) {
				if (strpos($key, 'HTTP_') === 0) {
					$header = substr($key, 5);
					$header = str_replace(array(' ', '_'), array('', ' '), $header);
					$header = ucwords(strtolower($header));
					$header = str_replace(' ', '-', $header);
					$headers[$header] = $value;
				}
			}
			if (array_key_exists('CONTENT_TYPE', $_SERVER)) {
				$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (array_key_exists('CONTENT_LENGTH', $_SERVER)) {
				$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
			}
			if (array_key_exists('REFERER', $_SERVER)) {
				$headers['Referer'] = $_SERVER['REFERER'];
			}
			if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
				$headers['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
			}

			if (array_key_exists('Host', $headers)) {
				$host = $headers['Host'];
			} else if (array_key_exists('SERVER_NAME', $_SERVER)) {
				$host = $_SERVER['SERVER_NAME'];
			}

			$method = array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'GET';
			$uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : '';
			$_uri = parse_url($uri);
			$path = (is_array($_uri) && array_key_exists('path', $_uri)) ? $_uri['path']  : $uri;
		}
		$this->setHeaders($headers);
		$this->setHost($host);
		$this->setMethod($method);
		$this->setUri($uri);
		$this->setPath($path);
	}

	public function setStatus($status) {
		$this->status = $status;
	}

	public function setCategory($category) {
		$this->category = $category;
	}

	public function setBody($body) {
		$this->body = $body;
	}

	public function setCookies($cookies) {
		$this->cookies = $cookies;
	}

	public function setFileNames($fileNames) {
		$this->fileNames = $fileNames;
	}

	public function setFiles($files) {
		$this->files = $files;
	}
	
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	public function setRespCode($code) {
		$this->respcode = $code;
	}

	public function getRespCode() {
		return $this->respcode;
	}

	public function setHost($host) {
		$this->host = $host;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function setQueryString($queryString) {
		$this->queryString = $queryString;
	}

	public function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}

	public function setUri($uri) {
		$this->uri = $uri;
	}

	public function updateRulesInfo($key, $value) {
		$this->rulesInfo[$key] = $value;
	}
	
	public function getRulesInfo() {
		return $this->rulesInfo;
	}

	public function updateReqInfo($info) {
		if (is_array($info)) {
			$this->reqInfo = $this->reqInfo + $info;
		}
	}

	public function getReqInfo() {
		return $this->reqInfo;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getCategory() {
		return $this->category;
	}

	public function getDataToLog() {
		$referer = $this->getHeader('Referer') ? $this->getHeader('Referer') : '';
		$user_agent = $this->getHeader('User-Agent') ? $this->getHeader('User-Agent') : '';
		$rules_info = serialize($this->getRulesInfo());
		$req_info = serialize($this->getReqInfo());
		if (strlen($req_info) > 16000) {
			$req_info = serialize(array("keys" => array_keys($this->getReqInfo())));
			if (strlen($req_info) > 16000) {
				$req_info = serialize(array("bv_over_size" => true));
			}
		}
		$data = array(
			"path"         => $this->getPath(),
			"filenames"    => serialize($this->getFileNames()),
			"host"         => $this->getHost(),
			"time"         => $this->getTimeStamp(),
			"ip"           => $this->getIP(),
			"method"       => $this->getMethod(),
			"query_string" => $req_info,
			"user_agent"   => $user_agent,
			"resp_code"    => $this->getRespCode(),
			"referer"      => $referer,
			"status"       => $this->getStatus(),
			"category"     => $this->getCategory(),
			"rules_info"   => $rules_info
		);
		return $data;
	}

	protected function getKeyVal($array, $key) {
		if (is_array($array)) {
			if (is_array($key)) {
				$_key = array_shift($key);
				if (array_key_exists($_key, $array)) {
					if (count($key) > 0) {
						return $this->getKeyVal($array[$_key], $key);
					} else {
						return $array[$_key];
					}
				}
			} else {
				return array_key_exists($key, $array) ? $array[$key] : null;
			}
		}
		return null;
	}
	
	public function getBody() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->getKeyVal($this->body, $args);
		}
		return $this->body;
	}
	
	public function getCookies() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->getKeyVal($this->cookies, $args);
		}
		return $this->cookies;
	}
	
	public function getQueryString() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->getKeyVal($this->queryString, $args);
		}
		return $this->queryString;
	}
	
	public function getHeader($key) {
		if (array_key_exists($key, $this->headers)) {
			return $this->headers[$key];
		}
		return null;
	}
	
	public function getFiles() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->getKeyVal($this->files, $args);
		}
		return $this->files;
	}

	public function getFileNames() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->getKeyVal($this->fileNames, $args);
		}
		return $this->fileNames;
	}

	public function getHost() {
		return $this->host;
	}

	public function getURI() {
		return $this->uri;
	}

	public function getPath() {
		return $this->path;
	}

	public function getIP() {
		return $this->ip;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}
}
endif;
