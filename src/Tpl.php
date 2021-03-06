<?php
namespace UiStd\Tpl;

use UiStd\Common\Config as UisConfig;
use UiStd\Common\Env as UisEnv;
use UiStd\Common\Env;

/**
 * Class Tpl 兼容smarty语法的模板引擎
 * @package UiStd\Tpl
 */
class Tpl
{
    /**
     * 目录切割符替换字符
     */
    const DIRECTORY_REPLACE = '_D_';

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
     * @var string 管道和自定义插件的目录
     */
    private $extend_dir;

    /**
     * @var array 用户注册的管道
     */
    private static $grep_list;

    /**
     * @var array 用户注册的插件
     */
    private static $plugin_list;

    /**
     * @var array 已经编译过的文件
     */
    private static $compiled_list;

    /**
     * @var Tpl 单例
     */
    private static $singleton;

    /**
     * @var bool 是否缓存结果
     */
    private $cache_result;

    /**
     * Tpl constructor.
     */
    public function __construct()
    {
        $conf_arr = UisConfig::get('uis-tpl');
        $base_path = Env::getRootPath();
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
        $this->cache_result = function_exists('apcu_fetch');
        $compile_dir = isset($conf_arr['compile_dir']) ? trim($conf_arr['compile_dir']) : 'tpl';
        $extend = isset($conf_arr['compile_dir']) ? trim($conf_arr['compile_dir']) : 'plugin';
        $this->compile_dir = $this->fixPath($compile_dir);
        $this->extend_dir = $this->fixPath($extend);
    }

    /**
     * 编译模板
     * @param string $tpl_file 模板文件
     * @param string $func_name 函数名
     * @throws TplException
     */
    public function compileTpl($tpl_file, $func_name)
    {
        if (isset(self::$compiled_list[$tpl_file])) {
            return;
        }
        self::$compiled_list[$tpl_file] = true;
        $need_compile = true;
        if ($this->cache_result) {
            $cache_key = 'tpl_'. $func_name;
            $content = apcu_fetch($cache_key, $is_ok);
            if ($is_ok) {
                $need_compile = false;
            }
        }
        if ($need_compile) {
            $compile = new Compiler($this->prefix_tag, $this->suffix_tag);
            $content = $compile->make($tpl_file, $func_name);
        }
        /** @var string $content */
        eval($content);
        if ($this->cache_result) {
            /** @var string $cache_key */
            apcu_store($cache_key, $content, 7200);
        }
    }

    /**
     * 清除已经编译过的列表
     */
    public static function cleanCompiledList()
    {
        self::$compiled_list = null;
    }

    /**
     * 返回模板的方法名
     * @param string $tpl_name
     * @return string
     * @throws TplException
     */
    public function tplMethodName($tpl_name)
    {
        //目录 / 换成 设定字符
        return str_replace(DIRECTORY_SEPARATOR, self::DIRECTORY_REPLACE, $tpl_name);
    }

