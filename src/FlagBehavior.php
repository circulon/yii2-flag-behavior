<?php
/**
 * @copyright Copyright &copy; Kieren Eaton, 2015
 * @version 1.0.0
 */
namespace circulon\flag;

use yii\base\Behavior;
use yii\validators\Validator;

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
    public function attach($owner)
    {
        parent::attach($owner);
        $validators = $owner->validators;
        $validator = Validator::createValidator('safe', $owner, array_keys($this->attributes));
        $validators->append($validator);
    }

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
     * Get a list of flags suitable for use in dropdownlists
     * and gridview search filter dropdown lists
     *
     * @access public
     * @param mixed $state (default: null)
     * @return void
     */
    public function searchFilterList($state = null)
    {
      $attribs = $this->flagsList($state);
      $list = [];

      foreach ($attribs as $key => $dummy)
      {
        $list[$key] = $this->owner->getAttributeLabel($key);
      }

      return $list;
    }

    /**
     * flagsList function.
     *
     * @access public
     * @param mixed $state (default: null)
     * @param bool $values (default: false)
     * @return void
     */
    public function flagsList($state = null, $values = false)
    {
      $list = [];
      foreach ($this->attributes as $key => $pos)
      {
        if (($state === null) || ($this->{$key} === $state)) {
          $list[$key] = ($values) ? (string)$this->flagValue($key) : $this->{$key};
        }
      }
      return $list;
    }

    /**
     * @param ActiveQuery $query
     * @param array|string $flags
     * @param bool $prefixTablename
     * @return ActiveQuery
     */
    public function addFlagsCriteria($query, $flags, $prefixTablename = false)
    {
        if (!is_array($flags)) {
            $flags = [$flags];
        }

        $trueFlags = [];
        $falseFlags = [];
        $table = ($prefixTablename) ? $this->owner->tableName().'.' : '';
        $class = (new \ReflectionClass($this->owner))->getShortName();

        foreach($flags as $key => $value)
        {
          if ((isset($this->attributes[$key])) && ((bool)$value === false)) {
            $falseFlags[] = $this->attributes[$key];
          } else {
            if (isset($this->attributes[$key])) {
              $trueFlags[] = $this->attributes[$key];
            } else {
              $trueFlags[] = $this->attributes[$value];
            }
          }
        }

        if (!empty($trueFlags)) {
          $tvalue = $this->mergeFlags($trueFlags);
          $query->andWhere($table."[[{$this->flagsAttribute}]] & :tvalue{$class}", [":tvalue{$class}" => $tvalue]);
        }

        if (!empty($falseFlags)) {
          $fvalue = $this->mergeFlags($falseFlags);
          $query->andWhere('!('.$table."[[{$this->flagsAttribute}]] & :fvalue{$class})", [":fvalue{$class}" => $fvalue]);
        }

        return $query;
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
     * processOptions function.
     *
     * @access private
     * @param mixed $sourceKey
     * @param mixed $sourceValue
     * @return void
     */
    private function processOptions($sourceKey, $sourceValue)
    {
      if (!isset($this->options[$sourceKey])) { return; }

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

      $options = $this->options[$sourceKey];
      foreach ($options as $operator => $otherFlags)
      {
        $otherFlags = (is_string($otherFlags)) ? [$otherFlags] : $otherFlags;
        foreach ( $otherFlags as $flagKey => $flagValue)
        {
          $newValue = true;
          if ($operator === 'set') {
            if (!is_int($flagKey)) {
              $newValue = ($flagValue === 'source') ? $sourceValue : $flagValue;
            }
          } else {
            $newValue = ($operator === 'clear') ? false : !$sourceValue;
          }
          $flagKey = (is_int($flagKey)) ? $flagValue : $flagKey;
          $this->setFlag($flagKey, $newValue);
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