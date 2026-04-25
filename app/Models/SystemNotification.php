<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'source_id',
        'source_type',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get unread count for a user
     */
    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get recent notifications for a user
     */
    public static function getRecent(int $userId, int $limit = 10)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create notification for multiple users
     */
    public static function createForUsers(array $userIds, array $data): void
    {
        foreach ($userIds as $userId) {
            self::create(array_merge($data, ['user_id' => $userId]));
        }
    }

    /**
     * Create sale return notification for super admins
     */
    public static function createSaleReturnNotification($saleReturn, $sale): void
    {
        // Get all super admin users
        $superAdmins = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->pluck('id')->toArray();

        if (empty($superAdmins)) {
            return;
        }

        $customer = \App\Models\Customer::find($saleReturn->customer);
        $customerName = $customer ? $customer->name : 'Unknown Customer';
        
        $data = [
            'title' => '🔄 New Sale Return Request',
            'message' => "Sale Return #{$saleReturn->id} created for Invoice #{$sale->invoice_no} by {$customerName}. Amount: PKR " . number_format($saleReturn->total_net, 2),
            'type' => 'sale_return',
            'source_id' => $saleReturn->id,
            'source_type' => 'App\Models\SalesReturn',
            'action_url' => route('sale.return.detail', $saleReturn->id),
            'is_read' => false,
        ];

        self::createForUsers($superAdmins, $data);
    }
}
