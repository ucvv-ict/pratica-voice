<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use App\Services\Traits\FpdfRotate;

class FpdiExtended extends Fpdi
{
    use FpdfRotate;
}
