<?php
	/*
	 *	Copyright (C) 2002-2004
	 *	@author chenxi
	 *	@version $Id: StringBuffer.class.php,v 0.1 2004/11/04 09:39:45
	 */

	require_once ('Object.class.php');

	class StringBuffer extends Object {
		var $string = '';

		function StringBuffer($string='') {
			$this->string = $string;
		}

		function append($string) {
			$this->string .= $string;
		}

		function appendEnter() {
			$this->string .= '<br/>';
		}

		function toString() {
			return $this->string;
		}
	}
?>