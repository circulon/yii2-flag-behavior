yii2-flag-behavior
==================

Yii 2.x behavior for managing multiple (virtual) boolean attributes stored as a a single attribute in a model.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require circulon/yii2-flag-behavior "*"
```

or add

```
"circulon/yii2-flag-behavior": "*"
```

to the ```require``` section of your `composer.json` file.

## Note:
In MySQL (I have not used other DB) we can use TINYINT, SMALLINT, MEDIUMINT, INT & BIGINT which offer 8, 16, 24, 32 & 64 flags respectively. 
Generally TINYINT(8) or SMALLINT(16) are a good starting point, this also reduces the overhead in your DB.

## Usage
### Add behavior to the model

> IMPORTANT: attribute names MUST be unique to the model as per standard attribute naming

> IMPORTANT: The ORDER of attributes (ie the bit position) CANNOT be changed after a record has been inserted.
Please check your order before you insert for the first time. 

> Additional flags can be added without issue 

```php
    // Recommended to use constants for 
    // virtual attribute names
    const SETTINGS_ACTIVE = 'active';
    const SETTINGS_ADMIN = 'admin';
    const SETTINGS_POST_COMMENTS = 'postComments';

    public function behaviors()
    {
        return [
            'FlagBehavior'=> [
                'class'=> FlagBehavior::className(),
                'fieldattribute' => 'flags', // optional 
                'attributes' => [ 
                    $this::SETTINGS_ACTIVE => 0,  // The index order once set
                    $this::SETTINGS_ADMIN => 1,   // MUST NOT BE CHANGED 
                    $this::SETTINGS_POST_COMMENTS => 2,
                ],
            ],
        ];
    }
```    

### Access
```php
    // get/set db attribute directly
    $value = $model->fieldAttribute;  // initialy set to 0 
    $model->fieldAttribute = 6; // set flags to admin and post comments (110)

    // change flags
    // NOTE: it is recommended to use the model constants for attribute
    // name expansion. this will 
    $model->{<class name>::SETTINGS_ACTIVE} = true; // set flag (now 111)
    $model->active = true; 
    $model->setFlag({<class name>::SETTINGS_ADMIN}, false); // clear flag (now 101)
    $model->setFlag('postComments', false); // clear flag (now 001)
    $model->{<class name>::SETTINGS_ACTIVE} = false; // clear flag (now 000)
    
    // check if a flag/setting is set
    if ($model->{<class name>::SETTINGS_POST_COMMENTS}) {
        // do something here
    }
    if (!$model->active) {
        // show error message
    }
    if ($model->hasFlag({<class name>::SETTINGS_ADMIN})) {
        // allow admin user to ....
    }
  
```
    
    
