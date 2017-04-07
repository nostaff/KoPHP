<?php defined('SYS_PATH') or die('No direct script access.');

class PaginationController extends Controller 
{

    public function before()
    {
        $this->autoRender(FALSE);
    }   
     
	public function index()
	{
	    $paginator = Paginator::factory(1024);
	    $pages = $paginator->setItemCountPerPage(30)
	              ->setCurrentPageNumber($this->getRequest()->getParam('page', 1))
	              ->setPageRange(10)
	              ->getPages();
	              
	    $this->view = View::factory('pagination');
	    $this->view->assign('url', $this->getBaseUrl() . $this->getRequest()->uri() . '/page/');
	    $this->view->assign('pages', $pages);
	    $this->view->render();
	}

}