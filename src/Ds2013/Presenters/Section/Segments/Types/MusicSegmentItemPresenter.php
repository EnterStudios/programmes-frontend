<?php
declare(strict_types=1);

namespace App\Ds2013\Presenters\Section\Segments\Types;

class MusicSegmentItemPresenter extends AbstractSegmentItemPresenter
{
    public function getType(): string
    {
        return 'music';
    }
}
