<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model {
    protected $guarded = [];
    public static function log($action, $description, $entityType = null, $entityId = null, $user = null) {
        self::create([
            'user_id' => $user ? $user->id : (auth()->id() ?? null),
            'user_name' => $user ? $user->name : (auth()->user()->name ?? 'Hệ thống'),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'description' => $description,
        ]);
    }
}