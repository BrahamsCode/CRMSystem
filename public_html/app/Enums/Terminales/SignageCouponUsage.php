<?php

namespace App\Enums\Terminales;

/** De qué lista del programa sale el cupón (tb_mycoupon_master_cds y variantes) */
enum SignageCouponUsage: int
{
    case General = 1;  // tb_mycoupon_master_cds
    case Felica = 2;   // feli_tb_mycoupon_master_cds
    case Mypage = 3;   // mypage_tb_mycoupon_master_cds
    case System = 4;   // system_tb_mycoupon_master_cds
}
