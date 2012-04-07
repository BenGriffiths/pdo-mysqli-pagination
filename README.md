# PDO and MySQLi Pagination Class

PDO MySQLi Pagination class is a pagination class that will work with either PDO, or MySQLi. There outputted pagination is served in an Unordered List, and the options are extensive.

## Options

All you need to do is include the file, set the options, and then you can work with the data as is. Here's the different options that are available:

```php
/* 
 * This is a list of all of the available options with the defaults: 
 */  
$options = array(  
    'results_per_page'              => 10,  
    'max_pages_to_fetch'            => 1000000,  
    'url'                           => '',  
    'url_page_number_var'           => '*VAR*',  
    'text_prev'                     => '« Prev',  
    'text_next'                     => 'Next »',  
    'text_first'                    => '« First',  
    'text_last'                     => 'Last »',  
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
    'max_links_between_ellipses'    => 7,  
    'max_links_outside_ellipses'    => 2,  
    'db_conn_type'                  => 'pdo',  /* Can be either: 'mysqli' or 'pdo' */  
    'db_handle'                     => 0  
    'named_params'                  => false,  
    'using_bound_params'            => false  
);
```

## Using the class

To use the class, you must pass at least 2 options if using PDO, or 3 if using MySQLi. The URL for the paginated link, and the Database Object (Handle). If you are using MySQLi instead of PDO, you must also define that in the options.
When using PDO you can call the class using a regular query, or you can use named params or bind params. After the full example below, I'll show you how to use the named params and bound params styles.
Here's a full example on how to use the class, using PDO:

```php
/* 
 * Include the class 
 */  
require_once('path/to/paginate.php');  
  
  
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
 * This is a list of all of the available options with the defaults: 
 */  
$options = array(  
    'url'        => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'  => $dbh  
);  
  
  
/* 
 * Call the class, the var's are: 
 * 
 * paginate(int $surrent_page, string $query, array $options) 
 */  
$paginate = new paginate($page, 'SELECT some_column FROM some_table ORDER BY some_other_column', $options);  
  
  
/* 
 * If all was successful, we can do something with our results 
 */  
if($paginate->success == true)  
{  
    /* 
     * Get the results 
     */  
    $result = $paginate->resultset->fetchAll();  
      
    foreach($result as $row)  
    {  
        echo '<p>'.$row['some_column'].'</p>';  
    }  
      
      
    /* 
     * Show the paginated links ( 1 2 3 4 5 6 7 ) etc. 
     */  
    echo $paginate->links_html;  
      
      
    /* 
     * Get the total number if pages if you like 
     */  
    echo $paginate->total_pages;  
      
      
    /* 
     * Get the total number of results if you like 
     */  
    echo $paginate->total_results;  
}  
```

## Named Params in PDO

When using named params, you simply need to add the array to the options like so:


```php
$options = array(  
    'url'          => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'    => $dbh,  
    'named_params' => array(':param_a' => 'foo', ':param_b' => 'bar')  
);
```

## Bind Params in PDO

Binding params work's a little differently, you need to tell the class that you'll want to be binding params, and then bind them - the class will not execute the query automatically like it does in all other ways of functioning. I have kept the naming convention of bindParam to help make it easier to use the system without having to remember what the method names are. Once you're done binding, you can then execute manually:


```php
$options = array(  
    'url'                => 'http://www.mysite.com/mypage.php?page=*VAR*',  
    'db_handle'          => $dbh,  
    'using_bound_params' => true  
);  
  
$paginate = new paginate($page, 'SELECT * FROM table WHERE field_a = :param_a AND field_b = :param_b', $options);  
  
$paginate->bindParam(':param_a', 'foo', PDO::PARAM_STR, 12);  
$paginate->bindParam(':param_b', 'bar');  
  
$paginate->execute();
```