    /**
     * 检查模板名称
     * @param string $tpl_name
     * @return string
     * @throws TplException
     */
    public function cleanTplName($tpl_name)
    {
        $suffix = '.' . $this->tpl_suffix;
        //如果有后缀名，去掉
        if ($suffix === substr($tpl_name, -4)) {
            $tpl_name = substr($tpl_name, 0, -4);
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z_0-9\/]*[a-zA-Z_0-9]?$/', $tpl_name)) {
            echo $tpl_name, PHP_EOL;
            throw new TplException('模板名错误，只支持a-zA-Z0-9(首位不能是数字)和下划线', TplException::TPL_NAME_ERROR);
        }
        if (false !== strpos($tpl_name, self::DIRECTORY_REPLACE)) {
            throw new TplException('模板名称中不能包含：' . self::DIRECTORY_REPLACE);
        }
        return $tpl_name;
    }

    /**
     * 返回tpl文件的文件名
     * @param string $tpl_name
     * @return string
     */
    public function tplFileName($tpl_name)
    {
        return $this->root_path . $tpl_name . '.' . $this->tpl_suffix;
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
            $path = UisEnv::getRuntimePath() . $path;
        }
        if (DIRECTORY_SEPARATOR !== $path[strlen($path) - 1]) {
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    /**
     * 一个简单的方法，方便使用
     * @param string $tpl_name 模板名称
     * @param null|array $model 值
     */
    public static function run($tpl_name, $model = null)
    {
        $render = new Render(self::getInstance());
        echo $render->load($tpl_name, $model, true);
    }

    /**
     * 简单的 fetch 方法
     * @param string $tpl_name 模板名称
     * @param null|array $model 值
     * @return string
     */
    public static function get($tpl_name, $model = null)
    {
        $render = new Render(self::getInstance());
        return $render->load($tpl_name, $model);
    }

    /**
     * 运行一个插件
     * @param string $name 插件名称
     * @param array $arguments 参数
     * @return mixed
     * @throws TplException
     */
    public function loadPlugin($name, array $arguments = [])
    {
        $call_arg = [$arguments, $this];
        //注册的插件
        if (isset(self::$plugin_list[$name])) {
            return call_user_func_array(self::$plugin_list[$name], $call_arg);
        }//尝试加载文件
        elseif ($this->loadExtendFile($name, 'plugin', $func_name)) {
            return call_user_func_array($func_name, $call_arg);
        } else {
            throw new TplException('不支持的插件:' . $name);
        }
    }

    /**
     * 加载管道
     * @param string $name 管道名称
     * @param array $arguments 参数
     * @return mixed
     * @throws TplException
     */
    public function loadGrep($name, $arguments)
    {
        $grep_name = self::systemGrepName($name);
        //系统自带
        if (is_callable($grep_name)) {
            return call_user_func_array($grep_name, $arguments);
        } //注册的管道
        elseif (isset(self::$grep_list[$name])) {
            return call_user_func_array(self::$grep_list[$name], $arguments);
        }//尝试加载文件
        elseif ($this->loadExtendFile($name, 'grep', $func_name)) {
            return call_user_func_array($func_name, $arguments);
        } else {
            throw new TplException('不支持的管道函数:' . $name);
        }
    }

    /**
     * 加载外部文件
     * @param string $name
     * @param string $type 类型
     * @param null $func_name
     * @return bool
     */
    private function loadExtendFile($name, $type, &$func_name)
    {
        $file_name = $this->extend_dir . $type . '.uis_tpl.' . $name . '.php';
        if (is_file($file_name)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file_name;
        } else {
            return false;
        }
        $func_name = $type . '_uis_tpl_' . $name;
        return function_exists($func_name);
    }

    /**
     * 生成系统自带的管道函数名称
     * @param string $name
     * @return string
     */
    private static function systemGrepName($name)
    {
        return '\UiStd\Tpl\TplGrep::' . $name;
    }

    /**
     * 注册一个自定义管道
     * @param string $name 管道名称
     * @param callable $handle
     * @throws TplException
     */
    public static function registerGrep($name, callable $handle)
    {
        $name = (string)$name;
        if (!self::nameValidCheck($name)) {
            throw new TplException('管道名称：' . $name . ' 出错');
        }
        if (is_callable(self::systemGrepName($name))) {
            throw new TplException('管道 ' . $name . ' 为系统自带的管道');
        }
        self::$grep_list[$name] = $handle;
    }

    /**
     * 注册一个自定义插件
     * @param string $name 管道名称
     * @param callable $handle
     * @throws TplException
     */
    public static function registerPlugin($name, callable $handle)
    {
        $name = (string)$name;
        if (!self::nameValidCheck($name)) {
            throw new TplException('插件名称：' . $name . ' 出错');
        }
        self::$plugin_list[$name] = $handle;
    }

    /**
     * 是否能当作变量名
     * @param string $name 变量名
     * @return int
     */
    private static function nameValidCheck($name)
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z_\d]*$/', $name);
    }

    /**
     * 是否存在某个模板
     * @param string $tpl_name
     * @param null $tpl_file
     * @return bool
     */
    public static function hasTpl($tpl_name, &$tpl_file = null)
    {
        $tpl = self::getInstance();
        $tpl_name = $tpl->cleanTplName($tpl_name);
        $tpl_file = $tpl->tplFileName($tpl_name);
        return is_file($tpl_file);
    }

    /**
     * 获取实例（单例）
     * @return Tpl
     */
    public static function getInstance()
    {
        if (null === self::$singleton) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }
}
