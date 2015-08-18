<?php namespace ahtun\Up2;

use Closure;
use Carbon\Carbon;
use ahtun\Up2\StoreInterface;
use Illuminate\Support\Traits\MacroableTrait;

class Repository {

    use MacroableTrait {
        __call as macroCall;
    }

    /**
     * The uploader store implementation.
     *
     * @var \ahtun\Up2\StoreInterface
     */
    protected $store;

    /**
     * Create a new uploader repository instance.
     *
     * @param  \ahtun\Up2\StoreInterface  $store
     */
    public function __construct(StoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * Get the uploader store implementation.
     *
     * @return \ahtun\Up2\StoreInterface
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method))
        {
            return $this->macroCall($method, $parameters);
        }

        return call_user_func_array(array($this->store, $method), $parameters);
    }

}