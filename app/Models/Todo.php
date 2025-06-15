<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = [
        'title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority'
    ];
}
