<style type="text/css">
    
    body {
        font-family: courier new;
        font-size: 11px;
    }
    
    .pagination {
        padding-left: 0px;
        margin-left: 0px;
    }
    
    .pagination li {
        float: left;
        padding-right: 10px;
    }
    
    .pagination li a.current-link {
        font-weight: bold;
        text-decoration: none;
        color: #000000;
    }
    
</style><?


/*
* Show all errors (not required of course)
*/
ini_set('display_errors','On');
error_reporting(-1);


/*
* Include the pagination.php class file
*/
require_once('../src/pagination.php');


/*
* Connect to the database (Replacing the XXXXXX's with the correct details)
*/
try
{
    $dbh = new PDO('mysql:host='xxx';dbname='xxx, 'xxx', 'xxx');
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
    print "Error!: " . $e->getMessage() . "<br/>";
}


/*
* Get and/or set the page number we are on
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
* Set a few of the basic options for the class, replacing the URL with your own of course
*/
$options = array(
    'results_per_page' => 6,
    'url' => 'http://www.mysite.com/example_pdo.php?page=*VAR*',
    'db_handle' => $dbh
);


/*
* Create the pagination object
*/
try
{
    $paginate = new pagination($page, 'SELECT * FROM demo_table ORDER BY id', $options);
}
catch(paginationException $e)
{
    echo $e;
    exit();
}


/*
* If we get a success, carry on
*/
if($paginate->success == true)
{

    /*
* Fetch our results
*/
    $result = $paginate->resultset->fetchAll();

    /*
* Echo out the UL with the page links
*/
    echo '<p>'.$paginate->links_html.'</p>';

    /*
* Echo out the total number of results
*/
    echo '<p style="clear: left; padding-top: 10px;">Total Results: '.$paginate->total_results.'</p>';

    /*
* Echo out the total number of pages
*/
    echo '<p>Total Pages: '.$paginate->total_pages.'</p>';

    echo '<p style="clear: left; padding-top: 10px; padding-bottom: 10px;">-----------------------------------</p>';

    /*
* Work with our data rows
*/
    foreach($result as $row)
    {
        echo '<p>'.$row['demo_field'].', '.$row['demo_field_two'].'</p>';
    }

}


?>
