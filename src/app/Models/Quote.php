<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, HasCompany, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_SENT = 'sent';
    const STATUS_DEPOSIT_RECEIVED = 'deposit_received';

    protected $fillable = [
        'company_id',
        'user_id',
        'status',
        'client_name',
        'client_email',
        'service_date',
        'reference_url',
        'notes',
        'destination_name',
        'service_type',
        'mileage',
        'extra_km',
        'duration_hours',
        'extension_hours',
        'extra_travel_hours',
        'toll_cost',
        'pax_count',
        'experience_per_pax',
        'seasonality',
        'vehicle_fill',
        'vat_percentage',
        'card_fees_percentage',
        'surcharge_percentage',
        'travel_costs',
        'override_taxable',
        'taxable_transport',
        'taxable_experience',
        'surcharge_amount',
        'taxable_price',
        'taxable_price_rounded',
        'vat_amount',
        'cc_fees_amount',
        'final_price',
        'final_price_rounded',
        'client_price',
        'discount_percentage',
        'discount_name',
        'discount_amount',
        'discounted_taxable',
        'deposit_percentage',
        'deposit_taxable',
        'deposit_handling_fees',
        'deposit_total',
        'balance_taxable',
        'balance_handling_fees',
        'balance_card_fees',
        'sumup_config_id',
        'gmail_account_id',
        'email_template_id',
        'rendered_subject',
        'rendered_body_html',
        'sumup_checkout_id',
        'sumup_checkout_url',
        'gmail_draft_id',
        'gmail_thread_id',
        'service_id',
        'approved_at',
        'sent_at',
        'deposit_received_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'mileage' => 'decimal:2',
        'extra_km' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'extension_hours' => 'decimal:2',
        'extra_travel_hours' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'pax_count' => 'integer',
        'experience_per_pax' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'card_fees_percentage' => 'decimal:2',
        'surcharge_percentage' => 'decimal:2',
        'travel_costs' => 'decimal:2',
        'override_taxable' => 'decimal:2',
        'taxable_transport' => 'decimal:2',
        'taxable_experience' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'taxable_price' => 'decimal:2',
        'taxable_price_rounded' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'cc_fees_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'final_price_rounded' => 'decimal:2',
        'client_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discounted_taxable' => 'decimal:2',
        'deposit_percentage' => 'decimal:2',
        'deposit_taxable' => 'decimal:2',
        'deposit_handling_fees' => 'decimal:2',
        'deposit_total' => 'decimal:2',
        'balance_taxable' => 'decimal:2',
        'balance_handling_fees' => 'decimal:2',
        'balance_card_fees' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'deposit_received_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sumupConfig(): BelongsTo
    {
        return $this->belongsTo(SumupConfig::class);
    }

    public function gmailAccount(): BelongsTo
    {
        return $this->belongsTo(GmailAccount::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteEmailTemplate::class, 'email_template_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function stateTransitions(): HasMany
    {
        return $this->hasMany(QuoteStateTransition::class)->orderBy('created_at');
    }
}
