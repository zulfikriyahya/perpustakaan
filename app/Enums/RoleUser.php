<?php

namespace App\Enums;

enum RoleUser: string
{
    case Siswa = 'siswa';
    case Pegawai = 'pegawai';
    case Pustakawan = 'pustakawan';
    case Admin = 'super_admin';
}
