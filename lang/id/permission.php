<?php

return [
    'User' => 'Pengguna',
    'Role' => 'Peran',
    'Permission' => 'Perizinan',
    'role_information' => 'Informasi Peran',
    'View Any' => 'Lihat Semua',
    'View' => 'Lihat',
    'Create' => 'Tambah',
    'Update' => 'Ubah',
    'Delete' => 'Hapus',
    'Restore' => 'Pulihkan',
    'Force Delete' => 'Hapus Permanen',
    'Role Name' => 'Nama Peran',
    'Enter Role Name' => 'Masukkan Nama Peran',
    'enable_role' => 'Aktifkan/Non-Aktifkan Semua Perizininan Untuk Role Ini.',
    'select_all' => 'Pilih Semua',
    'helper_text_permission' =>
        'Masukkan nama izin dengan format seperti <strong>View Any User</strong>, <strong>Create User</strong>, atau <strong>Delete User</strong>.
        <br><strong>Catatan:</strong> Gantilah kata <strong>"User"</strong> dengan entitas yang sesuai, misalnya <strong>View Any Order</strong> atau <strong>Create Product</strong>.
        <br><strong>Wajib:</strong> Setiap entitas harus memiliki <strong>7 izin</strong> berikut dengan format yang <u>persis</u> seperti di bawah ini:<br>
        <ul>
            <li><strong>View Any</strong> - Melihat semua data</li>
            <li><strong>View</strong> - Melihat detail data</li>
            <li><strong>Create</strong> - Menambahkan data baru</li>
            <li><strong>Update</strong> - Mengubah data</li>
            <li><strong>Delete</strong> - Menghapus data</li>
            <li><strong>Restore</strong> - Mengembalikan data yang terhapus</li>
            <li><strong>Force Delete</strong> - Menghapus data secara permanen</li>
        </ul>
        <br><strong>Penting:</strong> Gunakan format yang benar agar sistem dapat mengenali izin dengan tepat.',
];
