<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;

class InvalidOrderTransition extends FreightException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self('تغییر وضعیت سفارش از «'.$from->label().'» به «'.$to->label().'» مجاز نیست.');
    }
}
