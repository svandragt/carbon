<?php

use Michelf\MarkdownExtra;

class ModelPost extends Cuttlefish\Model
{

    public $model = array(
        'eno'          => 'metadata',
        'markdown|html' => 'content',
    );

    /**
     * @return int
     */
    public function sortByPublished($a, $b)
    {
        return strcmp($b->metadata->Published, $a->metadata->Published);
    }

    /**
     * @return self
     */
    public function contents($records)
    {
        $loaded_classes = array(
            'mdep' => new MarkdownExtra(),
            'spyc' => new Spyc(),
        );
        foreach ($records as $record) {
            $this->contents[] = $this->listContents($record, $loaded_classes);
        }
        usort($this->contents, array( $this, 'sortByPublished' ));

        return $this;
    }
}
