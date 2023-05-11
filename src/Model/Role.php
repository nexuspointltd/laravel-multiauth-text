<?php

namespace Bitfumes\Multiauth\Model;

use Illuminate\Database\Eloquent\Model;

use DB;

class Role extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($role) {
            if ($role->admins()->count() > 0) {
                throw new \Exception('Can not delete, Role is assigned to Admins.');
            }
        });
    }

    protected $fillable = ['name'];

    public function admins()
    {
        $adminModel = config('multiauth.models.admin');
        return $this->belongsToMany($adminModel);
    }

    public function permissions()
    {
        $permissionModel = config('multiauth.models.permission');
        return $this->belongsToMany($permissionModel);
    }

    public function addPermission($permission_ids)
    {
        $this->permissions()->attach($permission_ids);
    }

    public function removePermission($permission_ids)
    {
        $this->permissions()->detach($permission_ids);
    }

    public function syncPermissions($permission_ids)
    {
        $this->permissions()->sync($permission_ids);
    }

    public function hasPermission($permission)
    {
        if (is_numeric($permission)) {
            return $this->permissions->contains('id', $permission);
        }
        return $this->permissions->contains('name', $permission);
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = strtolower($name);
    }

    public function getRoles($id) {
        $roles = DB::table('admin_role')
                    ->join('roles', 'roles.id', '=', 'admin_role.role_id')
                    ->select('roles.name', 'admin_role.role_id')
                    ->groupBy('roles.name')
                    ->where('admin_role.admin_id',   $id )
                    ->get();

        return $roles;
    }

}
