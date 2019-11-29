<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectStatuses extends Model
{

    const TEMPLATE = 1;

    const TASK = 2;

    const INWORK = 3;

    const DONE = 4;

    const BROKEN = 5;
}
