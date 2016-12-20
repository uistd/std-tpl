<?php
namespace ffan\php\tpl;

use ffan\php\utils\Config as FFanConfig;
use ffan\php\utils\Env as FFanEnv;

/**
 * Class Tpl 兼容smarty语法的模板引擎
 * @package ffan\php\tpl
 */
class Tpl
{
    /**
     * @var bool 是否已经初始化
     */
    private static $is_init = false;

    /**
     * @var string 模板的根目录
     */
    private static $root_path;

    /**
     * @var string 临时目录
     */
    private static $compile_dir;

    /**
     * @var string 后缀名
     */
    private static $suffix = 'tpl';

    /**
     * 运行一个模板文件
     * @param string $tpl_name
     * @param null|array $model
     */
    public static function run($tpl_name, $model = null)
    {
        if (!self::$is_init) {
            self::init();
        }
        $func_name = self::tplMethodName($tpl_name);
        $compile_file = self::tplCompileName($func_name);
        //如果不存在，尝试编译 或者 已经过期
        if (!is_file($compile_file) || !self::isCacheValid($compile_file, $func_name, $tpl_name)){
            
        }
        return $func_name($model);
    }

    /**
     * 判断缓存文件是否有效
     * @param string $compile_file 缓存文件路径
     * @param string $func_name 方法名
     * @param string $file_name 模板文件
     * @return bool
     * @throws TplException
     */
    private static function isCacheValid($compile_file, $func_name, $file_name)
    {
        $tpl_file = self::tplFileName($file_name);
        if (!is_file($tpl_file)) {
            throw new TplException('No tpl '. $func_name .' found');
        }
        $last_time = filemtime($tpl_file);
        $func_name = $func_name .'_'. $last_time;
        return function_exists($func_name);
    }

    /**
     * 是否存在某个模板
     * @param string $tpl_name
     * @return bool
     */
    private static function hasTpl($tpl_name)
    {
        return is_file(self::tplFileName($tpl_name));
    }

    /**
     * 返回模板的方法名
     * @param string $tpl_name
     * @return string
     */
    private static function tplMethodName($tpl_name)
    {
        return str_replace(DIRECTORY_SEPARATOR, '_', $tpl_name);
    }

    /**
     * 返回tpl文件的文件名
     * @param string $tpl_name
     * @return string
     */
    private static function tplFileName($tpl_name)
    {
        return self::$root_path . $tpl_name . '.' . self::$suffix;
    }

    /**
     * 返回编译好的文件名
     * @param string $method_name 方法名
     * @return string
     */
    private static function tplCompileName($method_name){
        return self::$compile_dir . $method_name .'.php';
    }

    /**
     * 初始化
     */
    private static function init()
    {
        self::$is_init = true;
        $conf_arr = FFanConfig::get('ffan-tpl');
        $base_path = defined('FFAN_BASE') ? FFAN_BASE : str_replace('vendor/ffan/php/tpl', '', __DIR__);
        $base_dir = isset($conf_arr['tpl_dir']) ? trim($conf_arr['tpl_dir']) : 'views';
        self::$root_path = self::fix_path($base_path . $base_dir);
        $compile_dir = isset($conf_arr['compile_dir']) ? trim($conf_arr['compile_dir']) : 'tpl';
        self::$compile_dir = self::fix_path($compile_dir);
        if (!empty($conf_arr['suffix'])) {
            self::$suffix = (string)trim($conf_arr['suffix']);
        }
    }

    /**
     * 修正路径
     * @param string $path 路径名
     * @return string
     */
    private static function fix_path($path)
    {
        //如果第一个字符不是绝对路径
        if (DIRECTORY_SEPARATOR !== $path[0]) {
            $path = FFanEnv::getRuntimePath() . $path;
        }
        if (DIRECTORY_SEPARATOR !== $path[strlen($path) - 1]) {
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }
}