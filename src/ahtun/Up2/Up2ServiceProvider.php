<?php namespace ahtun\Up2;

use Illuminate\Support\ServiceProvider;
use ahtun\Up2\Attachments\Eloquent\Provider as AttachmentProvider;

use Imagine\Image\Box;
use Imagine\Image\Point;


class Up2ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register package.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('ahtun/up2');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAttachmentProvider();
		$this->registerUploader();
		$this->registerUp2();
	}

	/**
	 * Register attachment provider.
	 *
	 * @return void
	 */
	protected function registerAttachmentProvider()
	{
		$this->app['up2.attachment'] = $this->app->share(function($app)
		{
			$model = $app['config']->get('up2::attachments.model');

			return new AttachmentProvider($model);
		});
	}

	/**
	 * Register uploader adapter.
	 *
	 * @return void
	 */
	public function registerUploader()
	{
		$this->app['up2.uploader'] = $this->app->share(function($app)
		{
			return new UploaderManager($app);
		});
	}

	/**
	 * Register core class.
	 *
	 * @return void
	 */
	protected function registerUp2()
	{
		$this->app['up2'] = $this->app->share(function($app)
		{
			$app['up2.loaded'] = true;

			//s(get_class_methods($app['up2.uploader']));

			//sd($app['up2.uploader']->getDefaultDriver());

			return new Up2($app['config'], $app['up2.attachment'], $app['up2.uploader']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('attach', 'up2');
	}

}