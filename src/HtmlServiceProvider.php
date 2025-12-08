<?php

namespace SSLCollective;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Facades\Blade;

class HtmlServiceProvider extends ServiceProvider
{
    /**
     * The available form directives.
     *
     * @var array
     */
    protected $directives = [
        'entities', 'decode', 'script', 'style', 'image', 'favicon', 'link', 'secureLink',
        'linkAsset', 'linkSecureAsset', 'linkRoute', 'linkAction', 'mailto', 'email', 'ol',
        'ul', 'dl', 'meta', 'tag', 'open', 'model', 'close', 'token', 'label', 'input', 'text',
        'password', 'hidden', 'email', 'tel', 'number', 'date', 'datetime', 'datetimeLocal', 'time',
        'url', 'file', 'textarea', 'select', 'selectRange', 'selectYear', 'selectMonth',
        'getSelectOption', 'checkbox', 'radio', 'reset', 'image', 'color', 'submit', 'button', 'old'
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHtmlBuilder();
        $this->registerFormBuilder();
    }

    /**
     * Register the HTML builder instance.
     *
     * @return void
     */
    protected function registerHtmlBuilder()
    {
        $this->app->singleton('html', function ($app) {
            return new HtmlBuilder($app['url'], $app['view']);
        });
    }

    /**
     * Register the form builder instance.
     *
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function ($app) {
            $form = new FormBuilder(
                $app['html'],
                $app['url'],
                $app['view'],
                $app['session.store']->token()
            );

            return $form->setSessionStore($app['session.store']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $namespaces = [
                'Html' => get_class_methods(HtmlBuilder::class),
                'Form' => get_class_methods(FormBuilder::class),
            ];

            foreach ($namespaces as $namespace => $methods) {
                foreach ($methods as $method) {
                    if (in_array($method, $this->directives)) {
                        $snakeMethod = Str::snake($method);
                        $directive = strtolower($namespace).'_'.$snakeMethod;

                        $bladeCompiler->directive($directive, function ($expression) use ($namespace, $method) {
                            return "<?php echo $namespace::$method($expression); ?>";
                        });
                    }
                }
            }
        });
    }
}
