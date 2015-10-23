<?php

	/**
	 * Utilities class for Regular Expression replacement.
	 */
	class RegExUtilities {

		/**
		 * Replaces the underscores from a string with spaces.
		 */
		public static function replace_underscores($string) {

			return preg_replace("(_)", " ", $string);
		}

		/**
		 * Replaces the spaces from a string with underscores.
		 */
		public static function replace_spaces($string) {

			return preg_replace("/\s+/", "_", $string);
		}

		/**
		 * Replaces the backticks with single quotes.
		 */
		public static function replace_backticks($string) {

			return preg_replace("(`)", "'", $string);
		}

		/**
		 * Replaces the single quotes with backtaicks.
		 */
		public static function replace_singlequotes($string) {

			return preg_replace("(')", "`", $string);
		}		

		/**
		 * Replaces the single quotes with backtaicks.
		 */
		public static function replace_leftbrackets($string) {

			return preg_replace("/\[\[(\w+)\[\]/", "", $string);
		}
	}

	class DataManager {
		
	}

?>