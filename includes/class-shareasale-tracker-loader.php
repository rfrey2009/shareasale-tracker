<?php
 
class ShareASale_Tracker_Loader {
    /**
   * @var array $actions An array of hook names, object names, and their methods to run
   * @var array $filters An array of filter names, object names, and their methods to run
   */
	protected $actions, $filters;
 
    public function __construct() {
 
        $this->actions = array();
        $this->filters = array();
     
    }

    /**
     * Adds a desired object method call to a WordPress hook name
     *
     * @param string $hook A valid WordPress hook name
     * @param object $component An object with a method to run on a given WordPress hook's trigger
     * @param string $callback A method's name on the $component
     */
    public function add_action( $hook, $component, $callback ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback );
    }

    /**
     * Adds a desired object method call to a WordPress filter name
     *
     * @param string $hook A valid WordPress filter name
     * @param object $component An object with a method to run on a given WordPress filter call
     * @param string $callback A method's name on the $component
     */
    public function add_filter( $hook, $component, $callback ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback );
    }

     /**
     * Utility function that add_action() and add_filter() use to add to their respective arrays
     *
     * @param array  $hooks An internal array property the hook/filter should be added i.e. this->actions or this->filters
     * @param string $hook A valid WordPress hook or filter name
     * @param object $component An object with a method to run on a given WordPress hook or filter
     * @param string $callback A method's name on the $component
     * @return array Returns the respective array after storing the new hook or filter on it
     */
    private function add( $hooks, $hook, $component, $callback ) {
        /*
        same as array_push($hooks, array(
                                'hook' => $hook,
                                'component' => $component,
                                'callback' => $callback)
                            );
        */
        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback
        );
        return $hooks;
 
    }
 
    /**
     * Loops through all $hooks and $filters array to call the WordPress API for each, wiring everything up
     */
    public function run() {
 
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
 
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        } 
    }
 
}