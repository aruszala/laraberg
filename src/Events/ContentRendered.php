<?php

namespace aruszala\Laraberg\Events;

use Illuminate\Queue\SerializesModels;

use aruszala\Laraberg\Models\Content;

class ContentRendered
{
    use SerializesModels;

    public $content;

    /**
     * Create a new event instance
     * 
     * @param aruszala\Laraberg\Models\Content $content
     * @return void
     */
    public function __construct(Content $content)
    {
        $this->content = $content;
    }
}

