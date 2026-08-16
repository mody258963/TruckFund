<?php

namespace Database\Seeders;

use App\Enums\AutoProductType;
use App\Models\AutoProduct;
use Illuminate\Database\Seeder;

class HeavyTruckCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->trucks() as $truck) {
            AutoProduct::query()->updateOrCreate(
                ['chassis' => $truck['chassis']],
                [
                    ...$truck,
                    'price' => 0,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Representative heavy-truck models and applications available in Egypt.
     * The chassis value is a catalog SKU, not a vehicle VIN.
     *
     * @return list<array{name: string, model: string, type: AutoProductType, brand: string, model_year: int, chassis: string}>
     */
    private function trucks(): array
    {
        return [
            ['brand' => 'Mercedes-Benz', 'model' => 'Actros', 'name' => 'Actros L', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-MB-ACTROS-L-2025-TH'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Arocs', 'name' => 'Arocs 4048 K', 'type' => AutoProductType::Tipper, 'model_year' => 2025, 'chassis' => 'EG-MB-AROCS-4048-2025-TI'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Arocs', 'name' => 'Arocs 4142 B', 'type' => AutoProductType::ConcreteMixer, 'model_year' => 2025, 'chassis' => 'EG-MB-AROCS-4142-2025-CM'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Atego', 'name' => 'Atego 1726', 'type' => AutoProductType::CargoBox, 'model_year' => 2024, 'chassis' => 'EG-MB-ATEGO-1726-2024-CB'],
            ['brand' => 'Volvo', 'model' => 'FH', 'name' => 'FH 500', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-VOLVO-FH500-2025-TH'],
            ['brand' => 'Volvo', 'model' => 'FMX', 'name' => 'FMX 460', 'type' => AutoProductType::Tipper, 'model_year' => 2025, 'chassis' => 'EG-VOLVO-FMX460-2025-TI'],
            ['brand' => 'Volvo', 'model' => 'FM', 'name' => 'FM 420', 'type' => AutoProductType::WaterTanker, 'model_year' => 2024, 'chassis' => 'EG-VOLVO-FM420-2024-WT'],
            ['brand' => 'Scania', 'model' => 'R', 'name' => 'R 500', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-SCANIA-R500-2025-TH'],
            ['brand' => 'Scania', 'model' => 'G', 'name' => 'G 460 XT', 'type' => AutoProductType::Tipper, 'model_year' => 2025, 'chassis' => 'EG-SCANIA-G460XT-2025-TI'],
            ['brand' => 'Scania', 'model' => 'P', 'name' => 'P 360', 'type' => AutoProductType::GarbageCompactor, 'model_year' => 2024, 'chassis' => 'EG-SCANIA-P360-2024-GC'],
            ['brand' => 'MAN', 'model' => 'TGX', 'name' => 'TGX 18.510', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-MAN-TGX18510-2025-TH'],
            ['brand' => 'MAN', 'model' => 'TGS', 'name' => 'TGS 41.480', 'type' => AutoProductType::Tipper, 'model_year' => 2025, 'chassis' => 'EG-MAN-TGS41480-2025-TI'],
            ['brand' => 'MAN', 'model' => 'TGS', 'name' => 'TGS 33.430', 'type' => AutoProductType::Crane, 'model_year' => 2024, 'chassis' => 'EG-MAN-TGS33430-2024-CR'],
            ['brand' => 'Iveco', 'model' => 'S-Way', 'name' => 'S-Way 480', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-IVECO-SWAY480-2025-TH'],
            ['brand' => 'Iveco', 'model' => 'T-Way', 'name' => 'T-Way 410', 'type' => AutoProductType::ConcreteMixer, 'model_year' => 2025, 'chassis' => 'EG-IVECO-TWAY410-2025-CM'],
            ['brand' => 'Iveco', 'model' => 'Eurocargo', 'name' => 'Eurocargo ML180', 'type' => AutoProductType::Refrigerated, 'model_year' => 2024, 'chassis' => 'EG-IVECO-ML180-2024-RF'],
            ['brand' => 'Isuzu', 'model' => 'GXZ', 'name' => 'GXZ 360', 'type' => AutoProductType::TractorHead, 'model_year' => 2024, 'chassis' => 'EG-ISUZU-GXZ360-2024-TH'],
            ['brand' => 'Isuzu', 'model' => 'FVM', 'name' => 'FVM 34', 'type' => AutoProductType::WaterTanker, 'model_year' => 2024, 'chassis' => 'EG-ISUZU-FVM34-2024-WT'],
            ['brand' => 'Isuzu', 'model' => 'FVR', 'name' => 'FVR 34', 'type' => AutoProductType::CargoBox, 'model_year' => 2024, 'chassis' => 'EG-ISUZU-FVR34-2024-CB'],
            ['brand' => 'Sinotruk HOWO', 'model' => 'T7H', 'name' => 'T7H 440', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-HOWO-T7H440-2025-TH'],
            ['brand' => 'Sinotruk HOWO', 'model' => 'NX', 'name' => 'NX 400', 'type' => AutoProductType::Tipper, 'model_year' => 2025, 'chassis' => 'EG-HOWO-NX400-2025-TI'],
            ['brand' => 'Sinotruk HOWO', 'model' => 'TX', 'name' => 'TX 380', 'type' => AutoProductType::FuelTanker, 'model_year' => 2024, 'chassis' => 'EG-HOWO-TX380-2024-FT'],
            ['brand' => 'Foton', 'model' => 'Auman', 'name' => 'Auman EST-A 490', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-FOTON-ESTA490-2025-TH'],
            ['brand' => 'Foton', 'model' => 'Auman', 'name' => 'Auman GTL 430', 'type' => AutoProductType::Tipper, 'model_year' => 2024, 'chassis' => 'EG-FOTON-GTL430-2024-TI'],
            ['brand' => 'FUSO', 'model' => 'FJ', 'name' => 'FJ 2528C', 'type' => AutoProductType::ConcreteMixer, 'model_year' => 2024, 'chassis' => 'EG-FUSO-FJ2528C-2024-CM'],
            ['brand' => 'FUSO', 'model' => 'FZ', 'name' => 'FZ 4928T', 'type' => AutoProductType::TractorHead, 'model_year' => 2024, 'chassis' => 'EG-FUSO-FZ4928T-2024-TH'],
            ['brand' => 'Renault Trucks', 'model' => 'T', 'name' => 'T 480', 'type' => AutoProductType::TractorHead, 'model_year' => 2025, 'chassis' => 'EG-RENAULT-T480-2025-TH'],
            ['brand' => 'Renault Trucks', 'model' => 'K', 'name' => 'K 440', 'type' => AutoProductType::Lowbed, 'model_year' => 2024, 'chassis' => 'EG-RENAULT-K440-2024-LB'],
            ['brand' => 'Hino', 'model' => '700', 'name' => '700 SS', 'type' => AutoProductType::TractorHead, 'model_year' => 2024, 'chassis' => 'EG-HINO-700SS-2024-TH'],
            ['brand' => 'Hino', 'model' => '700', 'name' => '700 FY', 'type' => AutoProductType::CarCarrier, 'model_year' => 2024, 'chassis' => 'EG-HINO-700FY-2024-CC'],
            ['brand' => 'UD Trucks', 'model' => 'Quester', 'name' => 'Quester CWE', 'type' => AutoProductType::ChemicalTanker, 'model_year' => 2024, 'chassis' => 'EG-UD-QUESTER-CWE-2024-CT'],
            ['brand' => 'UD Trucks', 'model' => 'Quester', 'name' => 'Quester CGE', 'type' => AutoProductType::Flatbed, 'model_year' => 2024, 'chassis' => 'EG-UD-QUESTER-CGE-2024-FB'],
            ['brand' => 'Isuzu', 'model' => 'FVR', 'name' => 'FVR 34 Vacuum', 'type' => AutoProductType::SewageVacuum, 'model_year' => 2024, 'chassis' => 'EG-ISUZU-FVR34-2024-SV'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Atego', 'name' => 'Atego 1730 Fire', 'type' => AutoProductType::FireTruck, 'model_year' => 2024, 'chassis' => 'EG-MB-ATEGO-1730-2024-FI'],
            ['brand' => 'MAN', 'model' => 'TGS', 'name' => 'TGS 26.400 Service', 'type' => AutoProductType::RoadMaintenance, 'model_year' => 2024, 'chassis' => 'EG-MAN-TGS26400-2024-RM'],
            ['brand' => 'Toyota', 'model' => 'Coaster', 'name' => 'Coaster', 'type' => AutoProductType::Microbus, 'model_year' => 2025, 'chassis' => 'EG-TOYOTA-COASTER-2025-MB'],
            ['brand' => 'King Long', 'model' => 'XMQ6706', 'name' => 'XMQ6706', 'type' => AutoProductType::Microbus, 'model_year' => 2024, 'chassis' => 'EG-KINGLONG-XMQ6706-2024-MB'],
            ['brand' => 'Yutong', 'model' => 'ZK6122H', 'name' => 'ZK6122H', 'type' => AutoProductType::Bus, 'model_year' => 2025, 'chassis' => 'EG-YUTONG-ZK6122H-2025-BU'],
            ['brand' => 'Golden Dragon', 'model' => 'XML6125', 'name' => 'XML6125', 'type' => AutoProductType::Bus, 'model_year' => 2024, 'chassis' => 'EG-GOLDENDRAGON-XML6125-2024-BU'],
            ['brand' => 'SDLG', 'model' => 'L956F', 'name' => 'L956F', 'type' => AutoProductType::Loader, 'model_year' => 2025, 'chassis' => 'EG-SDLG-L956F-2025-LD'],
            ['brand' => 'XCMG', 'model' => 'LW500FN', 'name' => 'LW500FN', 'type' => AutoProductType::Loader, 'model_year' => 2024, 'chassis' => 'EG-XCMG-LW500FN-2024-LD'],
        ];
    }
}
