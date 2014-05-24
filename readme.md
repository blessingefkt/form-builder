## Laravel Form Builder

### A simple and intuitive form builder
Based on https://github.com/Flynsarmy/laravel-form-builder

### Installation
Require this package in your composer.json and run `composer update` (or run `composer require iyoworks/form-builder:1.0.*` directly):

	"iyoworks/form-builder": "1.0.*"

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

	'Iyoworks\FormBuilder\FormBuilderServiceProvider',

and optionally the Facade to the aliases array in the same file. This will allow for global callbacks (more on that later).

	'FormBuilder'     => 'Iyoworks\FormBuilder\Facades\FormBuilder',

### Usage

#### Add/Edit/Delete Fields

Create a form, add fields, render.

```php
$form = FormBuilder::form();
$form->action('UserController@create')
//$form->add[FieldType]([field_slug] [, Field Label]);
$form->addText('first_name', 'First Name');
$form->addSelect('gender')->options(['male'=>'Male', 'female'=>'Female', 'none'=>'Not Telling']);

$form->render();
```

Need to edit or remove a field?

```php
// Set field with id 'gender' to have 3 options instead of 2.
$form->getField('gender')->options(['m'=>'Male', 'f'=>'Female', 'n'=>'Not Telling']);

// Remove the gender field
$form->remove('gender');
```

Add fields exactly where you want them

```php
// Add last name after first name
$form->addAfter('first_name', 'last_name', 'text');
$form->addTextAfterFirstName('last_name');
$form->addBefore('last_name', 'first_name', 'text');
$form->addTextAfterLastName('first_name');
```

Closures are also supported

```php
use Iyoworks\FormBuilder\Form;
use Iyoworks\FormBuilder\Field;
// Closure support for FormBuilder
$form = FormBuilder::form(function(Form $form) {
    $form->url('users/create');
	$form->addText('first_name');
    $form->addSelect('gender'->options(['M'=>'Male', 'F'=>'Female']);
})->html();
```

```php
echo $form->open(), $form->render(), $form->close();
# the same as
echo $form->html();
```


#### Field settings

You can add fields to rows

```php
$form->addRow(function($form){
    $form->addText('first_name', 'First Name')
    	->label('First Name')
    	->description('Enter your first name')
    	->columns(12);
    $form->addText('last_name', 'Last Name')
        ->label('First Name')
        ->description('Enter your last name')
        ->columns(12);
});
$form->addEmail('email', 'Email Address')
$form->addSubmit('Submit')->addClass('btn btn-block btn-primary');
```

#### Callbacks

Callbacks can be used to render your form exactly the way you want it to look.

Supported callbacks include:

```php
beforeForm(Form $form)
afterForm(Form $form)
beforeField(Form $form, Field $field)
afterField(Form $form, Field $field)
```

They can be used on a per-form basis

```php
// Per-form Callbacks
$form->beforeField(function(Form $form, Field $field) {
	// Use field settings to display your form nicely
	return '<label>' . $field->label . '</label>';
});
```

or using the optional facade, a global basis

```php
// Global form callbacks
FormBuilder::bind('beforeField', function(Form $form, Field $field) {
		return '<div class="form-group"><label>'.$field->label.'</label>';
	})
	->bind('afterField', function(Form $form, Field $field) {
		$output = '';
        if ( $field->description )
            $output .= '<p class="help-block">' . $field->description . '</p>';
        return $output . '</div>';
	});
$form = FormBuilder::form(function(Form $form) {
    $form->route('user.create');
	$form->addText('first_name', 'First Name');
	$form->addText('last_name')->label('Last Name');
});

echo $form->model($model)->html();
```

### License

Laravel Form Builder is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)