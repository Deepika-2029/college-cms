<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class V3Component extends Model
{
    protected $table = 'v3_components';
    protected $fillable = ['name', 'category', 'base_html', 'base_css', 'base_js'];
}
