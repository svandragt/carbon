<?php

namespace Cuttlefish\Blog;

use Cuttlefish\Model;

class ModelPost extends Model
{
    public $required_fields = ['metadata' => ['published']];

    public $model = array(
        'metadatareader'    => 'metadata',
        'markdown'     => 'content',
    );

    /**
     * @return int
     */
    public function sortByPublished($a, $b)
    {
        return strcmp($b->metadata->published, $a->metadata->published);
    }

    /**
     * @return self
     */
    public function contents($records)
    {
        foreach ($records as $record) {
            $this->contents[] = $this->listContents($record);
        }
        usort($this->contents, array( $this, 'sortByPublished' ));

        return $this;
    }
}
