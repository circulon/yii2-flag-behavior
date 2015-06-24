<?php
/**
 * @copyright Copyright &copy; Kieren Eaton, 2015
 * @version 1.0.0
 */
namespace circulon\flag;

use yii\base\Behavior;

/**
 * @author Kieren Eaton <circledev@gmail.com>
 */
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
     * @var array
     */
    public $options = [];

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

        $this->setFlag($name, (bool)$value);
        $this->processOptions($name, (bool)$value);
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

    private function processOptions($name, $value)
    {
      if (!isset($this->options[$name])) { return; }

      // options: $flag => $options
      //    $flag : the source attribute
      //    $options : an array of $operator => $fields
      //      $operator : (set|clear|not)
      //        set: sets the attribute to a given value (true|false|'source')
      //          'source' will set the attribute to the same as the source ttributes value
      //          if only the attribute name is provided the attribute is set to true
      //
      //        clear: clears the attributes listed
      //        not: sets the value of the attributes to the inverse/complement of the source attribute

      $options = $this->options[$name];
      foreach ($options as $opt => $otherFlags)
      {
        foreach ( $otherFlags as $flagKey => $flagValue)
        {
          $newValue = true;
          if ($opt === 'set') {
            if (!is_int($flagKey)) {
              $newValue = ($flagValue === 'source')? $value : $flagValue;
            }
          } else {
            $newValue = ($opt === 'clear') ? false : !$value;
          }
          $this->setFlag($aFlag, $newValue);
        }
      }
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