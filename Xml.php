<?php
/**
 * Basic Xml Decoder. No support for attributes. Takes the json approach.
 * Uses <item> tag for numerically indexed objects
 *
 * @author kvz
 */
class Xml {
    /**
     * Decode xml string to multidimensional array
     *
     * @param string $xmldata
     *
     * @return array
     */
    public function decode ($xmldata) {
        if (!($obj = simplexml_load_string($xmldata))) {
            return false;
        }

        $array      = $this->_toArray($obj);
        $unitemized = $this->_unitemize($array);

        return $unitemized;
    }

    /**
     * Strips out <item> tags and nests children in sensible places
     *
     * @param array $array
     *
     * @return array
     */
    protected function _unitemize ($array) {
        if (!is_array($array)) {
            return $array;
        }
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $array[$key] = $this->_unitemize($val);
            }

            if ($key === 'item' && is_array($val)) {
                if ($this->_numeric($val)) {
                    foreach ($val as $i => $v) {
                        $array[] = $v;
                    }
                } else {
                    $array[] = $val;
                }
                unset($array['item']);
            }
        }
        return $array;
    }

    /**
     * SimpleXML Object to Array
     *
     * @param object $object
     * @param array  $array
     */
    protected function _toArray ($object) {
       $array = array();
       foreach ((array) $object as $key => $var) {
           if (is_object($var)) {
               if (count((array) $var) == 0) {
                   $array[$key] = null;
               } else {
                   $array[$key] = $this->_toArray($var);
               }
           } else {
               $array[$key] = $var;
           }
       }
       return $array;
    }

	/**
	 * Determines is an array is numerically indexed
	 *
	 * @param array $array
	 *
	 * @return boolean
	 */
	protected function _numeric ($array = array()) {
		if (empty($array)) {
			return null;
		}
		$keys = array_keys($array);
		foreach ($keys as $key) {
			if (!is_numeric($key)) {
				return false;
			}
		}
		return true;
	}
}