<?php

namespace Cuttlefish\Blog;

use Configuration;
use Cuttlefish\Controller;
use Cuttlefish\Feed;
use Cuttlefish\Files;

class ControllerFeed extends Controller
{
    // single feed
    /**
     * @return void
     */
    public function records()
    {
        $limit         = Configuration::POSTS_HOMEPAGE;
        $content_dir = Configuration::CONTENT_FOLDER . '/post';
        $Records       = new Files($content_dir, $this->ext);
        $this->records = $Records->limit($limit + 5);
    }

    /**
     * @return void
     */
    public function model()
    {
        $Model       = new ModelPost($this->records);
        $this->Model = $Model->limit(10);
    }

    /**
     * @return void
     */
    public function view()
    {
        parent::view();
        $this->View = new Feed($this->Model->contents);
    }
}
