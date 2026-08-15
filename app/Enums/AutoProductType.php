<?php

namespace App\Enums;

enum AutoProductType: int
{
    case TractorHead = 1;
    case CargoBox = 2;
    case Flatbed = 3;
    case Tipper = 4;
    case ConcreteMixer = 5;
    case WaterTanker = 6;
    case FuelTanker = 7;
    case ChemicalTanker = 8;
    case Refrigerated = 9;
    case Crane = 10;
    case GarbageCompactor = 11;
    case SewageVacuum = 12;
    case CarCarrier = 13;
    case Lowbed = 14;
    case FireTruck = 15;
    case RoadMaintenance = 16;

    public function label(): string
    {
        return __('enums.auto_product_type.'.$this->name);
    }
}
