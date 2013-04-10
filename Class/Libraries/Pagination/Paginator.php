<?php
/*
 * PHP Pagination Class
 * @author admin@catchmyfame.com - http://www.catchmyfame.com
 * @version 2.0.0
 * @date October 18, 2011
 * @copyright (c) admin@catchmyfame.com (www.catchmyfame.com)
 * @license CC Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0) - http://creativecommons.org/licenses/by-sa/3.0/
 * license says that you are free to  — to copy, distribute and transmit the work
										to Remix — to adapt the work
										to make commercial use of the work
 */
 
 /**
 * EDITED BY Halamgean Daniel - Romania (RO) 
 * PHP Pagination Class 
 * @author (edited by : halmageandaniel.com ) - http://www.businesswall.net
 * @version 2.0.1
 * @date Mar 23, 2013
 * Changes that were made at this class: - some function were stripped out, variables added,
 * a new way of getting data added, array is returned. 
 * license says that you are free to  — to copy, distribute and transmit the work
										to Remix — to adapt the work
										to make commercial use of the work
  * HOW TO USE *
  $pages = new Paginator;
  $num_rows = 50;//Your query to get the COUNT of rows
  echo'<pre/>';print_r( $pages->getPaginateData($_GET['page'],$num_rows) ); //this will display the array
  echo $pages->displayHtmlPages($_GET['page'],$num_rows); //this will display the resulted HTML
  */
class Paginator
{
	/**
	 * $current_page - is the current page, that is set when
	 * the function "paginate" is called, for security, default value 1
	 */
	public $current_page = 1;
	/**
	 * $num_page - total number of pages
	 */
	public $num_pages;
	/**
	 * $return - the string that is created after the call
	 * of paginate function, this variable contain html
	 */
	public $return;
	/**
	 * $mid_range - sets how many pages will be displayed 
	 * in the middle  ( around current page )
	 */
	public $mid_range = 7;
	/**
	 * $items_per_page - sets how many results will be displayed 
	 * on one single page, as default is set with value 3
	 */
	public $items_per_page = 3;
	/**
	 * $items_total - must be set when paginate function is called,
	 * its value is the total results that are returned from query
	 */
	public $items_total = 0;
	/**
	 * $data - the array that is returned at the end of 
	 * function "paginate" , the array contain information about
	 * the current page, next page, etc
	 */
	public $data = array();
	
	
	
	
	/**
	 * the constructer
	 */
	public function __construct()
	{
	}
	/**
	 * Paginate - this function creates array with information about the current page, next page, etc 
	 * ( this was added for users who use template class to display Html code ) ,$this->data
	 * and a string that is in fact an Html that can be just displayed in fronted. $$this->return
	 */
	private function paginate($page = 1,$items_total = 0)
	{
		$this->num_pages = ceil($items_total/$this->items_per_page);
		
		$this->current_page = (isset($page) && $page > 0 && $page <= $this->num_pages) ? (int) $page : 1 ; // must be numeric > 0
		$prev_page = $this->current_page-1;
		$next_page = $this->current_page+1;
		/** edited by halmageandaniel@yahoo.com **/
		$this->data['totalRows'] = $items_total;
		$this->data['totalPages'] = $this->num_pages;
		$this->data['results'] = $this->items_total;
		$this->data['previous'] = $prev_page;
		$this->data['current'] = $this->current_page;

		$this->data['next'] =  $next_page;
		$this->data['last'] = $this->num_pages;
		/** halmageandaniel@yahoo.com */
		
		if($this->num_pages > 10)
		{
			$this->return = ($this->current_page > 1 && $this->items_total >= 10) ? "<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$prev_page\">&laquo; Previous</a> ":"<span class=\"inactive\" href=\"#\">&laquo; Previous</span> ";

			$this->start_range = $this->current_page - floor($this->mid_range/2);
			$this->end_range = $this->current_page + floor($this->mid_range/2);

			if($this->start_range <= 0)
			{
				$this->end_range += abs($this->start_range)+1;
				$this->start_range = 1;
			}
			if($this->end_range > $this->num_pages)
			{
				$this->start_range -= $this->end_range-$this->num_pages;
				$this->end_range = $this->num_pages;
			}
			$this->range = range($this->start_range,$this->end_range);

			for($i=1;$i<=$this->num_pages;$i++)
			{
				if($this->range[0] > 2 && $i == $this->range[0]) 
				{
					/** edited by halmageandaniel@yahoo.com **/
					$this->data['pages'][] = "...";
					/** same with **/
					$this->return .= "...";
				}
				// loop through all pages. if first, last, or in range, display
				if($i==1 || $i==$this->num_pages || in_array($i,$this->range))
				{
					$this->data['pages'][] =  $i;
					$this->return .= ($i == $this->current_page) ? "<a title=\"Go to page $i of $this->num_pages\" class=\"current\" href=\"#\">$i</a> ":"<a class=\"paginate\" title=\"Go to page $i of $this->num_pages\" href=\"$_SERVER[PHP_SELF]?page=$i\">$i</a> ";
				}
				if($this->range[$this->mid_range-1] < $this->num_pages-1 && $i == $this->range[$this->mid_range-1])
				{	
				 	/** edited by halmageandaniel@yahoo.com **/
					$this->data['pages'][] = "...";
					/** same with **/
					$this->return .= "...";
				}
			}
			$this->return .= (($this->current_page < $this->num_pages && $this->items_total >= 10) And ($page != 'All') And $this->current_page > 0) ? "<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$next_page\">Next &raquo;</a>\n":"<span class=\"inactive\" href=\"#\">&raquo; Next</span>\n";
			
		}
		else
		{
			for($i=1;$i<=$this->num_pages;$i++)
			{
				$this->data['pages'][] = $i; 
				$this->return .= ($i == $this->current_page) ? "<a class=\"current\" href=\"#\">$i</a> ":"<a class=\"paginate\" href=\"$_SERVER[PHP_SELF]?page=$i\">$i</a> ";
			}
		}
	}
	/**
	 * Returns the string that is the result of the paginator
	 */
	public function displayHtmlPages($page = 1,$items_total = 0)
	{
		$this->paginate($page,$items_total);
		return $this->return;
	}
	/**
	 * Returns an array paginator data
	 */
	public function getPaginateData( $page = 1,$items_total = 0 )
	{
		$this->paginate($page,$items_total);
		return $this->data;
	}
}