<?php

namespace Se09deluca\HandoffImageUpload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Se09deluca\HandoffImageUpload\HandoffImageUpload
 */
class HandoffImageUpload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Se09deluca\HandoffImageUpload\HandoffImageUpload::class;
    }
}
