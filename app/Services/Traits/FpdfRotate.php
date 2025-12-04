<?php

namespace App\Services\Traits;

trait FpdfRotate
{
    protected $angle = 0;

    function ApplyRotation($angle, $x = -1, $y = -1)
    {
        if ($x == -1) $x = $this->GetX();
        if ($y == -1) $y = $this->GetY();
        if ($this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;

        if ($angle != 0) {
            $angle *= M_PI/180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;

            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.5F %.5F cm',
                $c, $s, -$s, $c,
                $cx - $cx*$c + $cy*$s,
                $cy - $cx*$s - $cy*$c
            ));
        }
    }

    function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}
