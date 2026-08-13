<?php
/**
 * LZString PHP implementation (URI-safe variant) — port of pieroxy/lz-string
 * https://github.com/pieroxy/lz-string
 * Used to decompress the `response` payload of BPJS Apotek API.
 */
class LZString {
    private static $keyStrUriSafe = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-$";

    public static function decompressFromEncodedURIComponent($input) {
        if ($input === null) return "";
        if ($input === "") return null;
        $input = str_replace(' ', '+', $input);
        return self::_decompress(strlen($input), 32, function($index) use ($input) {
            return self::getBaseValue(self::$keyStrUriSafe, $input[$index]);
        });
    }

    private static function getBaseValue($alphabet, $character) {
        $pos = strpos($alphabet, $character);
        return $pos === false ? -1 : $pos;
    }

    /**
     * String.fromCharCode(bits) equivalent — always returns UTF-8 string.
     */
    private static function charFromCode($bits) {
        $bin = pack('n', $bits & 0xFFFF);
        return mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE');
    }

    private static function firstChar($str) {
        $bin = mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
        if (strlen($bin) < 2) return '';
        return self::charFromCode(ord($bin[0]) << 8 | ord($bin[1]));
    }

    private static function readBits(&$dataVal, &$dataPosition, &$dataIndex, $length, $resetValue, $getNextValue, $numBits) {
        $bits = 0;
        $maxpower = 1 << $numBits;
        $power = 1;
        while ($power != $maxpower) {
            $resb = $dataVal & $dataPosition;
            $dataPosition >>= 1;
            if ($dataPosition == 0) {
                $dataPosition = $resetValue;
                $dataVal = $getNextValue($dataIndex++);
            }
            $bits |= ($resb > 0 ? 1 : 0) * $power;
            $power <<= 1;
        }
        return $bits;
    }

    private static function _decompress($length, $resetValue, $getNextValue) {
        $dictionary = [];
        $enlargeIn = 4;
        $dictSize = 4;
        $numBits = 3;
        $entry = "";
        $w = "";
        $c = "";
        $result = [];

        $dataVal = $getNextValue(0);
        $dataPosition = $resetValue;
        $dataIndex = 1;

        for ($i = 0; $i < 3; $i++) {
            $dictionary[$i] = $i;
        }

        $bits = self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, 2);

        switch ($bits) {
            case 0:
                $c = self::charFromCode(self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, 8));
                break;
            case 1:
                $c = self::charFromCode(self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, 16));
                break;
            case 2:
                return "";
        }

        $dictionary[3] = $c;
        $w = $c;
        $result[] = $c;

        while (true) {
            if ($dataIndex > $length) {
                return "";
            }

            $c = self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, $numBits);

            switch ($c) {
                case 0:
                    $c = self::charFromCode(self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, 8));
                    $dictionary[$dictSize++] = $c;
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 1:
                    $c = self::charFromCode(self::readBits($dataVal, $dataPosition, $dataIndex, $length, $resetValue, $getNextValue, 16));
                    $dictionary[$dictSize++] = $c;
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 2:
                    return implode('', $result);
            }

            if ($enlargeIn == 0) {
                $enlargeIn = 1 << $numBits;
                $numBits++;
            }

            if (isset($dictionary[$c])) {
                $entry = $dictionary[$c];
            } else {
                if ($c === $dictSize) {
                    $entry = $w . self::firstChar($w);
                } else {
                    return "";
                }
            }
            $result[] = $entry;

            $dictionary[$dictSize++] = $w . self::firstChar($entry);
            $enlargeIn--;

            $w = $entry;

            if ($enlargeIn == 0) {
                $enlargeIn = 1 << $numBits;
                $numBits++;
            }
        }
    }
}
?>
