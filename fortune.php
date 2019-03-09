<?php
/**
 * Author: Jak Wings (https://github.com/jakwings)
 *
 * Description: It is just a PHP script inspired by http://www.aasted.org/quote/
 */
class Fortune {

    const MAX_LENGTH = 2147483647;  // 2^31 - 1

    public function QuoteFromDir($dir) {
        $files = array_filter(glob($dir . '/*', GLOB_NOSORT) ?: array(), function ($file) {
            return is_file($file) and substr(strrchr($file, '.'), 1) !== 'dat';
        });
        if (empty($files)) {
            return;
        }
        $amount = 0;
        $amounts = array();
        foreach ($files as $index => $file) {
            if (!file_exists($file . '.dat')) {
                $this->CreateIndexFile($file);
            }
            $amount += $this->GetNumberOfQuotes($file);
            $amounts[$index] = $amount;
        }
        if ($amount < 1) {
            return;
        }
        $n = mt_rand(1, $amount);
        $index = 0;
        while ($amounts[$index] < $n)  {
            $index += 1;
        }
        return $this->GetRandomQuote($files[$index]);
    }

    public function GetNumberOfQuotes($file) {
        if (($fh = fopen($file . '.dat', 'rb')) === FALSE) {
            return 'FORTUNE: Failed to open index file.';
        }
        flock($fh, LOCK_SH);
        fseek($fh, 4, SEEK_SET);
        $number = $this->_ReadUint32($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        return $number;
    }

    public function GetExactQuote($file, $index) {
        $index_file = $file . '.dat';
        if (($fh = fopen($index_file, 'rb')) === FALSE) {
            return 'FORTUNE: Failed to open index file.';
        }
        flock($fh, LOCK_SH);
        fseek($fh, 4 * (6 + $index), SEEK_SET);
        $physical_index = $this->_ReadUint32($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        if (($fh = fopen($file, 'rb')) === FALSE) {
            return 'FORTUNE: Failed to open source file.';
        }
        $quote = $this->_GetQuote($fh, $physical_index);
        fclose($fh);
        return $quote;
    }

    public function GetRandomQuote($file) {
        $number = $this->GetNumberOfQuotes($file);
        if ($number < 1) {
            return;
        }
        $index = mt_rand(1, $number - 1);
        return $this->GetExactQuote($file, $index);
    }

    public function CreateIndexFile($file) {
        // Generate indices.
        if (($fh = fopen($file, 'r')) === FALSE) {
            throw new Exception('FORTUNE: Failed to load source file.');
        }
        $length = 0;
        $longest = 0;
        $shortest = self::MAX_LENGTH;
        $indices = array();
        $last_index = 0;
        while (!feof($fh)) {
            $line = $this->_ReadLine($fh, self::MAX_LENGTH, "\n");
            if (($line === "%\n") or ($line === "%\r\n") or ($line === '%') or feof($fh)) {
                if (feof($fh) and $line) {
                    $length += strlen($line);
                }
                if (($length > 0) and ($length <= self::MAX_LENGTH)) {
                    $indices[] = $last_index;
                    if ($length > $longest) {
                        $longest = $length;
                    }
                    if ($length < $shortest) {
                        $shortest = $length;
                    }
                }
                $last_index = ftell($fh);
                $length = 0;
            } else {
                $length += strlen($line);
            }
        }
        fclose($fh);

        // Write header.
        if (($fh = fopen($file . '.dat', 'wb')) === FALSE) {
            throw new Exception('FORTUNE: Failed to write index file.');
        }
        flock($fh, LOCK_EX);
        $number = count($indices);
        if ($number === 0) {
            $longest = 0;
            $shortest = 0;
        }
        $this->_WriteUint32($fh, 2);                // version number (unofficial)
        $this->_WriteUint32($fh, $number);          // number of quotes
        $this->_WriteUint32($fh, $longest);         // length of longest quote
        $this->_WriteUint32($fh, $shortest);        // length of shortest quote
        $this->_WriteUint32($fh, 0);                // flags (reserved)
        $this->_WriteUint32($fh, ord('%') << 24);   // delimiter
        for ($i = 0; $i < $number; $i++) {
            $this->_WriteUint32($fh, $indices[$i]);
        }
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    private function _GetQuote($fh, $index) {
        fseek($fh, $index, SEEK_SET);
        $line = '';
        $quote = '';
        do {
            $quote .= $line;
            $line = $this->_ReadLine($fh, self::MAX_LENGTH, "\n");
        } while ($line and ($line !== "%\n") and ($line !== "%\r\n") and ($line !== '%'));
        return $quote;
    }

    private function _WriteUint32($fh, $n) {
        fwrite($fh, chr(($n >> 24) & 0xFF));
        fwrite($fh, chr(($n >> 16) & 0xFF));
        fwrite($fh, chr(($n >> 8) & 0xFF));
        fwrite($fh, chr($n & 0xFF));
    }

    private function _ReadUint32($fh) {
        $bytes = fread($fh, 4);
        $n = isset($bytes[3]) ? ord($bytes[3]) : 0;
        $n += isset($bytes[2]) ? (ord($bytes[2]) << 8) : 0;
        $n += isset($bytes[1]) ? (ord($bytes[1]) << 16) : 0;
        $n += isset($bytes[0]) ? (ord($bytes[0]) << 24) : 0;
        return $n;
    }

    private function _ReadLine($fh, $length, $ending) {
        $pos = ftell($fh);
        $eol_len = strlen($ending);
        $len = 0;
        $line = '';
        while ($str = fread($fh, 512)) {
            $line .= $str;
            $offset = ($len > $eol_len) ? ($len - $eol_len) : $len;
            $index = strpos($line, $ending, $offset);
            if ($index !== FALSE) {
                $len = $index + $eol_len;
                $line = substr($line, 0, $len);
                break;
            }
            $len += strlen($str);
            if ($len > $length) {
                break;
            }
        }
        if ($len > 0) {
            if ($len > $length) {
                fseek($fh, $pos + $length);
                return substr($line, 0, $length);
            }
            fseek($fh, $pos + $len);
            return $line;
        }
        return FALSE;
    }
}
?>
