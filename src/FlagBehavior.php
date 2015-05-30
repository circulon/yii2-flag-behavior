<?php
/**
 *
 * @author Kieren Eaton <circledev@gmail.com>
 *
 * Date: 22.06.14
 */
namespace circulon\flags;

use yii\base\Behavior;
//use yii\db\ActiveRecord;
//use yii\db\Query;


class FlagBehavior extends Behavior
{
    /**
     * @var string
     */
    public $attribute = 'flags';

    /**
     * @var array
     */
    public $flags = array();

    /**
     * setFlag function.
     *
     * @param string $name
     * @param bool $value (default: true)
     */
    public function setFlag($name, $value = true)
    {
        $flags = $this->owner->{$this->attribute};
        $flagValue = $this->getFlagValue($name);

        if ($value) {
            $this->owner->{$this->fieldName} = $flags | $flagValue;
        } else {
            $this->owner->{$this->fieldName} = $flags & $flagValue
                ? $flags ^ $flagValue
                : $flags;
        }
    }

    /**
     * Unset specified flag value
     * Could use both clearFlag(User::SETTINGS_ENABLED) or setFlag(User::SETTINGS_ENABLED, false)
     * @param $name
     */
    public function clearFlag($name)
    {
        $this->setFlag($name, false);
    }

    /**
     * Is specified flag set?
     * @param $name
     * @return bool
     */
    public function hasFlag($name)
    {
        $flag = $this->getFlagValue($name);
        return ($this->owner->{$this->fieldName} & $flag) === $flag;
    }

    /**
     * Get flag index (bit's order) in collection
     * @param $name
     * @return int
     * @throws CException
     */
    public function getFlagIndex($name)
    {
        if (!isset($this->flags[$name]))
           throw new \yii\base\UnknownPropertyException("attribute not found" );
        return $this->flags[$name];
    }

    /**
     * Get flag value
     * @param $name
     * @return number
     */
    public function getFlagValue($name)
    {
        return pow(2,$this->getFlagIndex($name));
    }

    /**
     * Search by flags
     * @param $flags
     * @return CActiveRecord
     */
    public function scopeFlags($flags)
    {
        if (is_array($flags)) {
            $flags = $this->mergeFlags($flags);
        }


        $this->owner->getDbCriteria()->mergeWith(array(
            'condition'=>$this->fieldName.' & :flag = :flag',
            'params' => array(':flag' => $flags),
        ));
        $this->owner->getDbCriteria()->params[':flag'] = $flags;

        return $this->owner;
    }

    /**
     * @param array|string $flags
     * @return CDbCriteria
     */
    public function getFlagCriteria($flags)
    {
        if (is_array($flags)) {
            $flags = $this->mergeFlags($flags);
        }

        $paramId = ':flag' . ++CDbCriteria::$paramCount;
        $criteria = new CDbCriteria();
        $criteria->addCondition($this->fieldName . " & {$paramId} = {$paramId}");
        $criteria->params[$paramId] = $flags;

        return $criteria;
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