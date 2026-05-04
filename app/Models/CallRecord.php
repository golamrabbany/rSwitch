<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallRecord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'sip_account_id', 'user_id', 'reseller_id', 'call_flow',
        'caller', 'callee', 'caller_id', 'src_ip', 'dst_ip',
        'incoming_trunk_id', 'outgoing_trunk_id', 'did_id', 'destination_sip_account_id',
        'destination', 'matched_prefix', 'rate_per_minute', 'connection_fee', 'rate_group_id',
        'call_start', 'call_end', 'duration', 'billsec', 'billable_duration',
        'total_cost', 'reseller_cost',
        'disposition', 'hangup_cause', 'status',
        'ast_channel', 'ast_dstchannel', 'ast_context', 'rated_at',
    ];

    protected function casts(): array
    {
        return [
            'call_start' => 'datetime',
            'call_end' => 'datetime',
            'rated_at' => 'datetime',
            'total_cost' => 'decimal:4',
            'reseller_cost' => 'decimal:4',
            'rate_per_minute' => 'decimal:6',
        ];
    }

    /**
     * Datetimes are stored in UTC (the Python engine writes UTC) but displayed
     * in the configured local timezone. The Carbon instance stays TZ-aware, so
     * Carbon math (diffForHumans, comparisons) still works against now().
     */
    protected function asDateTime($value)
    {
        $carbon = parent::asDateTime($value);

        return $carbon
            ? $carbon->setTimezone(config('app.display_timezone', config('app.timezone', 'UTC')))
            : $carbon;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function sipAccount(): BelongsTo
    {
        return $this->belongsTo(SipAccount::class);
    }

    public function incomingTrunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class, 'incoming_trunk_id');
    }

    public function outgoingTrunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class, 'outgoing_trunk_id');
    }

    public function did(): BelongsTo
    {
        return $this->belongsTo(Did::class);
    }

    public function destinationSipAccount(): BelongsTo
    {
        return $this->belongsTo(SipAccount::class, 'destination_sip_account_id');
    }

    public function scopeAnswered($query)
    {
        return $query->where('disposition', 'ANSWERED');
    }

    public function scopeRated($query)
    {
        return $query->where('status', 'rated');
    }

    public function scopeInternal($query)
    {
        return $query->where('call_flow', 'sip_to_sip');
    }

    public function scopeExternal($query)
    {
        return $query->whereIn('call_flow', ['sip_to_trunk', 'trunk_to_sip']);
    }
}
