# PDO and MySQLi PHP Pagination Class

PDO MySQLi Pagination class is a PHP pagination class that will work with either PDO, or MySQLi. The outputted pagination is served in an Unordered List, and the options are extensive.

### Contribute

I'd love to see other people's contributions, so please fork away and submit any pull requests!

## Options

All you need to do is include the file, set the options, and then you can work with the data as is. Here's the different options that are available:

```php
<?
/* 
 * This is a list of all of the available options with the defaults: 
 */  
$options = array(  
    'results_per_page'              => 10,  
    'max_pages_to_fetch'            => 1000000,  
    'url'                           => '',  
    'url_page_number_var'           => '*VAR*',  
    'text_prev'                     => '� Prev',  
    'text_next'                     => 'Next �',  
    'text_first'                    => '� First',  
    'text_last'                     => 'Last �',  
    'text_ellipses'                 => '...',  
    'class_ellipses'                => 'ellipses',  
    'class_dead_links'              => 'dead-link',  
    'class_live_links'              => 'live-link',  
    'class_current_page'            => 'current-link',  
    'class_ul'                      => 'pagination',  
    'show_links_first_last'         => true,  
    'show_links_prev_next'          => true,  
    'show_links_first_last_if_dead' => true,  
    'show_links_prev_next_if_dead'  => true,  
    'max_links_between_ellipses'    => 7,      // This MUST be an odd number, io things will break
    'max_links_outside_ellipses'    => 2,  
    'db_conn_type'                  => 'pdo',  // Can be either: 'mysqli' or 'pdo'
    'db_handle'                     => 0  
    'named_params'                  => false,  
    'using_bound_params'            => false  
);
?>
```

Here's the full list of options, and the descriptions for each:

* __results_per_page__: The total number of results to show per page
* __max_pages_to_fetch__: You can limit the number of pages to fetch in total here
* __url__: The base URL to use in links
* __url_page_number_var__: The variable which will be replaced with the page numbers in URL's
* __text_prev__: The text for the 'Prev' link
* __text_next__: The text for the 'Next' link
* __text_first__: The text for the 'First' link
* __text_last__: The text for the 'Last' link
* __text_ellipses__: The text to use for the ellipses between the links
* __class_ellipses__: The CSS class to apply to the ellipses elements
* __class_dead_links__: The class to apply to dead links (i.e, if your on page 1, this would be applied to the 'Prev' link)
* __class_live_links__: The class to apply to any regular links
* __class_current_page__: The class to apply to the current page link
* __class_ul__: The class to apply to the whole UL
* __show_links_first_last__: Set to 1 to show the 'first/last' links
* __show_links_prev_next__: Set to 1 to show the 'prev/next' links
* __show_links_first_last_if_dead__: If the 'first/last' links are dead, set to 1 to still show them
* __show_links_prev_next_if_dead__: If the 'prev/next' links are dead, set to 1 to still show them
* __max_links_between_ellipses__: The total number of links to show on the indise of the ellipses (MUST be an odd number)
* __max_links_outside_ellipses__: The total number of links to show on the outside of the ellipses
* __db_conn_type__: Either 'pdo' (Default) or 'mysqli'
* __db_handle__: The database handle object
* __named_params__: An array for named params (See below)
* __using_bound_params__: Set to true if you are using bound params in PDO

## Using the class

To use the class, you must pass at least 2 options if using PDO, or 3 if using MySQLi. The URL for the pagination link, and the Database Object (Handle). If you are using MySQLi instead of PDO, you must also define that in the options.

When using PDO you can call the class using a regular query, or you can use named params or bind params. After the full example below, you can see how to use the named params and bound params styles.

Here's a full example on how to use the class, using PDO:

```php
<?
/* 
 * Include the class 
 */  
require_once('path/to/pagination.php');  
  
  
/* 
 * Set the current page number 
 */  
if(isset($_GET['page']))  
{  
    $page = $_GET['page'];  
}  
else  
{  
    $page = 1;  
}  
  
  
/* 
 * Connect to the database and create the handle 
 */  
try  
{  
    $dbh = new PDO('mysql:host=my.database.hostname;dbname=dbname', 'username', 'password');  
}  
catch (PDOException $e)  
{  
    echo 'Error!: '.$e->getMessage();  
    die();  
}  
  
  
/* 
 * Set the options
 */  
$options = array(  
    'url'        => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'  => $dbh  
);  
  
  
/* 
 * Call the class, the var's are: 
 * 
 * pagination(int $surrent_page, string $query, array $options) 
 */  
$pagination = new pagination($page, 'SELECT some_column FROM some_table ORDER BY some_other_column', $options);  
  
  
/* 
 * If all was successful, we can do something with our results 
 */  
if($pagination->success == true)  
{  
    /* 
     * Get the results 
     */  
    $result = $pagination->resultset->fetchAll();  
      
    foreach($result as $row)  
    {  
        echo '<p>'.$row['some_column'].'</p>';  
    }  
      
      
    /* 
     * Show the paginationd links ( 1 2 3 4 5 6 7 ) etc. 
     */  
    echo $pagination->links_html;  
      
      
    /* 
     * Get the total number if pages if you like 
     */  
    echo $pagination->total_pages;  
      
      
    /* 
     * Get the total number of results if you like 
     */  
    echo $pagination->total_results;  
}
?>
```

## Named Params in PDO

When using named params, you simply need to add the array to the options like so:


```php
<?
$options = array(  
    'url'          => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'    => $dbh,  
    'named_params' => array(':param_a' => 'foo', ':param_b' => 'bar')  
);
?>
```

## Bind Params in PDO

Binding params work's a little differently, you need to tell the class that you'll want to be binding params, and then bind them - the class will not execute the query automatically like it does in all other ways of functioning. I have kept the naming convention of bindParam to help make it easier to use the system without having to remember what the method names are. Once you're done binding, you can then execute manually:


```php
<?
$options = array(  
    'url'                => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'          => $dbh,  
    'using_bound_params' => true  
);  
  
$pagination = new pagination($page, 'SELECT * FROM table WHERE field_a = :param_a AND field_b = :param_b', $options);  
  
$pagination->bindParam(':param_a', 'foo', PDO::PARAM_STR, 12);  
$pagination->bindParam(':param_b', 'bar');  
  
$pagination->execute();
?>
```


## License

Copyright (c) 2012 Ben Griffiths

Licensed under the MIT License (http://www.opensource.org/licenses/mit-license.php)