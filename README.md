yii2-flag-behavior
==================

Yii 2.x behavior for managing multiple (virtual) boolean attributes stored as a single integer attribute in a model.

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

> IMPORTANT: attribute names MUST be unique to the model as per standard attribute naming

> IMPORTANT: The ORDER of attributes (ie the bit position) CANNOT be changed after a record has been inserted.
Please check your order before you insert for the first time. 

> Additional flags can be added without issue 

### Add behavior to the model
```php
    use circulon\flag\FlagBehavior;

    // Recommended to use constants for 
    // virtual attribute names
    const SETTINGS_ACTIVE = 'active';
    const SETTINGS_ADMIN = 'admin';
    const SETTINGS_POST_COMMENTS = 'postComments';
    const SETTINGS_VIEW_REPORTS = 'voewReports';
    const SETTINGS_CREATE_REPORTS = 'createReports';
    
    public function behaviors()
    {
        return [
            'FlagBehavior'=> [
                'class'=> FlagBehavior::className(),
                'fieldattribute' => 'flags', // the db field attribute. Default : 'flags'
                // attributes:  $flag => $position 
                //    $flag : the attribute name  
                //    $position : the bit position (starting at 0)
                'attributes' => [ 
                    $this::SETTINGS_ACTIVE => 0,  // The bit position order once set
                    $this::SETTINGS_ADMIN => 1,   // MUST NOT BE CHANGED 
                    $this::SETTINGS_POST_COMMENTS => 2, // after any records have been inserted
                ],
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
                 
                'options' = [
                  $this::SETTINGS_ADMIN => [ 
                    'set' => [
                      $this::SETTINGS_POST_COMMENTS, // set to true
                      $this::SETTINGS_ACTIVE, // set to true
                    ]
                  ],
                  $this::SETTINGS_ACTIVE => [
                    'set' => [ $this::SETTINGS_POST_COMMENTS => 'source' ], // set same as SETTINGS_ACTIVE
                  ],
                  $this::SETTINGS_CREATE_REPORTS => [
                    'set' => [ $this::SETTINGS_VIEW_REPORTS], 
                  ],
                ],
            ],
        ];
    }
```    

### Access

> NOTE: it is recommended to use constants for attribute names. 
Should you decide to change the attribute name (not the position) this will then
be used in all models accessing the flags. 

```php
    // get/set db attribute directly
    $value = $model->fieldAttribute;  // initialy set to 0 
    $model->fieldAttribute = 6; // set flags to admin and post comments (110)

    // change flags
    // NOTE: 
    // 
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
    
### Searching

You can simply add criteria for your model/s flags

> NOTE: This may seem a little backwards (ie creating an empty model).
   but when creating multi model queries this is the simplest method. 
If you have a better or simpler method, let me know or send a pull request.


Example 1 -- Single model search

```php

  // 
  
  $model = new MyModelWithFlags()
  $query = MyModelWithFlags::find();
  // add additional criteria as required
  $query = $model->addFlagsCriteria($query, [
    MyModelWithFlags::SETTING_ACTIVE,  //  Flags with no value are assumed true
    MyModelWithFlags::SETTING_VIEW_REPORTS => false // optionally set the specific (bool or int 1/0) search value
  ]);
  // get records
  $query->all();
  // DataProvider
  $dataProvider = new ActiveDataProvider(['query' => $query]);

```  

Example 2 -- Complex (multi-model) search

```php

  $searchQuery = PrimarySearchModel::find();
  // add criteria....
  
  $model = new MyModelWithFlags()
  $searchQuery = $model->addFlagsCriteria($searchQuery, [
    MyModelWithFlags::SETTING_ACTIVE,  //  Flags with no value are assumed true
    MyModelWithFlags::SETTING_VIEW_REPORTS => false // optionally set the specific (bool or int 1/0) search value
  ], true); // generate tablename prefixes
  
  $otherModel = new MyOtherModelWithFlags()
  // add additional criteria as required
  $searchQuery = $otherModel->addFlagsCriteria($searchQuery, [
    MyOtherModelWithFlags::SETTING_OTHER => true,  
    MyOtherModelWithFlags::SETTING_SOME_SETTING => false, 
    MyOtherModelWithFlags::SETTING_WORKING,
  ], true); // generate tablename prefixes
  
  // get records
  $searchQuery->all();
  // DataProvider
  $dataProvider = new ActiveDataProvider(['query' => $searchQuery]);
  
```
   
