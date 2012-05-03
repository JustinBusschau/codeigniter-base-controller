<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * A base controller for CodeIgniter with view autoloading, layout support,
 * model loading, asides/partials and per-controller 404
 *
 * @link http://github.com/jamierumbelow/codeigniter-base-controller
 * @copyright Copyright (c) 2012, Jamie Rumbelow <http://jamierumbelow.net>
 */

class MY_Controller extends CI_Controller
{

    /* --------------------------------------------------------------
     * VARIABLES
     * ------------------------------------------------------------ */

    /**
     * Define a default action for this controller if index()
     * is not available
     */
    protected $index = '';

    /**
     * The current request's view. Automatically guessed
     * from the name of the controller and action
     */
    protected $view = '';

    /**
     * An array of variables to be passed through to the
     * view, layout and any asides
     */
    protected $data = array();

    /**
     * The name of the layout to wrap around the view.
     */
    protected $layout;

    /**
     * An arbitrary list of asides to be loaded into
     * the layout. The key is the declared name, the value the file
     */
    protected $asides = array();

    /**
     * An arbitrary list of partials to be included into
     * the layout. The key is the declared name, the value the content
     */
    protected $partials = array();

    /**
     * A list of models to be autoloaded
     */
    protected $models = array();

    /**
     * A formatting string for the model autoloading feature.
     * The percent symbol (%) will be replaced with the model name.
     */
    protected $model_string = '%_model';

    /**
     * Whether or not to use the CI Parser when loading views
     */
    protected $use_parser = FALSE;

    /* --------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------ */

    /**
     * Initialise the controller, tie into the CodeIgniter superobject
     * and try to autoload the models
     */
    public function __construct()
    {
        parent::__construct();

        $this->_load_models();
    }

    /* --------------------------------------------------------------
     * VIEW RENDERING
     * ------------------------------------------------------------ */

    /**
     * Override CodeIgniter's despatch mechanism and route the request
     * through to the appropriate action. Support custom 404 methods and
     * autoload the view into the layout.
     */
    public function _remap($method)
    {
        if (method_exists($this, $method))
        {
            call_user_func_array(array($this, $method), array_slice($this->uri->rsegments, 2));
        }
        else
        {
            if (!empty($this->index) && method_exists($this, $this->index))
            {
                $this->router->method = $this->index;
                call_user_func_array(array($this, $this->index), array_slice($this->uri->rsegments, 2));
            }
            elseif (method_exists($this, '_404'))
            {
                call_user_func_array(array($this, '_404'), array($method));
            }
            else
            {
                show_404(strtolower(get_class($this)).'/'.$method);
            }
        }

        $this->_load_view();
    }

    /**
     * Automatically load the view, allowing the developer to override if
     * he or she wishes, otherwise being conventional.
     */
    protected function _load_view()
    {
        // If $this->view == FALSE, we don't want to load anything
        if ($this->view !== FALSE)
        {
            // If $this->view isn't empty, load it. If it is empty, try and guess based on the controller and action name
            $view = (!empty($this->view)) ? $this->view : ($this->router->fetch_module()) ? $this->router->class . '/' . $this->router->method : $this->router->directory . $this->router->class . '/' . $this->router->method;

            // Load the view into $yield
            $data['yield'] = $this->_load($view, $this->data, TRUE);

            // Do we have any asides? Load them.
            if (!empty($this->asides))
            {
                foreach ($this->asides as $name => $file)
                {
                    $data['yield_'.$name] = $this->_load($file, $this->data, TRUE);
                }
            }

            // Do we have any partials? Add them.
            if (!empty($this->partials))
            {
                foreach ($this->partials as $name => $content)
                {
                    $data['yield_'.$name] = $content;
                }
            }

            // Load in our existing data with the asides and view
            $data = array_merge($this->data, $data);
            $layout = FALSE;

            // If we didn't specify the layout, try to guess it
            if (!isset($this->layout))
            {
                if (file_exists(APPPATH . 'views/layouts/' . $this->router->class . '.php'))
                {
                    $layout = 'layouts/' . $this->router->class;
                }
                else
                {
                    $layout = 'layouts/application';
                }
            }

            // If we did, use it
            else if ($this->layout !== FALSE)
            {
                $layout = $this->layout;
            }

            // If $layout is FALSE, we're not interested in loading a layout, so output the view directly
            if ($layout == FALSE)
            {
                $this->output->set_output($data['yield']);
            }

            // Otherwise? Load away :)
            else
            {
                $this->_load($layout, $data);
            }
        }
    }

    /**
     * Load the view, or parse the template ...
     */
    protected function _load($view, $data, $return = FALSE)
    {
        if ($this->use_parser)
        {
            return $this->parser->parse($view, $data, $return);
        }
        else
        {
            return $this->load->view($view, $data, $return);
        }
    }

    /* --------------------------------------------------------------
     * MODEL LOADING
     * ------------------------------------------------------------ */

    /**
     * Load models based on the $this->models array
     */
    private function _load_models()
    {
        foreach ($this->models as $model)
        {
            $alias = ($m = strpos($model, '/')) ? substr($model, $m+1) : $model;
            $this->load->model($this->_model_name($model), $alias);
        }
    }

    /**
     * Returns the loadable model name based on
     * the model formatting string
     */
    protected function _model_name($model)
    {
        return str_replace('%', $model, $this->model_string);
    }
}
