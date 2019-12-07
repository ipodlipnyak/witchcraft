<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class InputStatuses extends Model
{

    const NOT_CHECKED = null;

    const READY = 1;

    const BROKEN = 2;

    const WRONG_RATIO = 3;

    const WRONG_CODEC = 4;
}
