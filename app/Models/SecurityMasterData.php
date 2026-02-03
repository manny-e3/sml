<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityMasterData extends Model
{
    protected $table = 'security_master_data';

    protected $fillable = [
        'category_id',
        'security_name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the market category
     */
    public function category()
    {
        return $this->belongsTo(MarketCategory::class, 'category_id');
    }

    /**
     * Get all field values for this security
     */
    public function fieldValues()
    {
        return $this->hasMany(SecurityMasterFieldValue::class, 'security_master_id');
    }

    /**
     * Get field values with field definitions
     */
    public function getFieldsWithValuesAttribute()
    {
        return $this->fieldValues()->with('field')->get()->map(function($fieldValue) {
            return [
                'field_id' => $fieldValue->field_id,
                'field_name' => $fieldValue->field->field_name,
                'field_type' => $fieldValue->field->field_type,
                'field_value' => $fieldValue->field_value,
                'required' => $fieldValue->field->required,
            ];
        });
    }
}
