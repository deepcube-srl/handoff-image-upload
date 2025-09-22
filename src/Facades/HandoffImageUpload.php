<?php

namespace Deepcube\HandoffImageUpload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Deepcube\HandoffImageUpload\HandoffImageUpload
 */
class HandoffImageUpload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Deepcube\HandoffImageUpload\HandoffImageUpload::class;
    }
}
