<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMonthlyTarget extends Model
{
    protected $table = "agent_monthly_targets";
    protected $guarded = [];

    public function agent()
    {
        return $this->belongsTo(User::class)->where('is_agent', true);
    }
}
