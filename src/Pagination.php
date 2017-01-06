<?php

/**
 * This file handles pagination using either MySQLi or PDO, and outputs the pagination
 * in the form of a UL
 *
 * Copyright (c) 2012 Ben Griffiths. All rights reserved.
 *
 * @name      pagination
 *
 * @author    Ben <ben@ben-griffiths.com>
 *
 * @copyright 2012 Ben Griffiths
 *
 * @url       https://github.com/BenGriffiths/pdo-mysqli-pagination
 *
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Pagination
{


    /**
     * Array of options for the class
     *
     * @access public
     * @var    array
     */
    protected $options = array(
        'results_per_page'              => 10,
        'max_pages_to_fetch'            => 1000000,
        'url'                           => '',
        'url_page_number_var'           => '*VAR*',
        'text_prev'                     => '&laquo; Prev',
        'text_next'                     => 'Next &raquo;',
        'text_first'                    => '&laquo; First',
        'text_last'                     => 'Last &raquo;',
        'text_ellipses'                 => '...',
        'class_ellipses'                => 'ellipses',
        'class_dead_links'              => 'dead-link',
        'class_live_links'              => 'live-link',
        'class_current_page'            => 'current-link',
        'class_ul'                      => 'pagination',
        'current_page_is_link'          => true,
        'show_links_first_last'         => true,
        'show_links_prev_next'          => true,
        'show_links_first_last_if_dead' => true,
        'show_links_prev_next_if_dead'  => true,
        'max_links_between_ellipses'    => 7,
        'max_links_outside_ellipses'    => 2,
        'db_conn_type'                  => 'pdo',  /* Can be either: 'mysqli' or 'pdo' */
        'db_handle'                     => 0,
        'named_params'                  => false,
        'using_bound_params'            => false,
        'using_bound_values'            => false
    );


    /**
     * True if all good, false if theres a problem
     *
     * @access public
     * @var    bool
     */
    public $success = true;


    /**
     * The current page
     *
     * @access protected
     * @var    int
     */
    protected $current_page;


    /**
     * The query to run on the database
     *
     * @access protected
     * @var    string
     */
    protected $query;


    /**
     * The total total number of links to render before showing the ellipses
     *
     * @access protected
     * @var    int
     */
    protected $number_of_links_before_showing_ellipses;


    /**
     * The PDO object - stored when users want to bind some params
     *
     * @access public
     * @var    object
     */
    protected $pdos;


    /**
     * The resultset of the query
     *
     * @access public
     * @var    resource
     */
    public $resultset;


    /**
     * The total results of the query
     *
     * @access public
     * @var    int
     */
    public $total_results;


    /**
     * The total pages returned
     *
     * @access public
     * @var    int
     */
    public $total_pages;


    /**
     * The pagination links (as an array)
     *
     * @access public
     * @var    array
     */
    public $links_array;


    /**
     * The pagination links (Presented as an UL)
     *
     * @access public
     * @var    string
     */
    public $links_html;


   /**
    * __construct(int $surrent_page, string $query, array $options)
    *
    * Class constructor
    *
    * @access  public
    * @param   int     $current_page  The number of the current page (Starts at 1)
    * @param   string  $query         The query to run on the database
    * @param   array   $options       An array of options
    * @return  void
    */
    public function __construct($current_page = 1, $query = '', $options = null)
    {
        $this->run($current_page, $query, $options);
    }


    /**
     * run(int $surrent_page, string $query, array $options)
     *
     * Run the class
     *
     * @access  public
     * @param   int     $current_page  The number of the current page (Starts at 1)
     * @param   string  $query         The query to run on the database
     * @param   array   $options       An array of options
     * @return  void
     */
    public function run($current_page = 1, $query = '', $options = null)
    {
        /*
         * Set the current page
         */
        $this->current_page = $current_page;

        /*
         * Set the query to run
         */
        $this->query = $query;

        /*
         * Populate the options array
         */
        if($this->set_options($options) == true)
        {
            /*
             * Add any extra code into the query
             */
            $this->prepare_query();

            /*
             * Execute the SQL
             */
            $this->excecute_query();

            /*
             * Calculate the total number of pages
             */
            $this->calculate_number_of_pages();

            /*
             * Work out the total number of pages before an ellipses is shown
             */
            $this->calculate_max_pages_before_ellipses();

            /*
             * Build the HTML to output
             */
            $this->build_links();

            /*
             * Set success to true
             */
            $this->success = true;
        }
        else
        {
            /*
             * Set success to false
             */
            $this->success = false;
        }
    }


    /**
     * set_options(array $options)
     *
     * Apply any options that have been provided
     *
     * @access  protected
     * @param   array   $options  An array of options
     * @return  void
     */
    protected function set_options($options = null)
    {
        if(!empty($options))
        {
            foreach($options as $key => $value)
            {
                if(array_key_exists($key, $this->options))
                {
                    $this->options[$key] = $value;
                }
                else
                {
                    throw new paginationException('Attempted to add setting \''.$key.'\' with the value \''.$value.'\' - option does not exist');
                }
            }
        }

        /*
         * Check to make sure we've been given a db handle
         */
        if(trim($this->options['url']) == '')
        {
            throw new paginationException('You have not provided a URL - please pass one with the option \'url\'');
            return false;
        }

        /*
         * Check to make sure we've been given a db handle
         */
        if(is_int($this->options['db_handle']) && $this->options['db_handle'] == 0)
        {
            throw new paginationException('You have not provided a DB Handle (Object) - please pass one with the option \'db_handle\'');
            return false;
        }

        /*
         * Check to make sure 'max_links_between_ellipses' is an odd number
         */
        if(!($this->options['max_links_between_ellipses'] & 1))
        {
            throw new paginationException('Setting \'max_links_between_ellipses\' has been set with the value \''.$this->options['max_links_between_ellipses'].'\' - This number must be an odd number');
            return false;
        }

        /*
         * Check to make sure the page number variable is in the URL
         */
        $page_number_var_position = strpos($this->options['url'], $this->options['url_page_number_var']);
        if($page_number_var_position === false)
        {
            throw new paginationException('You have not placed the variable in your URL that will be replaced with the page number - please add this variable where required: <strong>'.$this->options['url_page_number_var'].'<strong>');
            return false;
        }

        /*
         * If the checks have passed, return true
         */
        return true;
    }


    /**
     * prepare_query(void)
     *
     * Prepares the query to be run with the found rows and start/end limits
     *
     * @access  protected
     * @return  void
     */
    protected function prepare_query()
    {
        /*
         * Add SQL_CALC_FOUND_ROWS (If it's not there) for finding out total amount of results later on
         */
        if(substr($this->query, 0, 26) != 'SELECT SQL_CALC_FOUND_ROWS')
        {
          /*
          * Support for queries starting with "(", mostly used when UNION
          */

          if(substr($this->query, 0, 1) == '(')
          {
            $this->query = substr_replace(trim($this->query), '(SELECT SQL_CALC_FOUND_ROWS', 0, 7);
          }
          else
          {
            $this->query = substr_replace(trim($this->query), 'SELECT SQL_CALC_FOUND_ROWS', 0, 6);
          }
        }

        /*
         * Add our start/end limit
         */
        if($this->current_page == 1)
        {
            $this->query .= ' LIMIT 0, '.$this->options['results_per_page'];
        }
        else
        {
            $this->query .= ' LIMIT '.(($this->current_page - 1) * $this->options['results_per_page']).', '.$this->options['results_per_page'];
        }
    }


    /**
     * excecute_query(void)
     *
     * Run's the query against the database
     *
     * @access  protected
     * @return  void
     */
    protected function excecute_query()
    {
        if($this->options['db_conn_type'] == 'mysqli')
        {
            /*
             * Execute using MySQLi
             */
            $this->resultset = $this->options['db_handle']->query($this->query);

            /*
             * Get the total results with FOUND_ROWS()
             */
            $count_rows = $this->options['db_handle']->query('SELECT FOUND_ROWS();');
            $found_rows = $count_rows->fetch_assoc();
            $this->total_results = $found_rows['FOUND_ROWS()'];
        }
        elseif($this->options['db_conn_type'] == 'pdo')
        {
            if($this->options['using_bound_params'] == false && $this->options['using_bound_values'] == false)
            {
                /*
                 * Execute using PDO - not using bindParams
                 */
                $pdos = $this->options['db_handle']->prepare($this->query);

                /*
                 * Use plain method or bind some named params
                 *
                 * Using alternate styles to avoid any errors with empty arrays
                 */
                if($this->options['named_params'] == false)
                {
                    $pdos->execute();
                }
                else
                {
                    $pdos->execute($this->options['named_params']);
                }

                $this->resultset = $pdos;

                /*
                 * Get the total results with FOUND_ROWS()
                 */
                $pdos_fr = $this->options['db_handle']->prepare("SELECT FOUND_ROWS();");
                $pdos_fr->execute();
                $pdos_fr_result = $pdos_fr->fetch(PDO::FETCH_ASSOC);
                $this->total_results = $pdos_fr_result['FOUND_ROWS()'];
            }
            else
            {
                /*
                 * Excecute using PDO, but pause for binding params
                 */
                $this->pdos = $this->options['db_handle']->prepare($this->query);
            }
        }
        else
        {
            /*
             * An unknown DB connection type has been set
             */
            throw new paginationException('You have selected a \'db_conn_type\' of \''.$this->options['db_conn_type'].'\' - this method is not supported');
        }
    }


    /**
     * bindParam(standard params)
     *
     * Bind params to the query
     *
     * @access  public
     * @param   multi   Typical bindParam attr
     * @return  void
     */
    public function bindParam($a = null, $b = null, $c = null, $d = null, $e = null)
    {
        $this->pdos->bindParam($a, $b, $c, $d, $e);
    }


    /**
     * bindValue(standard params)
     *
     * Bind values to the query
     *
     * @access  public
     * @param   multi   Typical bindValue attr
     * @return  void
     */
    public function bindValue($a = null, $b = null, $c = null)
    {
        $this->pdos->bindValue($a, $b, $c);
    }


    /**
     * execute(void)
     *
     * Continues the execution of the query after binding params
     *
     * @access  public
     * @return  void
     */
    public function execute()
    {
        $this->pdos->execute();

        $this->resultset = $this->pdos;

        /*
         * Get the total results with FOUND_ROWS()
         */
        $pdos_fr = $this->options['db_handle']->prepare("SELECT FOUND_ROWS();");
        $pdos_fr->execute();
        $pdos_fr_result = $pdos_fr->fetch(PDO::FETCH_ASSOC);
        $this->total_results = $pdos_fr_result['FOUND_ROWS()'];

        /*
         * Calculate the total number of pages
         */
        $this->calculate_number_of_pages();

        /*
         * Work out the total number of pages before an ellipses is shown
         */
        $this->calculate_max_pages_before_ellipses();

        /*
         * Build the HTML to output
         */
        $this->build_links();

        /*
         * Set success to true
         */
        $this->success = true;
    }


    /**
     * calculate_number_of_pages(void)
     *
     * Calculates how many pages there will be
     *
     * @access  protected
     * @return  void
     */
    protected function calculate_number_of_pages()
    {
        if(ceil($this->total_results / $this->options['results_per_page']) > $this->options['max_pages_to_fetch'])
        {
            $this->total_pages = $this->options['max_pages_to_fetch'];
        }
        else
        {
            $this->total_pages = ceil($this->total_results / $this->options['results_per_page']);
        }
    }


    /**
     * calculate_max_pages_before_ellipses(void)
     *
     * Calculates the number of links to show before showing an ellipses
     *
     * @access  protected
     * @return  void
     */
    protected function calculate_max_pages_before_ellipses()
    {
        $this->number_of_links_before_showing_ellipses = $this->options['max_links_between_ellipses'] + ($this->options['max_links_outside_ellipses'] * 2);
    }


    /**
     * build_link_url(int $page_number)
     *
     * Builds the URL to insert in links
     *
     * @access  protected
     * @param   int     $page_number  The page number to insert into the link
     * @return  string                The built URL
     */
    protected function build_link_url($page_number)
    {
        return str_replace($this->options['url_page_number_var'], $page_number, $this->options['url']);
    }


    /**
     * get_current_or_normal_class(int $page_number)
     *
     * Returns the live link class, or link link and current page class
     *
     * @access  protected
     * @param   int     $page_number  The page number to insert into the link
     * @return  string                The class to use
     */
    protected function get_current_or_normal_class($page_number)
    {
        if($page_number == $this->current_page)
        {
            return $this->options['class_live_links'].' '.$this->options['class_current_page'];
        }
        else
        {
            return $this->options['class_live_links'];
        }
    }


    /**
     * build_links(void)
     *
     * Build the HTML links
     *
     * @access  protected
     * @return  void
     */
    protected function build_links()
    {
        /*
         * Start the UL
         */
        $this->links_html = '<ul class="'.$this->options['class_ul'].'">'.PHP_EOL;

        $this->build_links_first_prev();

        /*
         * Build our main links
         */
        if($this->total_pages <= $this->number_of_links_before_showing_ellipses)
        {
            $this->build_links_skip_all_ellipses();
        }
        else
        {
            /*
             * We have enough links to show the ellipses, so run through other method
             */
            if($this->current_page <= (($this->options['max_links_between_ellipses'] + $this->options['max_links_outside_ellipses'])) - 2)
            {
                $this->build_links_skip_first_ellipses();
            }
            elseif($this->current_page > (($this->options['max_links_between_ellipses'] + $this->options['max_links_outside_ellipses']) - 2) && $this->current_page < (($this->total_pages - ($this->options['max_links_between_ellipses'] + $this->options['max_links_outside_ellipses']) + 1) + 2))
            {
                $this->build_links_dont_skip_ellipses();
            }
            else
            {
                $this->build_links_skip_last_ellipses();
            }
        }


        $this->build_links_next_last();


         /*
         * Close the UL
         */
        $this->links_html .= '</ul>'.PHP_EOL;
    }


    /**
     * build_li_element(string $class, string $text, bool $is_link, int $page_number)
     *
     * Builds LI elements
     *
     * @access  protected
     * @param   string     $class        The class to apply to the LI
     * @param   string     $text         The text for the LI element
     * @param   bool       $is_link      If this is a link or not
     * @param   int        $page_number  The page number to use (if a link)
     * @return  void
     */
    public function build_li_element($class, $text, $is_link = false, $page_number = 0)
    {
        if($is_link == false)
        {
            $this->links_html .= '<li class="'.$class.'"><span>'.$text.'</span></li>'.PHP_EOL;
        }
        else
        {
            $this->links_html .= '<li class="'.$class.'"><a href="'.$this->build_link_url($page_number).'">'.$text.'</a></li>'.PHP_EOL;
        }
    }


    /**
     * build_links_first_prev(void)
     *
     * Builds (if required) the First/Prev links
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_first_prev()
    {
        /*
         * The 'First' link
         */
        if($this->options['show_links_first_last'] == true)
        {
            if($this->current_page == 1 && $this->options['show_links_first_last_if_dead'] == true)
            {
                $this->build_li_element($this->options['class_dead_links'], $this->options['text_first']);
            }
            elseif($this->current_page != 1)
            {
                $this->build_li_element($this->options['class_live_links'], $this->options['text_first'], true, 1);
            }

            $this->links_array['extras']['first'] = array(
                'page_number'     => 1,
                'is_current_page' => ($this->current_page == 1 ? 1 : 0),
                'link_url'        => $this->build_link_url(1),
                'label'           => $this->options['text_first']
            );
        }

        /*
         * The 'Previous' link
         */
        if($this->options['show_links_prev_next'] == true)
        {
            if($this->current_page == 1 && $this->options['show_links_prev_next_if_dead'] == true)
            {
                $this->build_li_element($this->options['class_dead_links'], $this->options['text_prev']);
            }
            elseif($this->current_page != 1)
            {
                $this->build_li_element($this->options['class_live_links'], $this->options['text_prev'], true, ($this->current_page - 1));
            }

            $this->links_array['extras']['previous'] = array(
                'page_number'     => ($this->current_page != 1 ? $this->current_page - 1 : 1),
                'is_current_page' => ($this->current_page == 1 ? 1 : 0),
                'link_url'        => ($this->current_page != 1 ? $this->build_link_url($this->current_page - 1) : $this->build_link_url(1)),
                'label'           => $this->options['text_prev']
            );
        }
    }


    /**
     * loop_through_links(int $start, int $finish)
     *
     * Loops through a given range of numbers and add's then as links in the html
     *
     * @access  protected
     * @param   int     $start   The number to start looping
     * @param   int     $finish  The number to finish looping
     * @return  void
     */
    protected function loop_through_links($start, $finish, $array_block_label = '')
    {
        $counter = $start;

        while($counter <= $finish)
        {
            if($this->options['current_page_is_link'] == false && $counter == $this->current_page)
            {
                $this->build_li_element($this->get_current_or_normal_class($counter), $counter);
            }
            else
            {
                $this->build_li_element($this->get_current_or_normal_class($counter), $counter, true, $counter);
            }

            $this->links_array['links'][$array_block_label][] = array(
                'page_number'     => $counter,
                'is_current_page' => ($counter == $this->current_page ? 1 : 0),
                'link_url'        => $this->build_link_url($counter)
            );

            $counter++;
        }
    }


    /**
     * add_ellipses(void)
     *
     * Add's an ellipses to the html
     *
     * @access  protected
     * @return  void
     */
    protected function add_ellipses()
    {
        $this->build_li_element($this->options['class_ellipses'], $this->options['text_ellipses']);
    }


    /**
     * build_links_skip_all_ellipses(void)
     *
     * Add all links, with no ellipses at all
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_skip_all_ellipses()
    {
        /*
         * If there's not enough links to have an ellipses in the set, just run through them all
         */
        $this->loop_through_links(1, $this->total_pages, 0);
    }


    /**
     * build_links_skip_first_ellipses(void)
     *
     * Add all links, without the first ellipses
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_skip_first_ellipses()
    {
        /*
         * Type 1 - skipping the first ellipses due to being low in the current page number
         */
        $this->loop_through_links(1, ($this->options['max_links_between_ellipses'] + $this->options['max_links_outside_ellipses']), 0);

        $this->add_ellipses();

        $this->loop_through_links((($this->total_pages - $this->options['max_links_outside_ellipses']) + 1), $this->total_pages, 1);
    }


    /**
     * build_links_dont_skip_ellipses(void)
     *
     * Add all links, with both sets of ellipses
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_dont_skip_ellipses()
    {
        /*
         * Type 2 - Current page is between both sets of ellipses
         */
        $this->loop_through_links(1, $this->options['max_links_outside_ellipses'], 0);

        $this->add_ellipses();

        $before_after = (($this->options['max_links_between_ellipses'] - 1) / 2);

        $this->loop_through_links(($this->current_page - $before_after), ($this->current_page + $before_after), 1);

        $this->add_ellipses();

        $this->loop_through_links((($this->total_pages - $this->options['max_links_outside_ellipses']) + 1), $this->total_pages, 2);
    }


    /**
     * build_links_dont_skip_ellipses(void)
     *
     * Add all links, without the last ellipses
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_skip_last_ellipses()
    {
        /*
         * Type 3 - skipping the last ellipses due to being high in the current page number
         */
        $this->loop_through_links(1, $this->options['max_links_outside_ellipses'], 0);

        $this->add_ellipses();

        $this->loop_through_links((($this->total_pages - ($this->options['max_links_between_ellipses'] + $this->options['max_links_outside_ellipses'])) + 1), $this->total_pages, 1);
    }


    /**
     * build_links_next_last(void)
     *
     * Builds (if required) the Next/Last links
     *
     * @access  protected
     * @return  void
     */
    protected function build_links_next_last()
    {
        /*
         * The 'Next' link
         */
        if($this->options['show_links_prev_next'] == true)
        {
            if($this->current_page == $this->total_pages && $this->options['show_links_prev_next_if_dead'] == true)
            {
                $this->links_html .= '<li  class="'.$this->options['class_dead_links'].'"><span>'.$this->options['text_next'].'</span></li>'.PHP_EOL;
            }
            elseif($this->current_page != $this->total_pages)
            {
                $this->links_html .= '<li class="'.$this->options['class_live_links'].'"><a href="'.$this->build_link_url($this->current_page + 1).'">'.$this->options['text_next'].'</a></li>'.PHP_EOL;
            }

            $this->links_array['extras']['next'] = array(
                'page_number'     => ($this->current_page != $this->total_pages ? $this->current_page + 1 : $this->total_pages),
                'is_current_page' => ($this->current_page == $this->total_pages ? 1 : 0),
                'link_url'        => ($this->current_page != $this->total_pages ? $this->build_link_url($this->current_page + 1) : $this->build_link_url($this->total_pages)),
                'label'           => $this->options['text_next']
            );
        }

        /*
         * The 'Last' link
         */
        if($this->options['show_links_first_last'] == true)
        {
            if($this->current_page == $this->total_pages && $this->options['show_links_first_last_if_dead'] == true)
            {
                $this->links_html .= '<li class="'.$this->options['class_dead_links'].'"><span>'.$this->options['text_last'].'</span></li>'.PHP_EOL;
            }
            elseif($this->current_page != $this->total_pages)
            {
                $this->links_html .= '<li class="'.$this->options['class_live_links'].'"><a href="'.$this->build_link_url($this->total_pages).'">'.$this->options['text_last'].'</a></li>'.PHP_EOL;
            }

            $this->links_array['extras']['last'] = array(
                'page_number'     => $this->total_pages,
                'is_current_page' => ($this->current_page == $this->total_pages ? 1 : 0),
                'link_url'        => $this->build_link_url($this->total_pages),
                'label'           => $this->options['text_last']
            );
        }
    }

}
class paginationException extends Exception
{


    /**
     * __construct(string $message)
     *
     * Prepare the new exception class
     *
     * @access  protected
     * @param   string     $message  The exception message
     * @return  void
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }


    /**
     * __toString(void)
     *
     * Return the message for the exception
     *
     * @access  protected
     * @return  string     The exception message
     */
    public function __toString()
    {
        return '<strong>Pagination Class Error:</strong> '.$this->message.PHP_EOL;
    }
}


?>
