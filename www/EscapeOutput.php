<?php

class EscapeOutput {
	public static function write($str) {
		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}
}
