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
     * @var string 模板的根目录
     */
    private $root_path;

    /**
     * @var string 临时目录
     */
    private $compile_dir;

    /**
     * @var string 后缀名
     */
    private $tpl_suffix = 'tpl';

    /**
     * @var string 左标签
     */
    private $prefix_tag = '{{';

    /**
     * @var string 右标签
     */
    private $suffix_tag = '}}';

    /**
     * @var array 解析变量
     */
    private $tpl_data;

    /**
     * @var bool 是否缓存编译好的结果
     */
    private $cache_result = false;

    /**
     * Tpl constructor.
     */
    public function __construct()
    {
        $conf_arr = FFanConfig::get('ffan-tpl');
        $base_path = defined('FFAN_BASE') ? FFAN_BASE : str_replace('vendor/ffan/php/tpl', '', __DIR__);
        $base_dir = isset($conf_arr['tpl_dir']) ? trim($conf_arr['tpl_dir']) : 'views';
        $this->root_path = $this->fixPath($this->fixPath($base_path) . $base_dir);
        //文件后缀名
        if (isset($conf_arr['tpl_suffix'])) {
            $this->tpl_suffix = (string)trim($conf_arr['tpl_suffix']);
        }
        //标签前缀
        if (isset($conf_arr['prefix_tag'])) {
            $this->prefix_tag = (string)trim($conf_arr['prefix_tag']);
        }
        //标签后缀
        if (isset($conf_arr['suffix_tag'])) {
            $this->suffix_tag = (string)trim($conf_arr['suffix_tag']);
        }
        //缓存结果
        if (!empty($conf_arr['cache_result'])) {
            $this->cache_result = true;
            $compile_dir = isset($conf_arr['compile_dir']) ? trim($conf_arr['compile_dir']) : 'tpl';
            $this->compile_dir = $this->fixPath($compile_dir);
        }
    }

    /**
     * 运行一个模板文件
     * @param string $tpl_name 模板名称
     * @param null|array $model
     * @return null
     * @throws TplException
     */
    public function display($tpl_name, $model = null)
    {
        $this->load($tpl_name, $model, false);
    }

    /**
     * 获取模板运行内容
     * @param string $tpl_name 模板名称
     * @param null|array $model
     * @return string
     * @throws TplException
     */
    public function fetch($tpl_name, $model = null)
    {
        return $this->load($tpl_name, $model, true);
    }

    /**
     * 获取模板运行内容
     * @param string $tpl_name 模板名称
     * @param null|array $model
     * @param bool $is_echo 模板结果是否打印出来
     * @return string
     * @throws TplException
     */
    private function load($tpl_name, $model, $is_echo)
    {
        if (null !== $model && null === $this->tpl_data) {
            $this->tpl_data = $model;
        }
        $func_name = $this->tplMethodName($tpl_name);
        $tpl_file = $this->tplFileName($tpl_name);
        if (!is_file($tpl_file)) {
            throw new TplException('No tpl ' . $tpl_file . ' found');
        }
        $last_time = filemtime($tpl_file);
        $func_name .= '_' . $last_time;
        $compile_file = $this->tplCompileName($func_name);
        //如果存在，并且未过期
        if (is_file($compile_file) && $this->isCacheValid($compile_file, $func_name)) {
            $this->compileTpl($tpl_file, $func_name, $compile_file);
        }
        return null;
    }

    /**
     * 编译模板
     * @param string $tpl_file 模板文件
     * @param string $func_name 函数名
     * @param string $compile_file 编译之后的文件名
     * @throws TplException
     */
    public function compileTpl($tpl_file, $func_name, $compile_file)
    {
        $compile = new Compiler($this->prefix_tag, $this->suffix_tag);
        $content = $compile->make($tpl_file, $func_name);
        $this->saveCacheFile($content, $compile_file)
        /** @noinspection PhpIncludeInspection */
        require_once $compile;
    }

    /**
     * 保存缓存文件
     * @param string $content 内容
     * @param string $file 文件
     * @return int
     * @throws TplException
     */
    private function saveCacheFile($content, $file)
    {
        if (!is_dir($this->compile_dir) && !mkdir($this->compile_dir, 0755, true)) {
            throw new TplException('目录:' . $this->compile_dir . ' 不存在');
        }
        //不可写
        if (!is_writable($this->compile_dir)) {
            throw new TplException('目录：' . $this->compile_dir . ' 没有写入权限');
        }
        //如果写入失败
        if (false === file_put_contents($file, $content)) {
            throw new TplException('无法生成缓存文件：' . $file);
        }
    }

    /**
     * 判断缓存文件是否有效
     * @param string $compile_file 缓存文件路径
     * @param string $func_name 方法名
     * @return bool
     * @throws TplException
     */
    private function isCacheValid($compile_file, $func_name)
    {
        return false;
        /** @noinspection PhpIncludeInspection */
        require_once $compile_file;
        return function_exists($func_name);
    }

    /**
     * 返回模板的方法名
     * @param string $tpl_name
     * @return string
     * @throws TplException
     */
    private function tplMethodName($tpl_name)
    {
        $re_str = str_replace(DIRECTORY_SEPARATOR, '_D_', $tpl_name);
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $re_str)) {
            throw new TplException('模板名错误，只支持a-zA-Z0-9(首位不能是数字)和下划线');
        }
        return $re_str;
    }

    /**
     * 返回tpl文件的文件名
     * @param string $tpl_name
     * @return string
     */
    private function tplFileName($tpl_name)
    {
        return $this->root_path . $tpl_name . '.' . $this->tpl_suffix;
    }

    /**
     * 返回编译好的文件名
     * @param string $method_name 方法名
     * @return string
     */
    private function tplCompileName($method_name)
    {
        return $this->compile_dir . $method_name . '.php';
    }

    /**
     * 修正路径
     * @param string $path 路径名
     * @return string
     */
    private function fixPath($path)
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

    /**
     * 赋值
     * @param string $name 变量名
     * @param mixed $value 值
     */
    public function assign($name, $value)
    {
        $this->tpl_data[$name] = $value;
    }

    /**
     * 一个简单的方法，方便使用
     * @param string $tpl_name 模板名称
     * @param null|array|object $model 值
     */
    public static function run($tpl_name, $model = null)
    {
        $tpl = new self();
        $tpl->display($tpl_name, $model);
    }

    /**
     * 获取模板数据的引用
     * @return null|array
     */
    public function &getData()
    {
        return $this->tpl_data;
    }

    /**
     * 运行一个插件
     * @param string $plugin_name 插件名称
     * @param array $args 参数
     */
    public function loadPlugin($plugin_name, $args)
    {

    }
}
