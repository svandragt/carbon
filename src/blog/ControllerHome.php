<?php

namespace Cuttlefish\Blog;

use Configuration;
use Cuttlefish\Controller;
use Cuttlefish\Files;
use Cuttlefish\Html;

class ControllerHome extends Controller
{
    // list of recent posts
    /**
     * @return void
     */
    public function records()
    {
        $limit         = Configuration::POSTS_HOMEPAGE;
        $content_dir = Configuration::CONTENT_FOLDER . '/posts';
        $Files         = new Files(array( 'url' => $content_dir ), $this->ext);
        $this->records = $Files->limit($limit + 5);
    }

    /**
     * @return void
     */
    public function model()
    {
        $Model       = new ModelPost($this->records);
        $this->Model = $Model->limit(Configuration::POSTS_HOMEPAGE);
    }

    /**
     * @return void
     */
    public function view()
    {
        parent::view();
        $this->View = new Html($this->Model->contents, array(
            'layout'     => 'layout.php',
            'controller' => 'home',
            'model'      => 'post',
        ));
    }
}
