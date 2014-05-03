<?php namespace Flynsarmy\FormBuilder;

use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

    public function boot()
    {
        /** @var FormBuilderManager $formBuilder */
        $formBuilder = $this->app['formbuilder'];
        $formBuilder->addRenderer('laravel', function()
        {
           return new LaravelFormRenderer($this->app['form'], $this->app['html']);
        });
        $formBuilder->setDefaultRenderer('laravel');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('formbuilder', function()
        {
            return new FormBuilderManager();
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('formbuilder');
	}
}
