<?php
namespace Bootstrap\Container;

class Application extends \Illuminate\Container\Container
{
    /**
     * Detect the application's current environment.
     *
     * @param  array|string $environments
     *
     * @return string
     */
    public function detectEnvironment($environments)
    {
        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
        if (php_sapi_name() == 'cli' && !is_null($value = $this->getEnvironmentArgument($args))) {
            //running in console and env param is set
            return $this['env'] = head(array_slice(explode('=', $value), 1));
        } else {
            //running as the web app

            if ($environments instanceof Closure) {
                // If the given environment is just a Closure, we will defer the environment check
                // to the Closure the developer has provided, which allows them to totally swap
                // the webs environment detection logic with their own custom Closure's code.
                return $this['env'] = call_user_func($environments);
            } elseif (is_array($environments)) {
                foreach ($environments as $environment => $hosts) {
                    // To determine the current environment, we'll simply iterate through the possible
                    // environments and look for the host that matches the host for this request we
                    // are currently processing here, then return back these environment's names.
                    foreach ((array)$hosts as $host) {
                        if (str_is($host, gethostname())) return $this['env'] = $environment;
                    }
                }
            } elseif (is_string($environments)) {
                return $this['env'] = $environments;
            }
        }
        return $this['env'] = 'production';
    }

    /**
     * Get the environment argument from the console.
     *
     * @param  array $args
     *
     * @return string|null
     */
    private function getEnvironmentArgument(array $args)
    {
        return array_first($args, function ($k, $v) {
            return starts_with($v, '--env');
        });
    }

    public function environment()
    {
        return $this['env'];
    }

    /**
     * Bind the installation paths to the application.
     *
     * @param  array $paths
     *
     * @return void
     */
    public function bindInstallPaths(array $paths)
    {
        $this->instance('path', realpath($paths['app']));

        // Here we will bind the install paths into the container as strings that can be
        // accessed from any point in the system. Each path key is prefixed with path
        // so that they have the consistent naming convention inside the container.
        foreach (array_except($paths, array('app')) as $key => $value) {
            $this->instance("path.{$key}", realpath($value));
        }
    }
} 