<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Permission;
use App\Models\RolePermission;

class PermissionController extends Controller {

    public function index(): void {
        $this->requireAuth(['owner', 'admin']);

        $rp = new RolePermission();
        $menus = Permission::menus();
        $roles = ['admin', 'cashier'];

        $per = [];
        foreach ($roles as $role) {
            $per[$role] = $rp->isConfigured($role) ? $rp->allowedKeys($role) : Permission::defaultFor($role);
        }

        // Grouping menus utk tampilan matrik
        $groups = [];
        foreach ($menus as $key => $menu) {
            $groups[$menu['group']][] = ['key' => $key, 'label' => $menu['label'], 'url' => $menu['url']];
        }

        $this->view('admin.permissions.index', [
            'groups' => $groups,
            'roles'  => $roles,
            'per'    => $per,
        ], 'admin');
    }

    public function save(): void {
        $this->requireAuth(['owner', 'admin']);

        $rp = new RolePermission();
        $menus = Permission::menus();
        $allKeys = array_keys($menus);
        $roles = ['admin', 'cashier'];

        $posted = $_POST['perm'] ?? [];
        foreach ($roles as $role) {
            $allowed = [];
            if (isset($posted[$role]) && is_array($posted[$role])) {
                foreach ($posted[$role] as $key => $v) {
                    if (in_array($key, $allKeys, true)) {
                        $allowed[] = $key;
                    }
                }
            }
            $allowed = array_values(array_unique($allowed));
            $rp->saveRole($role, $allKeys, $allowed);
        }

        // Invalidasi cache in-memory supaya langsung berlaku
        Permission::resetCache();
        $this->setFlash('success', 'Hak akses menu berhasil diperbarui!');
        $this->redirect('admin/permissions');
    }
}