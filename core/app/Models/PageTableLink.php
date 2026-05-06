<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageTableLink extends Model
{
    protected $fillable = ['page_slug', 'table_name'];
}
