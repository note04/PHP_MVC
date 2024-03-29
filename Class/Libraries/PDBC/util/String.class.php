<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: String.class.php,v 0.1 2004/11/18 11:48:35
	 */

	require_once ('Object.class.php');

	class String extends Object {
		var $orinstring	= NULL;
		var $chars		= NULL;
		var $count		= 0;
		var $offset		= 0;

		function String($string=NULL) {
			$this->orinstring =& $string;
			$this->chars =& $string;
			$this->count = strlen($this->orinstring);
			$this->offset = 0;
		}

		function __desturct() {
			$this->chars   = NULL;
			$this->orinstring = NULL;
			$this->count  = 0;
			$this->offset = 0;
		}

		function charAt($index) {
			if (($index < 0) || ($index > $this->count))
				trigger_error();
			return $this->chars[$index + $this->offset];
		}

		function compareTo($anotherString) {}

		function compareToIgnoreCase($anotherString) {}

		function concat($str) {
			$this->orinstring .= $str;
		}

		function contains($chars) {
			return $this->indexOf($chars) > -1;
		}

		/**
		 *	@param	$index
		 *	@param	$str String Object OR String reference
		 *	@return	index or -1
		 */
		function indexOf($str=false, $fromIndex=false) {
		}

		function length() {
			return $this->count;
		}

		function matches($regex) {}

		function replace($oldChar, $newChar) {
			if (is_object($oldChar) && is_a($newChar, 'String')) {
				if ($oldChar->toString() != $newChar->toString()) {
					
				}
				return $this->toString();
			} else if (is_string($oldChar) && is_string($newChar)) {
				if ($oldChar != $newChar) {
					$len = $this->count;
					$nl = strlen($newChar);
				}
				return $this->orinstring;
			} else {}
		}

		function split($char) {
			return explode($char, $this->orinstring);
		}

		function subString($offset) {}

		function toCharArray() {}

		function toLowerCase() {
			$this->chars = strtolower($this->orinstring);
		}
		
		function toString() {
			return $this->orinstring;
		}

		function toUpperCase() {
			$this->chars = strtoupper($this->orinstring);
		}

		function trim() {
			return trim($this->orinstring);
		}

		function valueof() {}

		function firstNonWsChar($string=null) {
			if (null === $string || '' == $string)
				return false;

			for ($i = 0; $i < strlen($string); $i++) {
				if (!self::isWhitespace($string[$i]))
					return strtoupper($string[$i]);
			}

			return false;
		}

		function isWhitespace($char) {
			$SPACE_CHAR = array(' ', '��');

			if (in_array($char, $SPACE_CHAR))
				return true;
			return false;
		}

		function startsWith($prefix, $toffset=0) {
			if (is_object($prefix) && is_a($prefix, 'String')) {
				$ta = $this->chars;
				$to = $this->offset + $toffset;
				$pa = $prefix->chars;
				$po = $prefix->offset + $toffset;
			} else if (is_string($prefix)) {
				$ta = $this->chars;
				$to = $this->offset + $toffset;
				$pa = $prefix;
				$po = 0;
				$pc = strlen($prefix);
			} else {
				return false;
			}

			if ($toffset < 0 || ($toffset > ($this->count - $pc)))
				return false;

			while (--$pc >= 0) {
				if ($ta[$to++] != $pa[$po++])
					return false;
			}
			return true;
		}

		function endsWith($suffix) {
			if ((is_object($suffix)) && (is_a($suffix, 'String')))
				return String::startsWith($suffix, ($this->count - $suffix->count));
			else if (is_string($suffix))
				return String::startsWith($suffix, ($this->count - strlen($suffix)));
			else
				return false;
		}
	}
?>