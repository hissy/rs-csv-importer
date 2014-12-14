<?php

/**
 * A helper class for get data from CSV files.
 *
 * @package Really Simple CSV Importer
 */
class RS_CSV_Helper {

	const DELIMITER = ",";

	// File utility functions
	public function fopen($filename, $mode='r') {
		return fopen($filename, $mode);
	}

	public function fgetcsv($handle, $length = 0) {
		return fgetcsv($handle, $length, self::DELIMITER);
	}

	public function fclose($fp) {
		return fclose($fp);
 	}

	public function parse_columns(&$obj, $array) {
		if (!is_array($array) || count($array) == 0)
			return false;

		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 == strncmp($array[0], $bom, 3)) {
			$array[0] = substr($array[0], 3);
		}

		$keys = array_keys($array);
		$values = array_values($array);

		$obj->column_indexes = array_combine($values, $keys);
		$obj->column_keys = array_combine($keys, $values);
	}

	public function get_data($obj, &$array, $key) {
		if (!isset($obj->column_indexes) || !is_array($array) || count($array) == 0)
			return false;

		if (isset($obj->column_indexes[$key])) {
			$index = $obj->column_indexes[$key];
			if (isset($array[$index]) && !empty($array[$index])) {
				$value = $array[$index];
				unset($array[$index]);
				return $value;
			} elseif (isset($array[$index])) {
				unset($array[$index]);
			}
		}

		return false;
	}

}