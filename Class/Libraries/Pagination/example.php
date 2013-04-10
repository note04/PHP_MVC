<?php
/**
 *$num_rows =  First make a query to get the number of the results
 */
$num_rows = 50;
include('paginator.class.php');
$pages = new Paginator;

echo'<pre/>';print_r( $pages->getPaginateData($_GET['page'],$num_rows) );
echo $pages->displayHtmlPages($_GET['page'],$num_rows);

