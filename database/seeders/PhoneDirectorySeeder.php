<?php

namespace Database\Seeders;

use App\Models\PhoneBuilding;
use App\Models\PhoneEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Transcribed from the printed "Local Phone Numbers" sheets (Building A last
 * updated August 2026). The scans crop the left edge, so some floor names and
 * company prefixes are best guesses — fix them in /admin/phone-directory.
 * Only seeds when the directory is empty, so it never overwrites admin edits.
 */
class PhoneDirectorySeeder extends Seeder
{
    public function run(): void
    {
        if (PhoneBuilding::exists()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->sheets() as $buildingOrder => $sheet) {
                $building = PhoneBuilding::create([
                    'name' => $sheet['name'],
                    'trunkline' => $sheet['trunkline'],
                    'mobile' => $sheet['mobile'],
                    'sort_order' => $buildingOrder + 1,
                ]);

                foreach ($sheet['floors'] as $floorOrder => [$floorName, $accessCode, $entries]) {
                    $floor = $building->floors()->create([
                        'name' => $floorName,
                        'access_code' => $accessCode,
                        'sort_order' => $floorOrder + 1,
                    ]);

                    foreach ($entries as $entryOrder => [$department, $name, $local]) {
                        $floor->entries()->create([
                            'department' => $department,
                            'name' => $name,
                            'local' => $local,
                            'is_highlighted' => PhoneEntry::looksLikeManager($department),
                            'sort_order' => $entryOrder + 1,
                        ]);
                    }
                }

                // Saving floors/lines stamps today; restore the date on the printout.
                $building->update(['last_updated_on' => '2026-08-01']);
            }
        });
    }

    protected function sheets(): array
    {
        return [
            [
                'name' => 'Building A',
                'trunkline' => '8 521-8999',
                'mobile' => '0999-888-9333',
                'floors' => [
                    ['Ground Floor', null, [
                        ['ASST. ADMIN MANAGER', 'MAYETH MARQUEZ', '1113'],
                        ['TRUNKLINE', null, '0'],
                        ['LOBBY / RECEPTION', 'BEAH', '1111'],
                        ['INFORMATION', null, '1112'],
                        ['NURSE', 'PAM', '1015'],
                    ]],
                    ['2nd Floor', null, [
                        ['SECURITY OFFICE', null, '1211'],
                    ]],
                    ['3rd Floor', null, [
                        ['CCSI - GENERAL MANAGER', 'CRIS GARCIA', '1301'],
                        ['CCSI - TECH OPS MANAGER', 'ALLAN PAGUIO', '1303'],
                        ['CCSI - QC', 'DANSON', '1302'],
                        ['CCSI - ADMIN', 'RUZZEL', '1314'],
                        ['CCSI - CUSTOMER RELATIONS', 'JILL', '1316'],
                        ['CCSI - RDU', 'ARIEL', '1319'],
                        ['CCSI - TECHNICAL', 'SCOTT', '1317'],
                        ['QC/CM', 'LOVE', '1412'],
                        ['WAREHOUSE', 'HERBERT', '1312'],
                        ['R88 - SALES', 'HANNAH', '1318'],
                    ]],
                    ['5th Floor', null, [
                        ['WAREHOUSE', 'SHERWIN', '1511'],
                        ['WAREHOUSE', 'AZENITH / KAYLA / JACKIE', '1512'],
                        ['WAREHOUSE CLASS B', 'JAKE', '1513'],
                        ['LWI - WAREHOUSE', 'ELOY / THONG', '1513'],
                    ]],
                    ['6th Floor', null, [
                        ['PRODUCT DEV. MANAGER', 'JACK CATALIG', '1602'],
                        ['PDD', 'RICKY', '1603'],
                        ['PDD', 'MORRIS / TRISHA', '1611'],
                        ['PDD', 'ERIC / JOVAN', '1615'],
                        ['PDD', 'AERIEL / CIELO', '1616'],
                    ]],
                    ['7th Floor', null, [
                        ['ASST. VICE PRESIDENT - MIS / IT', 'HAMMURABI DE ROCA', '1802'],
                        ['MIS / IT MANAGER', 'HENRY MEDENILLA JR.', '1807'],
                        ['TRADE MARKETING MANAGER', 'JOBELLE TUPAZ - GO', '1803'],
                        ['SENIOR MARKETING MANAGER', 'ERIKA MAGHIRANG', '1811'],
                        ['ASST. MANAGER BRAND MARKETING', 'PRINCESS DANOS', '1820'],
                        ['ASST. MANAGER GRAPHICS AND VISUAL CREATIVES', 'PAULO RESOL', '1826'],
                        ['ASST. MANAGER VISUAL MERCHANDISING', 'CHRISTIAN AGULOS', '1830'],
                        ['AUDIT MANAGER', 'WALLY MERCADO', '1938'],
                        ['MIS / IT', 'ALFRED / ARVIN', '1813'],
                        ['MIS / IT', 'JEFF / REGGIE', '1814'],
                        ['MIS / IT', 'MON / JOEART', '1816'],
                        ['MIS / IT', 'MICH', '1817'],
                        ['MIS / IT', 'IZZA / ROBERT', '1828'],
                        ['CCTV ROOM', null, '1818'],
                    ]],
                    ['8th Floor', null, [
                        ['TRADE MARKETING', 'DOLLY / JAY AR / DENNIS', '1819'],
                        ['MARKETING', 'SIMOUN / STEPH', '1820'],
                        ['PR', 'JACOB', '1820'],
                        ['GRAPHICS', 'MONIQUE / FRANZ / SHALOM', '1826'],
                        ['GRAPHICS', 'VHON / ROSELLE', '1826'],
                        ['CSR / MSN FOUNDATION', 'GINESTY / YANNIE', '1835'],
                        ['INTERNAL AUDIT', 'GRACE / KATE', '1938'],
                    ]],
                    ['9th Floor', null, [
                        ['VICE PRESIDENT - FINANCE', 'GINA TAN - CRUZ', '1901'],
                        ['VICE PRESIDENT - SALES', 'FE VARILLA', '1902'],
                        ['VICE PRESIDENT PRODUCT DEVELOPMENT', 'DONG SHENG LIU', '1908'],
                        ['SR. FINANCE MANAGER', 'NANCY CHENG', '1910'],
                        ['SALES MANAGER', 'AVEGAIL GUEVARRA', '1903'],
                        ['SALES MANAGER', 'SETTY NOLASCO', '1906'],
                        ['ASSISTANT MANAGER PURCHASING', 'CLIFFORD CUREG', '1909'],
                        ['R88 - ASSISTANT FINANCE MANAGER', 'SARAH PON', '1919'],
                        ['ACCOUNTING', 'DIANE / MYRA', '1914'],
                        ['ACCOUNTING', 'MARJ / RACHELLE', '1918'],
                        ['ACCOUNTING', 'SAIRAH / LOVELY', '1920'],
                        ['ACCOUNTING', 'RICHARD / RENA', '1921'],
                        ['ACCOUNTING', 'ADRIAN / LENRECEL', '1922'],
                        ['ACCOUNTING', 'ABBY / CINDY', '1923'],
                        ['ACCOUNTING', 'RIA / ELAINE', '1924'],
                        ['ACCOUNTING', 'DIVINE / MARICRIS / CRISIA', '1929'],
                        ['ACCOUNTING', 'SHERLYN', '1926'],
                        ['PAYROLL', 'RAYMUND / JASPER / ROGER', '1928'],
                        ['SALES', 'KIM', '1915'],
                        ['SALES', 'RECHELLE', '1916'],
                        ['PURCHASING', 'ALLAN', '1917'],
                        ['PURCHASING', 'MARLOU', '1927'],
                        ['PURCHASING', 'CESS', '1931'],
                        ['PURCHASING', 'ROAH', '1937'],
                        ['PURCHASING', 'JENN', '1941'],
                        ['PURCHASING', 'MONETTE / CARLO', '1998'],
                        ['RADIANT 88 - ACCOUNTING', 'LHEA', '1944'],
                        ['OMTI / OMEI', 'ROMMEL / CHRISTAR', '1282'],
                    ]],
                    ['10th Floor', null, [
                        ['MSN SR. EXECUTIVE ASSISTANT', 'RENALYN DIMAYACYAC', '1011'],
                        ['MSN EXECUTIVE ASSISTANT', 'JOANNE / CARLA / JEN', '1012'],
                        ['MSN EXECUTIVE ASSISTANT', 'KISHA', '1013'],
                    ]],
                ],
            ],
            [
                'name' => 'Building B',
                'trunkline' => '8 521-8999',
                'mobile' => '0999-888-9333',
                'floors' => [
                    ['Ground Floor', null, [
                        ['MAINTENANCE MANAGER', 'DANNY MENDEZ', '2111'],
                        ['ASSISTANT MANAGER - RDU', 'FELY CALCES', '1911'],
                        ['RDU', 'SONNY', '1514'],
                        ['RDU', 'JASPER', '1912'],
                    ]],
                    ['3rd Floor', null, [
                        ['TRADE MARKETING WAREHOUSE', 'ROD', '1833'],
                    ]],
                    ['5th Floor', null, [
                        ['RDU', 'JONNEL / RICHARD', '(20) 2500'],
                        ['RDU', 'JERRSON / MICHAEL', '(20) 2500'],
                        ['QC', 'TEEDWARD', '1521'],
                    ]],
                    ['7th Floor', null, [
                        ['EKILA 888 - SALES MANAGER', 'LORRAINE JAVALERA', '2714'],
                        ['VI - DIGITAL RETAIL MANAGER', 'DUINKIE TAN - ANG', '2801'],
                        ['WBS - ASSISTANT MANAGER FINANCE / SUPPLY CHAIN', 'MARLON JOHN BAGUSTO', '(20) 2075'],
                        ['SC GROUP - ACCOUNTING', 'JOSSEN', '2711'],
                        ['MSN LIFESTYLE', 'DIANNE / MAY-ANN', '2703'],
                        ['MSN LIFESTYLE', 'CHA / HAZE', '2727'],
                        ['MSN LIFESTYLE', 'CHERRY', '2728'],
                        ['CCTV ROOM', null, '2707'],
                        ['PURELIVING', null, '(29) 2925'],
                        ['ONZ - ACCOUNTING', 'RIZA / MARIAH', '(20) 2030'],
                        ['ONZ', 'ARSY / MACC', '(20) 2025'],
                    ]],
                    ['8th Floor', null, [
                        ['PARAGON - GENERAL MANAGER', 'LEONCIO CANTONJOS', '2901'],
                        ['PARAGON', 'AILEEN', '2910'],
                        ['PARAGON - ACCOUNTING', 'QUEENCEL / MYRA / DARILLE', '2917'],
                        ['PARAGON - ACCOUNTING', 'HANNAH / ANDRE', '2917'],
                        ['PARAGON - OPERATIONS', 'ANDREA / LORNHEY', '2922'],
                    ]],
                    ['9th Floor', null, [
                        ['ASSISTANT VICE PRESIDENT VENTURE DEVELOPMENT', 'RICHARD FRANCISCO', '2001'],
                        ['SENIOR MANAGER - HR & ADMIN', 'VIVIAN CASTILLO', '1907 / 2005'],
                        ['CORPCOMM MANAGER', 'KEREN SIBLAG', '2020'],
                        ['ASSISTANT MANAGER COMPENBEN / ONBOARDING / GEN HR', 'LORIE DIONISIO', '2015'],
                        ['ASSISTANT MANAGER EMPLOYEE & LABOR RELATIONS', 'MICHAEL "MIKE" RACADIO', '2016'],
                        ['ASSISTANT MANAGER RECRUITMENT', 'RENELITA TANDUS', '1935'],
                        ['HR / ADMIN / CORPCOMM', 'JASMINE', '1939'],
                        ['HR / ADMIN / CORPCOMM', 'JOYCE / ERICK / YEL', '2012'],
                        ['HR / ADMIN / CORPCOMM', 'CHESKA / COLINE / ARIANNE', '2017'],
                        ['VENTURE DEVELOPMENT', 'EMMIE / DEO', '2018'],
                        ['VENTURE DEVELOPMENT', 'JAMES / JOBIN', '2019'],
                    ]],
                    ['11th Floor', null, [
                        ['LANBENA', null, '2911'],
                    ]],
                ],
            ],
        ];
    }
}
