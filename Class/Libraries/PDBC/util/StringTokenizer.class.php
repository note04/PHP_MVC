<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: StringTokenizer.class.php,v 0.1 2004/11/03 10:00:18
	 */

	require_once ('Object.class.php');

	 class StringTokenizer extends Object {
		/**
		 *	token位移指针
		 */
		var $pos = 0;

		/**
		 *	token数组
		 */
		var $tokens = array();

		function StringTokenizer($string, $separator) {
			if (!is_string($string))
				trigger_error('StringTokenizer->__construct: param is not a string');
			$this->tokens = explode($separator, $string);
		}
		
		function __destruct() {}

		/**
		 *	@return 返回token的个数
		 */
		function countTokens() {
			return sizeof($this->tokens);
		}

		function hasMoreElements() {}

		/**
		 *	@return 如果有元素返回ture，否则返回false
		 */
		function hasMoreTokens() {
			return ($this->pos < sizeof($this->tokens));
		}

		function nextElement() {}
		
		/**
		 *	@return 返回下一个token
		 */
		function nextToken() {
			if ($this->pos > sizeof($this->tokens))
				trigger_error('token越界');

			return $this->tokens[$this->pos++];
		}

		//function nextToken($delim) {}
	 }
?>