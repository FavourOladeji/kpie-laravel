<?php

namespace App\Enums;

enum CurrencyType: string {
    case Fiat = 'fiat';
    case Crypto = 'crypto';
}
