<?php

return [
    'User' => 'User',
    'Role' => 'Role',
    'Permission' => 'Permission',
    'role_information' => 'Role Information',
    'View Any' => 'View Any',
    'View' => 'View',
    'Create' => 'Create',
    'Update' => 'Update',
    'Delete' => 'Delete',
    'Restore' => 'Restore',
    'Force Delete' => 'Force Delete',
    'Role Name' => 'Role Name',
    'Enter Role Name' => 'Enter Role Name',
    'enable_role' => 'Enables/Disables all Permissions for this role.',
    'select_all' => 'Select All',
    'helper_text_permission' =>
        'Enter the permission name in the format like <strong>View Any User</strong>, <strong>Create User</strong>, or <strong>Delete User</strong>.
        <br><strong>Note:</strong> Replace <strong>"User"</strong> with the appropriate entity, such as <strong>View Any Order</strong> or <strong>Create Product</strong>.
        <br><strong>Required:</strong> Each entity must have the following <strong>7 permissions</strong> with the exact format below:<br>
        <ul>
            <li><strong>View Any</strong> - View all data</li>
            <li><strong>View</strong> - View data details</li>
            <li><strong>Create</strong> - Add new data</li>
            <li><strong>Update</strong> - Modify data</li>
            <li><strong>Delete</strong> - Delete data</li>
            <li><strong>Restore</strong> - Restore deleted data</li>
            <li><strong>Force Delete</strong> - Permanently delete data</li>
        </ul>
        <br><strong>Important:</strong> Use the correct format so the system can recognize the permission properly.',
];
