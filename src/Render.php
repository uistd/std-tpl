<?php
namespace FFan\Std\Tpl;

/**
 * Class Render
 * @package FFan\Std\Tpl
 */
class Render
{
    /**
     * @var Tpl
     */
    private $tpl;

    /**
     * @var array
     */
    private $tpl_data;

    /**
     * Render constructor.
     * @param Tpl $tpl
     */
    public function __construct($tpl)
    {
        $this->tpl = $tpl;
    }

    /**
     * 获取模板运行内容
     * @param string $tpl_name 模板名称
     * @param null|array $model
     * @param bool $is_echo 模板结果是否打印出来
     * @return string
     * @throws TplException
     */
    public function load($tpl_name, $model = null, $is_echo = false)
    {
        if (null !== $model && null === $this->tpl_data) {
            $this->tpl_data = $model;
        }
        $tpl_name = $this->tpl->cleanTplName($tpl_name);
        $func_name = $this->tpl->tplMethodName($tpl_name);
        $tpl_file = $this->tpl->tplFileName($tpl_name);
        if (!is_file($tpl_file)) {
            throw new TplException('No tpl ' . $tpl_file . ' found');
        }
        $last_time = filemtime($tpl_file);
        $compile_file = $this->tpl->tplCompileName($func_name);
        $func_name .= '_' . $last_time;
        //如果不存在，或者模板被修改过了
        if (!$this->tpl->isCacheResult() || !is_file($compile_file) || !$this->tpl->isCacheValid($compile_file, $func_name)) {
            $this->tpl->compileTpl($tpl_file, $func_name, $compile_file);
        }
        return call_user_func_array($func_name, array($this, $is_echo));
    }

    /**
     * 获取tpl对象
     * @return Tpl
     */
    public function getTpl()
    {
        return $this->tpl;
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
     * 赋值
     * @param string $name 变量名
     * @param mixed $value 值
     */
    public function assign($name, $value)
    {
        $this->tpl_data[$name] = $value;
    }
}
