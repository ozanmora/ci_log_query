<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log_Query
{
    protected $CI;
    protected $_log_path;
    protected $_file_permissions = 0644;
    protected $_threshold = 1;
    protected $_threshold_array = array();
    protected $_date_fmt = 'Y-m-d H:i:s';
    protected $_file_ext;
    protected $_file_prefix = '';
    protected $_enabled = true;
    protected static $func_overload;
    
    public function __construct()
    {
        $this->CI =& get_instance();

        log_message('info', 'Log_Query Hook Initialized');

        $config =& get_config();

        isset(self::$func_overload) or self::$func_overload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));

        $this->_log_path = ($config['log_path'] !== '') ? rtrim($config['log_path'], '/\\') . DIRECTORY_SEPARATOR : APPPATH . 'logs' . DIRECTORY_SEPARATOR;
        $this->_file_ext = (isset($config['log_file_extension']) && $config['log_file_extension'] !== '') ? ltrim($config['log_file_extension'], '.') : 'php';
        $this->_file_prefix = (isset($config['query_log_file_prefix']) && $config['query_log_file_prefix'] !== '') ? rtrim($config['query_log_file_prefix'], '-') . '-' : 'log-';

        file_exists($this->_log_path) or mkdir($this->_log_path, 0755, true);

        if (!is_dir($this->_log_path) or !is_really_writable($this->_log_path)) {
            $this->_enabled = false;
        }

        if (is_numeric($config['log_threshold'])) {
            $this->_threshold = (int)$config['log_threshold'];
        } elseif (is_array($config['log_threshold'])) {
            $this->_threshold = 0;
            $this->_threshold_array = array_flip($config['log_threshold']);
        }

        if (!empty($config['log_date_format'])) {
            $this->_date_fmt = $config['log_date_format'];
        }

        if (!empty($config['log_file_permissions']) && is_int($config['log_file_permissions'])) {
            $this->_file_permissions = $config['log_file_permissions'];
        }
    }

    public function run()
    {
        if ($this->_enabled === false) {
            return false;
        }

        if (2 > $this->_threshold && !isset($this->_threshold_array[2])) {
            return false;
        }

        $filepath = $this->_log_path . $this->_file_prefix . date('Y-m-d') . '.' . $this->_file_ext;
        $message = '';

        $queries = $this->CI->db->queries;
        $times = $this->CI->db->query_times;
        if (!empty($queries)) {
            if (!file_exists($filepath)) {
                $newfile = true;
                // Only add protection to php files
                if ($this->_file_ext === 'php') {
                    $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
                }
            }

            if (!$fp = @fopen($filepath, 'ab')) {
                return false;
            }

            flock($fp, LOCK_EX);

            // Instantiating DateTime with microseconds appended to initial date is needed for proper support of this format
            if (strpos($this->_date_fmt, 'u') !== false) {
                $microtime_full = microtime(true);
                $microtime_short = sprintf("%06d", ($microtime_full - floor($microtime_full)) * 1000000);
                $date = new DateTime(date('Y-m-d H:i:s.' . $microtime_short, $microtime_full));
                $date = $date->format($this->_date_fmt);
            } else {
                $date = date($this->_date_fmt);
            }

            foreach ($queries as $key => $query) {
                $query_time = !empty($times[$key]) ? round($times[$key], 4) : 0.0000;
                $message .= 'QUERY' . ' - ' . $date . ' --> ' . $query_time . ' | ' . str_replace(array("\n", "\n\r", "\r", PHP_EOL), " ", $query) . "\n";
            }

            for ($written = 0, $length = self::strlen($message); $written < $length; $written += $result) {
                if (($result = fwrite($fp, self::substr($message, $written))) === false) {
                    break;
                }
            }

            flock($fp, LOCK_UN);
            fclose($fp);

            if (isset($newfile) && $newfile === true) {
                chmod($filepath, $this->_file_permissions);
            }
            return is_int($result);
        }
    }

    // --------------------------------------------------------------------

    /**
    * Byte-safe strlen()
    *
    * @param	string	$str
    * @return	int
    */
    protected static function strlen($str)
    {
        return (self::$func_overload) ? mb_strlen($str, '8bit') : strlen($str);
    }

    // --------------------------------------------------------------------

    /**
    * Byte-safe substr()
    *
    * @param	string	$str
    * @param	int	$start
    * @param	int	$length
    * @return	string
    */
    protected static function substr($str, $start, $length = NULL)
    {
        if (self::$func_overload) {
            return mb_substr($str, $start, $length, '8bit');
        }
        
        return isset($length) ? substr($str, $start, $length) : substr($str, $start);
    }
}
