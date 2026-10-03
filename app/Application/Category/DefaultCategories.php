<?php

namespace App\Application\Category;

/** The shelves every new store starts with. The owner can rename or delete them. */
final class DefaultCategories
{
    public const SEMBAKO = 'Sembako (bahan pokok)';

    public const BUMBU = 'Bumbu dan bahan masak';

    public const MINUMAN = 'Minuman';

    public const SNACK = 'Makanan ringan (snack)';

    public const MANDI = 'Perlengkapan mandi dan kebersihan diri';

    public const RUMAH_TANGGA = 'Kebutuhan rumah tangga';

    public const ROKOK = 'Rokok';

    public const OBAT = 'Obat-obatan ringan';

    public const GAS = 'Gas dan bahan bakar';

    public const LAYANAN = 'Layanan tambahan (pulsa, token listrik, dll.)';

    public const SIAP_SAJI = 'Makanan dan minuman siap saji';

    public const NAMES = [
        self::SEMBAKO,
        self::BUMBU,
        self::MINUMAN,
        self::SNACK,
        self::MANDI,
        self::RUMAH_TANGGA,
        self::ROKOK,
        self::OBAT,
        self::GAS,
        self::LAYANAN,
        self::SIAP_SAJI,
    ];
}
