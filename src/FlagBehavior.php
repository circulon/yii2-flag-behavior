<?php
/**
 *
 * @author Kieren Eaton <circledev@gmail.com>
 * @copyright Copyright &copy; Kieren Eaton, 2015
 * @version 1.0.0
 */
namespace circulon\flag;

use yii\base\Behavior;

class FlagBehavior extends Behavior
{
    /**
     * @inheritdoc
     */
    public $attributes = [];

    /**
     * @var string
     */
    public $flagsAttribute = 'flags';


    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if (isset($this->attributes[$name]) || ($name === $this->flagsAttribute)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name === $this->flagsAttribute) {
          return $this->owner->{$this->flagsAttribute};
        }

        $flag = $this->flagValue($name);
        return ($this->owner->{$this->flagsAttribute} & $flag) === $flag;
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if (isset($this->attributes[$name]) || ($name === $this->flagsAttribute)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name === $this->flagsAttribute) {
          $this->owner->{$this->flagsAttribute} = $value;
          return;
        }

        $this->setFlag($name,$value);
    }

    /**
     * setFlag function.
     *
     * @param string $name
     * @param bool $value (default: true)
     */
    public function setFlag($name, $value = true)
    {
        $currentFlags = $this->owner->{$this->flagsAttribute};
        $flagValue = $this->flagValue($name);

        if ($value) {
            $this->owner->{$this->flagsAttribute} = $currentFlags | $flagValue;
        } else {
            $this->owner->{$this->flagsAttribute} = ($currentFlags & $flagValue)
                ? $currentFlags ^ $flagValue
                : $currentFlags;
        }
    }

    /**
     * Get flag value
     * @param $name
     * @return number
     */
    private function flagValue($name)
    {
        return pow(2,$this->attributes[$name]);
    }

    /**
     * Get combined flags value
     * @param $flags
     * @return int
     */
    private function mergeFlags($flags)
    {
        return (int)array_reduce($flags, function($result, $value) {
            return $result = $result | pow(2, $value);
        });
    }

}